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
use TWPG\Controls\Delete;
use TWPG\Controls\Export;
use TWPG\Services\Configuration;
use TWPG\Services\Mail;
use TWPG\Services\SystemLog;
use TWPG\Models\Sitelog;

use Carbon\Carbon;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Processes designed to be run on a daily shedule, preferably called by cron/Task Manager.
 */
class Cron extends Controls {
	protected $config;
	protected $fs;
	protected $log;
	protected $db;
	protected $mail;
	protected $delete;
	protected $export;

	public function __construct( Configuration $config, Filesystem $fs, SystemLog $log, Sitelog $sitelog, Mail $mail, Delete $delete, Export $export ) {
		$this->config = $config;
		$this->fs     = $fs;
		$this->log    = $log;
		$this->db     = $sitelog;
		$this->mail   = $mail;
		$this->delete = $delete;
		$this->export = $export;
	}

	/**
	 * Runs the cron processes.
	 * @return array
	 */
	public function shedule() {
		$this->log->info( "Cron job started." );

		$listings  = $this->db->getAll( false );
		$prunelist = [];

		foreach ( $listings as $listing ) {
			if ( filter_var( $listing['protected'], FILTER_VALIDATE_BOOLEAN ) ) {
				$this->log->info( "Site {$listing['id']} skipped as it is marked as protected." );
				continue;
			}

			$daysRemaining = $this->daysRemaining( Carbon::parse( $listing['created_date']), $listing['extensiondays'] );

			// Polite Warning
			if ( $daysRemaining <= 5 ) {
				if ( ! $this->db->getReminderStatus( $listing["id"] ) ) {
					$this->log->info( "{$daysRemaining} for site {$listing['id']}, sending the site owner an expiry warning." );
					$this->emailReminder( $listing['id'] );
					$this->db->setReminderStatus( $listing["id"], true );
				}
			}

			// Grim Reaper
			if ( $daysRemaining === 0 ) {
				$this->log->info( 'Time elapsed for site ' . $listing["id"] . '. Removing.' );

				// Try and save their site, in case the system accidentally deletes it, or they're on holiday.
				$this->export->createExportArchive( $listing["id"] );
				$this->delete->deleteSite( $listing["id"], true );
				$this->log->info( 'Site ' . $listing["id"] . ' removed.' );
				array_push( $prunelist, $listing["id"] );
			}
		}

		$this->log->info( 'Cron job ended.' );

		return $prunelist;
	}

	/**
	 * Sends an email to the site owner warning them their site will expire soon.
	 * @param integer $id
	 * @return void
	 */
	private function emailReminder( $id ) {
		$site_info = $this->db->get( $id );
		$name      = ( isset( $site_info['name'] ) ) ? $site_info['name'] : "Site {$id}";

		$this->mail->sendEmailToSiteOwner(
			$id,
			"{$name} expiry warning",
			"<p>The following website is close to expiry:</p>
			<p><a href='http://{$this->config->general->domain}/{$id}'>{$this->config->general->domain}/{$id}</a></p>
			<p>If you wish to keep this site, <a href='{$this->config->general->domain}'>please visit the generator</a> to extend your container or export it.</p>"
		);
	}
}
