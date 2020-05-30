<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use Symfony\Component\Dotenv\Dotenv;
use Carbon\Carbon;

/**
 * .env translator class, to validate and use values stored in the configuration file.
 *
 * @todo Refactor. This is just... awful.
 */
class Configuration
{
	protected $dotenv;

	public $general;
	public $database;
	public $mail;
	public $directories;

	public function __construct()
	{
		$env_loc = __DIR__ . '/../../.env';
		if (file_exists($env_loc)) {
			$dotenv = new Dotenv();
			$dotenv->load($env_loc);
		}

		$this->general     = (object) $this->setGeneral();
		$this->database    = (object) $this->setDatabase();
		$this->mail        = (object) $this->setMail();
		$this->directories = (object) $this->setDirectories();
	}

	private function setGeneral():array
	{
		return [
			'domain'         => $this->getEnv('GN_DOMAIN'),
			'debug'          => $this->getEnvBoolean('GN_DEBUG'),
			'sslAvailable'   => $this->getEnvBoolean('GN_SSL_AVAILABLE'),
			'rootDir'        => __DIR__ . '/../..',
			'system_wp'      => $this->getEnvBoolean('GN_SYSTEM_WPCLI'),
			'custom_wp_path' => $this->getEnv('GN_WPCLI', 'wp'),
			'disable_env'    => $this->getEnvBoolean('GN_ENV_DISABLE', false)
		];
	}

	private function setDatabase():array
	{
		return [
			'host'      => $this->getEnv('DB_HOST', 'localhost'),
			'port'      => $this->getEnv('DB_PORT', '3306'),
			'database'  => $this->getEnv('DB_DATABASE'),
			'user'      => $this->getEnv('DB_USER'),
			'password'  => $this->getEnv('DB_PASSWORD'),
			'maintable' => 'wpmgr_sitelog',
			'charset'   => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci'
		];
	}

	private function setMail():array
	{
		return [
			'enabled'     => $this->getEnvBoolean('MAIL_ON'),
			'fromAddress' => $this->getEnv('MAIL_ADDR'),
			'fromName'    => $this->getEnv('MAIL_NAME', 'WordPress Generator'),
			'cc'          => $this->getEnv('MAIL_CC'),
			'auth'        => $this->getEnvBoolean('MAIL_SMTPAUTH'),
			'user'        => $this->getEnv('MAIL_USER'),
			'password'    => $this->getEnv('MAIL_PASS'),
			'useSSL'      => $this->getEnvBoolean('MAIL_SSL'),
			'SMTP'        => $this->getEnv('MAIL_SMTP', 'localhost'),
			'Port'        => $this->getEnv('MAIL_PORT', '25'),
		];
	}

	private function setDirectories():array
	{
		$root = realpath(__DIR__ . '/../../');

		return [
			'rootpath'         => "{$root}",
			'cache'            => "{$root}/cache",
			'assets'           => "{$root}/assets",
			'wordpressInstall' => "{$root}/assets/wordpress",
			'siteExports'      => "{$root}/assets/exports"
		];
	}

	/**
	 * Gets the value from the specified env, with validation processing.
	 *
	 * @param string $name    ENV variable name.
	 * @param string $default Value to return if the ENV is not found.
	 * @return string
	 */
	private function getEnv(string $name, string $default = ''):string
	{
		$get = ( isset($_ENV[ $name ]) ) ? $_ENV[ $name ] : null;
		if (! empty($get)) {
			return $get;
		} else {
			return $default;
		}
	}

	/**
	 * Gets the value from the specified env, with validation processing.
	 *
	 * @param string $name    ENV variable name.
	 * @param boolean $default Value to return if the ENV is not found.
	 * @return boolean
	 */
	private function getEnvBoolean(string $name, bool $default = false):bool
	{
		$get = ( isset($_ENV[ $name ]) ) ? $_ENV[ $name ] : null;
		if (! empty($get)) {
			return filter_var($get, FILTER_VALIDATE_BOOLEAN);
		} else {
			return $default;
		}
	}
}
