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
use TWPG\Services\Mail;
use TWPG\Services\SystemLog;
use TWPG\Services\ViewRender;
use TWPG\Models\Sitelog;

use Symfony\Component\Filesystem\Filesystem;

/**
 * The deletion class for the generator sites.
 */
class Delete extends Controls
{
	protected $config;
	protected $fs;
	protected $log;
	protected $sitelog;
	protected $mail;
	protected $view;

	public function __construct(
		Configuration $config,
		Filesystem $fs,
		SystemLog $log,
		Sitelog $sitelog,
		Mail $mail,
		ViewRender $view
	) {
		$this->config  = $config;
		$this->fs      = $fs;
		$this->log     = $log;
		$this->sitelog = $sitelog;
		$this->mail    = $mail;
		$this->view    = $view;
	}

	/**
	 * The main executed function.
	 *
	 * @param integer $id
	 * @param boolean $cron
	 * @return boolean
	 */
	public function deleteSite(int $id, bool $cron = false):bool
	{
		$site_info = $this->sitelog->get($id);

		if (empty($site_info['expiry_date'])) {
			$this->log->info("Deletion for site {$id} prevented due to protected status.");
			return false;
		}

		$this->log->info("Deletion started for site {$id}.");

		$downloadable = "";
		if ($this->fs->exists("{$this->config->directories->rootpath}/assets/exports/export-site-{$id}.zip")) {
			$downloadable = "http://{$this->config->general->domain}/assets/exports/export-site-{$id}.zip";
		}

		$name = ( isset($site_info['name']) ) ? $site_info['name'] : "Site {$id}";
		$this->mail->sendEmailToSiteOwner(
			$id,
			"{$name} has been deleted",
			$this->view->render(
				'Mail/delete',
				[
					'url'         => "http://{$this->config->general->domain}/{$id}",
					'downloadZip' => ( ! empty($downloadable) ) ? $downloadable : null,
				],
				true
			)
		);

		$this->sitelog->purge($id, ( $cron ) ? 'CRON' : $_SERVER['REMOTE_ADDR']);
		$this->fs->remove([ $id ]);

		$this->log->info("Site {$id} deletion successful.");

		return true;
	}

	/**
	 * Removes the WordPress staging folder. Boolean response depending on if action was taken.
	 *
	 * @return boolean
	 */
	public function forceDelete():bool
	{
		$this->log->info('Fix called. Force-deleting \'wordpress\' folder.');

		if (file_exists("{$this->config->directories->rootpath}/wordpress/")) {
			try {
				$this->fs->remove([ "{$this->config->directories->rootpath}/wordpress/" ]);
			} catch (\Exception $e) {
				$this->log->error('An error occured during the delete: ' . $e->getMessage());
				return false;
			}

			$this->log->info('\'wordpress\' folder deleted.');
			return true;
		} else {
			$this->log->info('No \'wordpress\' folder found.');

			return false;
		}
	}
}
