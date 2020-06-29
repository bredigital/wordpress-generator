<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Controls;

use TWPG\Controls\Controls;
use TWPG\Services\Configuration;
use TWPG\Services\SystemLog;
use TWPG\Services\Mail;
use TWPG\Services\Com;
use TWPG\Services\ViewRender;
use TWPG\Models\Sitelog;

use Symfony\Component\Filesystem\Filesystem;
use Comodojo\Zip\Zip;
use Exception;
use OutOfRangeException;

/**
 * Import existing WordPress sites into the generator.
 */
class Import extends Controls
{
	protected $config;
	protected $fs;
	protected $log;
	protected $sitelog;
	protected $mail;
	protected $com;
	protected $view;

	private $cacheDir;

	public function __construct(
		Configuration $config,
		Filesystem $fs,
		SystemLog $log,
		Sitelog $sitelog,
		Mail $mail,
		Com $com,
		ViewRender $view
	) {
		$this->config  = $config;
		$this->fs      = $fs;
		$this->log     = $log;
		$this->sitelog = $sitelog;
		$this->mail    = $mail;
		$this->com     = $com;
		$this->view    = $view;

		$this->cacheDir = $this->config->directories->cache . '/import';
	}

	/**
	 * Imports an existing configuration into the WordPress system.
	 *
	 * @param string      $email
	 * @param string      $file
	 * @return string|null URL of the new site admin panel.
	 */
	public function import(string $email, array $file):?string
	{
		$filename = $file["name"];
		$cacheDir = $this->cacheDir;
		$id       = $this->sitelog->create('Importing...', $_SERVER['REMOTE_ADDR'], isset($_SERVER['HTTPS']));
		
		// Check if this site folder already exists.
		$this->log->info("Creation started for site {$id}.");
		if ($this->fs->exists($id)) {
			$this->log->warning("Site {$id} already exists. Exiting.");

			return null;
		}

		$id_dir   = "{$this->config->directories->sites}/{$id}";
		$ssl      = ( isset($_SERVER['HTTPS']) ) ? 'https://' : 'http://';
		$site_url = "{$ssl}{$this->config->general->domainSites}/{$id}";

		$this->fs->mkdir("{$id_dir}/");
		$this->com->setPath(realpath($id_dir));
		$this->com->setURL($site_url);

		$this->unpackArchive($file, $id);

		$setup        = null;
		$genConfigLoc = "{$cacheDir}/process-{$id}/wpgen-config.json";
		$dupConfigLoc = "{$cacheDir}/process-{$id}/dup-installer/dup-archive__*.txt";
		if ($this->fs->exists($genConfigLoc)) {
			// Check if the import is a Generator package.
			$setup = $this->processArchive(
				ImportEnum::WPGEN,
				$id,
				$genConfigLoc,
				"{$this->cacheDir}/process-{$id}/*.sql",
				$email,
				$site_url
			);
		} elseif (count(glob($dupConfigLoc)) === 1) {
			// Check if the import is a Duplicator package.
			$setup = $this->processArchive(
				ImportEnum::DUP,
				$id,
				glob($dupConfigLoc)[0],
				"{$this->cacheDir}/process-{$id}/dup-installer/dup-database__*.sql",
				$email,
				$site_url
			);
		} else {
			$this->fs->remove($cacheDir . "/process-{$id}");
			wpgen_die('Valid archive uploaded, but was not in the supported format.');
		}

		// Copy all the plugins and themes for a new site.
		$this->log->info('Copying in generator plugin.');
		$this->fs->copy("{$this->config->directories->assets}/generator.php", "{$id_dir}/wp-content/mu-plugins/generator.php");

		$this->log->info('Process finished.');

		// Let the site owner know their details.
		$this->mail->sendEmailToSiteOwner(
			(int) $id,
			"Site '{$setup['name']}' Has Been imported",
			$this->view->render(
				'Mail/create',
				[
					'url'      => $site_url,
					'username' => $setup['username'],
					'password' => $setup['password'],
				],
				true
			)
		);

		// Cleanup.
		$this->fs->remove($cacheDir . "/process-{$id}");

		return "{$site_url}/wp-admin";
	}

