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
	protected $path;
	protected $url;
	public function __construct( Configuration $config, SystemLog $log ) {
		$this->config = $config;
		$this->log    = $log;

		$this->wp = ( $this->config->general->system_wp ) ? $this->config->general->custom_wp_path : realpath( $this->config->directories->rootpath . '/vendor/wp-cli/wp-cli/bin/wp' );
	}

	/**
	 * Set the filesystem path that is operated on.
	 *
	 * @param string|null $path Path of operation.
	 * @return self
	 */
	public function set_path( ?string $path ):self {
		$this->path = $path;

		return $this;
	}

	/**
	 * Set the URL of the site to be processed. Not normally required.
	 *
	 * @param string|null $path Path of operation.
	 * @return self
	 */
	public function set_url( ?string $url ):self {
		$this->url = $url;

		return $this;
	}

	/**
	 * Runs the CLI process of downloading the desired version of WordPress.
	 * 'path' is required.
	 *
	 * @param string $version Desired version of WordPress. 'latest' (default), 'nightly' and a specific version supported.
	 * @return void
	 */
	public function download( string $path, string $version = 'latest' ):void {
		$version = escapeshellarg( $version );
		$this->wpcli_call( "core download --version={$version}", $path );
	}

	public function create_config( string $path, string $id ):void {
		$db_host = escapeshellarg( $this->config->database->host . ':' . $this->config->database->port );
		$db_name = escapeshellarg( $this->config->database->database );
		$db_user = escapeshellarg( $this->config->database->user );
		$db_pass = escapeshellarg( $this->config->database->password );
		$site_id = escapeshellarg( "wp_t{$id}_" );

		$this->wpcli_call(
			implode(
				' ',
				[
					'config create',
					"--dbhost={$db_host}",
					"--dbname={$db_name}",
					"--dbuser={$db_user}",
					"--dbpass={$db_pass}",
					"--dbprefix={$site_id}",
					"--skip-check",
				]
			),
			$path
		);
	}

	public function set_configs( string $path, array $configs ):void {
		foreach ( $configs as $name => $value ) {
			$name  = escapeshellarg( $name );
			$value = escapeshellarg( $value );

			$this->wpcli_call( "config set {$name} {$value} --raw", $path );
		}
	}

	public function set_options( string $path, array $configs ):void {
		foreach ( $configs as $key => $value ) {
			$key   = escapeshellarg( $key );
			$value = escapeshellarg( $value );

			$this->wpcli_call( "option add {$key} {$value}" , $path );
		}
	}

	public function install( string $path, string $url, string $title, string $email, ?string $username = null, ?string $password = null ):array {
		$title    = escapeshellarg( $title );
		$email    = escapeshellarg( $email );
		$username = escapeshellarg( ( isset( $username ) ) ? $username : 'admin' );
		$password = escapeshellarg( ( isset( $password ) ) ? $password : $this->generatePassword() );

		$this->wpcli_call(
			implode(
				' ',
				[
					'core install',
					"--title={$title}",
					"--admin_user={$username}",
					"--admin_password={$password}",
					"--admin_email={$email}",
					"--skip-email",
				]
			),
			$path,
			$url
		);

		return [
			'username' => $username,
			'password' => $password,
		];
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

	/**
	 * Generates a medium security password.
	 *
	 * @return string A randomly-generated password string.
	 */
	private function generatePassword():string {
		$range = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890$%£@.,~?!';
		$pass  = [];
		$al    = strlen( $range ) - 1;

		for ( $i = 0; $i < 12; $i++ ) {
			$n      = rand( 0, $al );
			$pass[] = $range[ $n ];
		}

		return implode( $pass );
	}

	private function wpcli_call( string $command, string $directory, ?string $link = null, bool $log = true, bool $return_command = false ):string {
		$path = '--path=' . escapeshellarg( realpath( $directory ) );
		$url  = ( isset( $link ) ) ? '--url=' . escapeshellarg( $link ) : null;
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
}
