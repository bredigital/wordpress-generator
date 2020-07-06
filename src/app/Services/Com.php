<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use Exception;
use TWPG\Services\Configuration;
use TWPG\Services\SystemLog;

/**
 * Executes commands on WP-CLI.
 */
class Com
{
	protected $config;
	protected $log;

	protected $wp;
	protected $path;
	protected $url;
	public function __construct(Configuration $config, SystemLog $log)
	{
		$this->config = $config;
		$this->log    = $log;

		$this->wp = ( $this->config->general->system_wp ) ? $this->config->general->custom_wp_path : realpath($this->config->directories->rootpath . '/vendor/wp-cli/wp-cli/bin/wp');
	}

	/**
	 * Set the filesystem path that is operated on.
	 *
	 * @param string|null $path Path of operation.
	 * @return self
	 */
	public function setPath(?string $path):self
	{
		$this->path = $path;

		return $this;
	}

	/**
	 * Set the URL of the site to be processed. Not normally required.
	 *
	 * @param string|null $path Path of operation.
	 * @return self
	 */
	public function setURL(?string $url):self
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * Runs the CLI process of downloading the desired version of WordPress.
	 *
	 * @param string $version Desired version of WordPress. 'latest' (default), 'nightly' and a specific version supported.
	 * @return void
	 */
	public function download(string $version = 'latest'):void
	{
		$version = escapeshellarg($version);
		$this->wpcliCall("core download --version={$version}");
	}

	/**
	 * Sets the input user as admin.
	 *
	 * @param string $user User identifier.
	 * @return void
	 */
	public function setAdmin(string $user):void
	{
		$user = escapeshellarg($user);
		$this->wpcliCall("user set-role {$user} administrator");
	}

	/**
	 * Find-replace mapper.
	 *
	 * @param string $find    The string to find in the DB...
	 * @param string $replace ...and what to replace it with.
	 * @return void
	 */
	public function replace(string $find, string $replace):void
	{
		$find    = escapeshellarg($find);
		$replace = escapeshellarg($replace);
		$this->wpcliCall("search-replace {$find} {$replace}");
	}

	/**
	 * Creates the wp-config file with generator settings.
	 *
	 * @param string $id Used for the prefix, generally matches the site URL.
	 * @return void
	 */
	public function createConfig(string $id, bool $force = false):void
	{
		$db_host = escapeshellarg($this->config->database->host . ':' . $this->config->database->port);
		$db_name = escapeshellarg($this->config->database->database);
		$db_user = escapeshellarg($this->config->database->user);
		$db_pass = escapeshellarg($this->config->database->password);
		$site_id = escapeshellarg("wp_t{$id}_");

		$this->wpcliCall(
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
					($force) ? "--force" : ""
				]
			)
		);
	}

	/**
	 * Sets the input array as additional configuration items in wp-config.php. MUST BE CREATED FIRST.
	 *
	 * @param array $configs Array key will be the config name, and value will match.
	 * @return void
	 */
	public function setConfigs(array $configs):void
	{
		foreach ($configs as $name => $value) {
			$name  = escapeshellarg($name);
			$value = escapeshellarg($value);

			$this->wpcliCall("config set {$name} {$value} --raw");
		}
	}

	/**
	 * Adds options in the array as entries in the wp-options table.
	 *
	 * @param array $configs Array key will be the config key, and value will match.
	 * @return void
	 */
	public function setOptions(array $configs):void
	{
		foreach ($configs as $key => $value) {
			$key   = escapeshellarg($key);
			$value = escapeshellarg($value);

			$this->wpcliCall("option update {$key} {$value}");
		}
	}

	/**
	 * Tells WordPress the configuration elements, then completes the '5 minute install' automagically.
	 * set_url must be set.
	 *
	 * @param string      $title    WordPress site title.
	 * @param string      $email    Administrative user email (no email is sent in this step).
	 * @param string|null $username Different username from 'admin' if desired.
	 * @param string|null $password Specify a password, or leave for the system to generate.
	 * @return array
	 */
	public function install(string $title, string $email, ?string $username = null, ?string $password = null):array
	{
		$title    = escapeshellarg($title);
		$email    = escapeshellarg($email);
		$username = escapeshellarg(( isset($username) ) ? $username : 'admin');
		$password = escapeshellarg(( isset($password) ) ? $password : $this->generatePassword());

		$this->wpcliCall(
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
			)
		);

		return [
			'username' => $username,
			'password' => $password,
		];
	}

	/**
	 * Generates a database export for the specified site.
	 *
	 * @param string $dloc ???
	 * @return void
	 */
	public function exportDb(string $dloc):void
	{
		$subcom = $this->wpcliCall("db tables --all-tables-with-prefix --format=csv", true, true);

		$this->wpcliCall("db export {$dloc} --tables=$({$subcom})");
	}

	/**
	 * Imports a database file into the Generator.
	 *
	 * @param string $sqlFile A filesystem location to import.
	 * @return void
	 */
	public function importDb(string $sqlFile):void
	{
		$this->wpcliCall("db import {$sqlFile}");
	}

	/**
	 * Gets the WP-CLI version. Does not require path or url.
	 *
	 * @return string
	 */
	public function version():string
	{
		$response = shell_exec("{$this->wp} --version 2>&1");
		if (strpos($response, 'WP-CLI') !== false) {
			return $response;
		} else {
			$this->log->error('Cannot determine WP-CLI version: ' . $response);
			return 'WP-CLI Init Error';
		}
	}

	/**
	 * Generates a medium security password.
	 *
	 * @return string A randomly-generated password string.
	 */
	private function generatePassword():string
	{
		$range = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890$%£@.,~?!';
		$pass  = [];
		$al    = strlen($range) - 1;

		for ($i = 0; $i < 12; $i++) {
			$n      = rand(0, $al);
			$pass[] = $range[ $n ];
		}

		return implode($pass);
	}

	/**
	 * The big cheese of Com. Formulate a WP-CLI shell command, executes it under the permissions of the
	 * running user, and logs whatever is outputted from the command.
	 *
	 * @throws Exception if the reply from the shell command contains an error.
	 * @param string  $command        The command issued to WP-CLI.
	 * @param boolean $log            Should Com write output to log? Default is true.
	 * @param boolean $return_command Instead of running, return the command. Designed for nested statements.
	 * @return string Response from shell_exec. Also written to the main log, if enabled.
	 */
	private function wpcliCall(string $command, bool $log = true, bool $return_command = false):string
	{
		$path = '--path=' . escapeshellarg(realpath($this->path));
		$url  = ( isset($this->url) ) ? '--url=' . escapeshellarg($this->url) : null;
		$env  = ( ! $this->config->general->disable_env ) ? "WP_CLI_CACHE_DIR='{$this->config->directories->rootpath}/cache/'" : null;
		$com  = "{$env} {$this->wp} {$command} {$url} {$path} --allow-root 2>&1";

		if ($return_command) {
			return $com;
		}

		$response = shell_exec($com);

		if (strpos($response, 'Error:') === false) {
			if ($log) {
				$this->log->info('WP-CLI responded with: ' . $response);
			}

			return $response;
		} else {
			$this->log->error('WP-CLI responded with: ' . $response);
			throw new Exception('WP-CLI responded with an error. Check the logs for the output.');
		}
	}
}
