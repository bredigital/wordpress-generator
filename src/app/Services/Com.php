<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use TWPG\Services\Configuration;
use TWPG\Services\SystemLog;

/**
 * Executes commands on WP-CLI.
 */
class Com {
	protected $config;
	protected $log;

	protected $wp;
	public function __construct( Configuration $config, SystemLog $log ) {
		$this->config = $config;
		$this->log    = $log;

		$this->wp = ( $this->config->general->system_wp ) ? 'wp' : realpath( $this->config->directories->rootpath . '/vendor/wp-cli/wp-cli/bin/wp' );
	}

	public function wpcli_call( $command, $directory, $link = null, $log = true, $return_command = false ) {
		$path = '--path=' . realpath( $directory );
		$url  = ( isset( $link ) ) ? '--url=' . $link : null;
		$com  = "{$this->wp} {$command} {$url} {$path} --allow-root";

		if ( $return_command ) {
			return $com;
		}

		$response = shell_exec( $com );

		if ( $log ) {
			$this->log->info( 'WP-CLI responded with: ' . $response );
		}

		return $response;
	}

	public function wpcli_exportdb( $dloc, $directory, $link = null, $log = true ) {
		$subcom = $this->wpcli_call(
			"db tables --all-tables-with-prefix --format=csv",
			$directory,
			$link,
			$log,
			true
		);

		return $this->wpcli_call(
			"db export {$dloc} --tables=$({$subcom})",
			$directory,
			$link,
			$log
		);
	}

	public function wpcli_version() {
		$response = shell_exec( "{$this->wp} --version" );
		if ( ! empty( $response ) ) {
			return $response;
		} else {
			return 'WP-CLI Init Error';
		}
	}
}
