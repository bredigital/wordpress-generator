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
use TWPG\Services\SystemLog;
use TWPG\Services\Mail;
use TWPG\Services\Com;
use TWPG\Models\Sitelog;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Functions that handle the creation or modification of generator sites.
 */
class Create extends Controls {
	protected $config;
	protected $fs;
	protected $log;
	protected $db;
	protected $mail;
	protected $com;

	public function __construct( Configuration $config, Filesystem $fs, SystemLog $log, Sitelog $sitelog, Mail $mail, Com $com ) {
		$this->config = $config;
		$this->fs     = $fs;
		$this->log    = $log;
		$this->db     = $sitelog;
		$this->mail   = $mail;
		$this->com    = $com;
	}

	/**
	 * Creates a new sandbox site.
	 *
	 * @param string  $email
	 * @param string  $name
	 * @param boolean $useSSL
	 * @return string URL of the new site admin panel.
	 */
	public function newSandbox( $email = '', $name = '', $useSSL = false ) {
		$id = $this->db->create( $name, $_SERVER['REMOTE_ADDR'], false );

		$this->log->info( "Creation started for site {$id}." );
		if ( $this->fs->exists( $id ) ) {
			$this->log->warning( "Site {$id} already exists. Exiting." );

			return [ false, 'Folder exists' ];
		}

		$id_dir     = "{$this->config->directories->rootpath}/{$id}";
		$ssl        = ( $useSSL ) ? 'https://' : 'http://';
		$site_owner = ( empty( $email ) ) ? "no-reply@example.com" : $email;
		$site_name  = ( empty( $name ) ) ? "Spinup {$id}" : $name;

		$this->fs->mkdir( "{$id_dir}/" );
		$path = realpath( $id_dir );
		$url  = "{$ssl}{$this->config->general->domain}/{$id}";

		$o = $this->com->wpcli_call( 'core download', $path );
		$o = $this->com->wpcli_call( "config create --dbhost=\"{$this->config->database->host}:{$this->config->database->port}\" --dbname=\"{$this->config->database->database}\" --dbuser=\"{$this->config->database->user}\" --dbpass=\"{$this->config->database->password}\" --dbprefix=\"wp_t{$id}_\" --skip-check", $path );

		$opts = [
			'WP_DEBUG'         => 'true',
			'WP_DEBUG_LOG'     => 'true',
			'WP_DEBUG_DISPLAY' => 'false',
		];
		foreach ( $opts as $name => $val ) {
			$this->com->wpcli_call( "config set {$name} {$val} --raw", $path, null, true );
		}

		$o = $this->com->wpcli_call( "core install --title=\"{$site_name}\" --admin_user=admin --admin_password=password --admin_email=\"{$site_owner}\" --skip-email", $path, $url );

		$this->com->wpcli_call( "option add _wp_generator_id \"{$id}\"" , $path, null, true );

		$this->log->info( 'Copying in plugins & themes.' );
		$this->fs->mirror( "{$this->config->directories->wordpressInstall}/mu-plugins", "{$id_dir}/wp-content/mu-plugins" );
		$this->fs->mirror( "{$this->config->directories->wordpressInstall}/plugins", "{$id_dir}/wp-content/plugins" );
		$this->fs->mirror( "{$this->config->directories->wordpressInstall}/themes", "{$id_dir}/wp-content/themes" );

		$this->log->info( 'Process finished.' );

		$this->mail->sendEmailToSiteOwner(
			$id,
			"Site {$id} Has Been Created",
			"<p>The following development website has been created:</p>
			<p><a href='http://{$this->config->general->domain}/{$id}'>{$this->config->general->domain}/{$id}</a></p>
			<p>You have 60 days from today before the site is deleted, however it can be extended on the homepage. You will be notified 5 days prior to the pending removal.</p>"
		);

		return "{$ssl}{$this->config->general->domain}/{$id}/wp-admin";
	}

	/**
	 * Extends the time for a specified sandbox.
	 *
	 * @param integer $id
	 * @param integer $days Days to extend by. Defaults to 30 day extensions.
	 * @return void
	 */
	public function extend( $id, $days = 30 ) {
        $this->log->info( "Extending site {$id} expiry by {$days} days." );
		$this->db->extendtime( $id, $days );
		$this->db->setReminderStatus( $id, false );

		header( "Location: http://{$this->config->general->domain}" );
    }
}
