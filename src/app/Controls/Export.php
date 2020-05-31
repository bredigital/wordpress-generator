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
use TWPG\Services\Com;

use Comodojo\Zip\Zip;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class designed to handle the full export process of generator sites.
 */
class Export extends Controls
{
	protected $config;
	protected $fs;
	protected $log;
	protected $com;

	public function __construct(Configuration $config, Filesystem $fs, SystemLog $log, Com $com)
	{
		$this->config = $config;
		$this->fs     = $fs;
		$this->log    = $log;
		$this->com    = $com;
	}

	public function createExportArchive(int $id):string
	{
		$this->log->info("Export in progress for site {$id}.");
		ignore_user_abort(true);
		set_time_limit(0);

		$this->cleanup($id);
		$expo = $this->exportFilesystem($id);#
		$this->fs->remove("{$this->config->directories->siteExports}/dbdump-{$id}.sql");

		$this->log->info('Export finished.');

		return $expo;
	}

	/**
	 * Deletes remaining staging zip and sql from the assets folder.
	 *
	 * @param integer $id
	 * @return boolean
	 */
	public function cleanup(int $id):bool
	{
		$this->fs->remove("{$this->config->directories->siteExports}/export-site-{$id}.zip");
		$this->fs->remove("{$this->config->directories->siteExports}/dbdump-{$id}.sql");

		return true;
	}

	/**
	 * Exports the filesystem into a zip archive. Can also include the database.
	 *
	 * @param integer $id
	 * @param boolean $includeDatabase
	 * @return string Name of the archive
	 */
	private function exportFilesystem(int $id, bool $includeDatabase = true):string
	{
		$this->log->info("Exporting {$id} filesystem.");

		$rootPath = "{$this->config->directories->sites}/{$id}";
		$zipName  = "export-site-{$id}.zip";
		$zipPath  = "{$this->config->directories->siteExports}/{$zipName}";

		$zip = Zip::create($zipPath);
		$zip->add($rootPath, true);
		$zip->add("{$this->config->directories->assets}/export-readme.txt");

		if ($includeDatabase) {
			$dumpName = $this->exportDatabase($id);
			$zip->add("{$this->config->directories->siteExports}/{$dumpName}");
		}

		return $zipName;
	}

	/**
	 * Exports the relevant database entries for the website into a dump file.
	 *
	 * @param integer $id
	 * @return string Name of the dump
	 */
	private function exportDatabase(int $id):string
	{
		$this->log->info("Exporting {$id} database.");

		$path  = realpath("{$this->config->directories->sites}/{$id}");
		$dname = "dbdump-{$id}.sql";
		$dloc  = realpath($this->config->directories->siteExports) . "/{$dname}";
		$this->com->setPath($path);
		$this->com->exportDb($dloc);

		return $dname;
	}
}
