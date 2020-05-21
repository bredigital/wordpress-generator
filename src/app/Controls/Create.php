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

/**
 * Functions that handle the creation or modification of generator sites.
 */
class Create extends Controls {
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
	 * Creates a new sandbox site.
	 *
	 * @param string      $email
	 * @param string      $name
	 * @param boolean     $useSSL
	 * @param string|null $version
	 * @return string|null URL of the new site admin panel.
	 */
	public function newSandbox( string $email, ?string $name = null, bool $useSSL = false, ?string $version = null ):?string {
		$id      = $this->sitelog->create( $name, $_SERVER['REMOTE_ADDR'], $useSSL );
		$version = ( isset( $version ) ) ? $version : 'latest';

		// Check if this site folder already exists.
		$this->log->info( "Creation started for site {$id}." );
		if ( $this->fs->exists( $id ) ) {
			$this->log->warning( "Site {$id} already exists. Exiting." );

			return null;
		}

		$id_dir   = "{$this->config->directories->rootpath}/{$id}";
		$ssl      = ( $useSSL ) ? 'https://' : 'http://';
		$site_url = "{$ssl}{$this->config->general->domain}/{$id}";

		$this->fs->mkdir( "{$id_dir}/" );
		$this->com->set_path( realpath( $id_dir ) );
		$this->com->set_url( $site_url );

		// Download and setup the requested copy of WordPress.
		$this->com->download( $version );
		$this->com->create_config( $id );
		$this->com->set_configs(
			[
				'WP_DEBUG'         => 'true',
				'WP_DEBUG_LOG'     => 'true',
				'WP_DEBUG_DISPLAY' => 'false',
			]
		);

		// Setup WordPress with their details, and indentify with the mu-plugin.
		$site_name = ( empty( $name ) ) ? "Spinup {$id}" : $name;
		$account   = $this->com->install( $site_name, $email );
		$this->com->set_options( [ '_wp_generator_id' => $id ] );

		// Copy all the plugins and themes for a new site.
		$this->log->info( 'Copying in plugins & themes.' );
		$this->fs->mirror( "{$this->config->directories->wordpressInstall}/mu-plugins", "{$id_dir}/wp-content/mu-plugins" );
		$this->fs->mirror( "{$this->config->directories->wordpressInstall}/plugins", "{$id_dir}/wp-content/plugins" );
		$this->fs->mirror( "{$this->config->directories->wordpressInstall}/themes", "{$id_dir}/wp-content/themes" );

		$this->log->info( 'Process finished.' );

		// Let the site owner know their details.
		$this->mail->sendEmailToSiteOwner(
			(int) $id,
			"Site '{$site_name}' Has Been Created",
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

		return "{$site_url}/wp-admin";
	}

	/**
	 * Extends the time for a specified sandbox.
	 *
	 * @param integer $id
	 * @param integer $days Days to extend by. Defaults to 30 day extensions.
	 * @return void
	 */
	public function extend( int $id, int $days = 30 ):void {
        $this->log->info( "Extending site {$id} expiry by {$days} days." );
		$this->sitelog->extendtime( $id, $days );
		$this->sitelog->setReminderStatus( $id, false );

		header( "Location: http://{$this->config->general->domain}" );
	}
}
