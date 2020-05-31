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
	}

	/**
	 * Imports an existing configuration into the WordPress system.
	 *
	 * @param string      $email
	 * @param string      $file
	 * @return string|null URL of the new site admin panel.
	 */
	public function import(string $email, array $file = null):?string
	{
		$filename = $file["name"];
		$cacheDir = $this->config->directories->cache . '/import';
		$id       = $this->sitelog->create($filename, $_SERVER['REMOTE_ADDR'], isset($_SERVER['HTTPS']));
		
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

		if ( ! $this->fs->exists( $cacheDir ) ) {
			$this->fs->mkdir( $cacheDir );
		}
		move_uploaded_file($file["tmp_name"], $cacheDir . '/' . $filename);
		
		// Create a staging folder, extract ZIP and delete archive.
		$this->log->info("Extracting archive '{$filename}' for site {$id}.");
		$this->fs->mkdir($cacheDir . "/process-{$id}");
		$validZip  = zip::check($cacheDir . '/' . $filename);
		$zipstream = Zip::open($cacheDir . '/' . $filename);
		$zipstream->extract($cacheDir . "/process-{$id}");
		$zipstream->close();
		$this->fs->remove($cacheDir . '/' . $filename);

		$database_import = [];
		foreach (glob($cacheDir . "/process-{$id}/*.sql") as $file) {
			$database_import[] = $file;
		}

		$rdir     = realpath($cacheDir . "/process-{$id}");
		$dbfile   = realpath($database_import[0]);
		$dbfinal  = "{$rdir}/database.sql";
		$needle   = 'wp_t2_';
		$haystack = "wp_t{$id}_";
		passthru("sed s/{$needle}/{$haystack}/ {$dbfile} > {$dbfinal}");

		$this->log->info("Preparing database {$database_import[0]} of site {$id} for importing.");

		foreach ($database_import as $db) {
			$this->fs->remove($db);
		}

		$this->fs->mirror( $cacheDir . "/process-{$id}", $id_dir );

		// Install the database.
		$this->log->info("Importing site {$id} database into the generator.");
		$this->com->createConfig($id, true);
		$this->com->importDb($id_dir . '/database.sql');

		$this->log->info("Database import complete. Reconfiguring import of site {$id} into generator mode.");
		$this->com->setConfigs(
			[
				'WP_DEBUG'         => 'true',
				'WP_DEBUG_LOG'     => 'true',
				'WP_DEBUG_DISPLAY' => 'false',
			]
		);

		// Setup WordPress with their details, and indentify with the mu-plugin.
		$site_name = "Spinup {$id}";
		$account   = $this->com->install($site_name, $email);
		$this->com->setOptions([ '_wp_generator_id' => $id ]);

		// Copy all the plugins and themes for a new site.
		$this->log->info('Copying in generator plugin.');
		$plugin = '/mu-plugins/generator.php';
		//$this->fs->mirror("{$this->config->directories->wordpressInstall}{$plugin}", "{$id_dir}/wp-content{$plugin}");

		$this->log->info('Process finished.');

		// Let the site owner know their details.
		$this->mail->sendEmailToSiteOwner(
			(int) $id,
			"Site '{$site_name}' Has Been imported",
			$this->view->render(
				'Mail/create',
				[
					'url'      => $site_url,
					'username' => $account['username'],
					'password' => $account['password'],
				],
				true
			)
		);

		// Cleanup.
		$this->fs->remove($cacheDir . "/process-{$id}");

		return "{$site_url}/wp-admin";
	}
}