	/**
	 * Handles import functionality related to WordPress Generator archives.
	 *
	 * @param integer $type          Type of archve.
	 * @param integer $id            The new site ID allocated.
	 * @param string  $siteConfigLoc Path to the json config file.
	 * @param string  $siteDBLoc     Globbable path to SQL file (first is chosen).
	 * @param string  $email         The importer's email address.
	 * @param string  $siteUrl       The URL the new site will use.
	 * @return array 'name' of the site, 'username' and 'password' of the controlling/admin user.
	 */
	private function processArchive($type, $id, $siteConfigLoc, $siteDBLoc, $email, $siteUrl) {
		$config = $this->loadArchiveConfig($type, $siteConfigLoc);
		$idDir  = "{$this->config->directories->sites}/{$id}";

		$this->sitelog->updateName((int)$id, $config['name']);

		$database_import = [];
		foreach (glob($siteDBLoc) as $file) {
			$database_import[] = $file;
		}

		$rdir     = realpath($this->cacheDir . "/process-{$id}");
		$dbfile   = realpath($database_import[0]);
		$dbfinal  = "{$rdir}/database.sql";
		$input    = $config['prefix'];
		$output   = "wp_t{$id}_";
		passthru("sed s/{$input}/{$output}/ {$dbfile} > {$dbfinal}");

		$this->log->info("Preparing database {$database_import[0]} of site {$id} for importing.");

		foreach ($database_import as $db) {
			$this->fs->remove($db);
		}

		$this->fs->mirror($this->cacheDir . "/process-{$id}", $idDir);

		// Install the database.
		$this->log->info("Importing site {$id} database into the generator.");
		try {
			$this->com->createConfig((string)$id, true);
			$this->com->importDb($idDir . '/database.sql');

			$this->log->info("Database import complete. Reconfiguring import of site {$id} into generator mode.");
			$this->com->setConfigs(
				[
					'WP_HOME'          => "'{$siteUrl}'",
					'WP_SITEURL'       => "'{$siteUrl}'",
					'WP_DEBUG'         => 'true',
					'WP_DEBUG_LOG'     => 'true',
					'WP_DEBUG_DISPLAY' => 'false',
				]
			);

			// Setup WordPress with their details, and indentify with the mu-plugin.
			$site_name = $config['name'];
			$account   = $this->com->install($site_name, $email);
			$this->com->setOptions([ '_wp_generator_id' => $id ]);
		} catch (Exception $e) {
			wpgen_die($e->getMessage());
		}

		return [
			'name'     => $site_name,
			'username' => $account['username'],
			'password' => $account['password'],
		];
	}

	/**
	 * Unpacks the uploaded archive.
	 *
	 * @param array $file The desired file from the $_FILE global.
	 * @param integer $id The sitelog ID.
	 */
	private function unpackArchive($file, $id)
	{
		$cacheDir = $this->config->directories->cache . '/import';
		$zipFile  = $cacheDir . '/' . $file["name"];
		$pDir     = $cacheDir . "/process-{$id}";
 
		if (! $this->fs->exists($cacheDir)) {
			$this->fs->mkdir($cacheDir);
		}
		move_uploaded_file($file["tmp_name"], $zipFile);
		
		// Create a staging folder, extract ZIP and delete archive.
		$this->log->info("Extracting archive '{$file['name']}' for site {$id}.");
		$this->fs->mkdir($pDir);
		$validZip  = zip::check($zipFile);
		$zipstream = Zip::open($zipFile);
		$zipstream->extract($pDir);
		$zipstream->close();
		$this->fs->remove($zipFile);
	}

	/**
	 * Loads up the generator config for WordPress Generator archives.
	 *
	 * @throws OutOfRangeException if $type is outside of Enum.
	 * @param integer $type          Type of archive import.
	 * @param string  $siteConfigLoc Path to the config file.
	 * @return array
	 */
	private function loadArchiveConfig($type, $siteConfigLoc)
	{
		$imp = json_decode(file_get_contents($siteConfigLoc), false);
		switch ($type) {
			case ImportEnum::WPGEN:
				$this->log->info("Generator archive (v{$imp->genver}) discovered.");
				return [
					'name'     => $imp->name,
					'prefix'   => $imp->prefix,
					'prev_url' => $imp->url,
				];
			case ImportEnum::DUP:
				$this->log->info("Duplicator archive (v{$imp->version_dup}) discovered.");
				return [
					'name'     => $imp->blogname,
					'prefix'   => $imp->wp_tableprefix,
					'prev_url' => $imp->url_old,
				];
			default:
				throw new OutOfRangeException('Type index not supported');
		}
	}
}

class ImportEnum
{
	const WPGEN = 1;
	const DUP   = 2;
}