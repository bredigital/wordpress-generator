<?php declare(strict_types=1);
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

		$this->wp = ( $this->config->general->system_wp ) ? $this->config->general->custom_wp_path : realpath( $this->config->directories->rootpath . '/vendor/wp-cli/wp-cli/bin/wp' );
	}

	public function wpcli_call( string $command, string $directory, ?string $link = null, bool $log = true, bool $return_command = false ):string {
		$path = '--path=' . realpath( $directory );
		$url  = ( isset( $link ) ) ? '--url=' . $link : null;
		$env  = ( ! $this->config->general->disable_env ) ? "WP_CLI_CACHE_DIR='{$this->config->directories->rootpath}/cache/'" : null;
		$com  = "{$env} {$this->wp} {$command} {$url} {$path} --allow-root 2>&1";

		if ( $return_command ) {
			return $com;
		}

		$response = shell_exec( $com );

		if ( $log ) {
			$this->log->info( 'WP-CLI responded with: ' . $response );
		}

		return $response;
	}

	public function wpcli_exportdb( string $dloc, string $directory, ?string $link = null, bool $log = true ):string {
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

	public function wpcli_version():string {
		$response = shell_exec( "{$this->wp} --version 2>&1" );
		if ( strpos( $response, 'WP-CLI' ) !== false ) {
			return $response;
		} else {
			$this->log->error( 'Cannot determine WP-CLI version: ' . $response );
			return 'WP-CLI Init Error';
		}
	}
}
