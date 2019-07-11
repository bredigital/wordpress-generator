<?php
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
use TWPG\Models\Sitelog;

use Symfony\Component\Filesystem\Filesystem;

/**
 * The deletion class for the generator sites.
 */
class Delete extends Controls {
	protected $config;
	protected $fs;
	protected $log;
	protected $db;
	protected $mail;

	public function __construct( Configuration $config, Filesystem $fs, SystemLog $log, Sitelog $sitelog, Mail $mail ) {
		$this->config = $config;
		$this->fs     = $fs;
		$this->log    = $log;
		$this->db     = $sitelog;
		$this->mail   = $mail;
	}

	/**
	 * The main executed function.
	 *
	 * @param integer $id
	 * @param boolean $cron
	 * @return boolean
	 */
	public function deleteSite( $id, $cron = false ) {
		$this->log->info( "Deletion started for site {$id}." );

		$downloadable = "";
		if ( $this->fs->exists( "{$this->config->directories->rootpath}/assets/exports/export-site-{$id}.zip" ) ) {
			$downloadable = "<p>A <a href='http://{$this->config->general->domain}/assets/exports/export-site-{$id}.zip'>downloadable backup of the deleted site</a> is available.</p>";
		}

		$this->mail->sendEmailToSiteOwner(
			$id,
			"Site {$id} Has Been Deleted",
			"<p>The following website has been deleted (manually or automatically) from the WordPress generator:</p>
			<p><a href='http://{$this->config->general->domain}/{$id}'>{$this->config->general->domain}/{$id}</a></p>
			<p>If this was unexpected, please note that there is a 60 day timer on each site which requires manually extending if desired to stay longer.</p>
			{$downloadable}"
		);

		$this->db->purge( $id, ( $cron ) ? 'CRON' : $_SERVER['REMOTE_ADDR'] );
		$this->fs->remove( [ $id ] );

		$this->log->info( "Site {$id} deletion successful." );

		return true;
	}

	/**
	 * Removes the WordPress staging folder. Boolean response depending on if action was taken.
	 *
	 * @return boolean
	 */
	public function forceDelete() {
		$this->log->info( 'Fix called. Force-deleting \'wordpress\' folder.' );

		if ( file_exists( "{$this->config->directories->rootpath}/wordpress/" ) ) {
			try {
				$this->fs->remove( [ "{$this->config->directories->rootpath}/wordpress/" ] );
			} catch ( Exception $e ) {
				$this->log->error( 'An error occured during the delete.', $e->getMessage() );
				return false;
			}

			$this->log->info( '\'wordpress\' folder deleted.' );
			return true;
		} else {
			$this->log->info( 'No \'wordpress\' folder found.' );

			return false;
		}
	}
}
