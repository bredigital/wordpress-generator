<?php
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
class Configuration {
	Protected $dotenv;

	Public $general;
	Public $database;
	Public $mail;
	public $directories;

	public function __construct() {
		$env_loc = __DIR__ . '/../../.env';
		if ( file_exists( $env_loc ) ) {
			$dotenv = new Dotenv();
			$dotenv->load( $env_loc );
		}

		$this->general     = (object) $this->setGeneral();
		$this->database    = (object) $this->setDatabase();
		$this->mail        = (object) $this->setMail();
		$this->directories = (object) $this->setDirectories();
	}

	private function setGeneral() {
		return [
			'domain'       => $this->getEnv( 'GN_DOMAIN' ),
			'debug'        => $this->getEnv( 'GN_DEBUG', false, 'boolean' ),
			'sslAvailable' => $this->getEnv( 'GN_SSL_AVAILABLE', false, 'boolean' ),
			'rootDir'      => __DIR__ . '/../..',
			'system_wp'    => $this->getEnv( 'GN_SYSTEM_WPCLI', false, 'boolean' ),
		];
	}

	private function setDatabase() {
		return [
			'host'      => $this->getEnv( 'DB_HOST', 'localhost' ),
			'port'      => $this->getEnv( 'DB_PORT', 3306 ),
			'database'  => $this->getEnv( 'DB_DATABASE' ),
			'user'      => $this->getEnv( 'DB_USER' ),
			'password'  => $this->getEnv( 'DB_PASSWORD' ),
			'maintable' => 'wpmgr_sitelog'
		];
	}

	private function setMail() {
		return [
			'enabled'     => $this->getEnv( 'MAIL_ON', false, 'boolean' ),
			'fromAddress' => $this->getEnv( 'MAIL_ADDR' ),
			'fromName'    => $this->getEnv( 'MAIL_NAME', 'WordPress Generator' ),
			'cc'          => $this->getEnv( 'MAIL_CC' ),
			'auth'        => $this->getEnv( 'MAIL_SMTPAUTH', false, 'boolean' ),
			'user'        => $this->getEnv( 'MAIL_USER' ),
			'password'    => $this->getEnv( 'MAIL_PASS' ),
			'useSSL'      => $this->getEnv( 'MAIL_SSL', false, 'boolean' ),
			'SMTP'        => $this->getEnv( 'MAIL_SMTP', 'localhost' ),
			'Port'        => $this->getEnv( 'MAIL_PORT', 25 ),
		];
	}

	private function setDirectories() {
		$root = realpath( __DIR__ . '/../../' );

		return [
			'rootpath'         => "{$root}",
			'assets'           => "{$root}/assets",
			'wordpressInstall' => "{$root}/assets/wordpress",
			'siteExports'      => "{$root}/assets/exports"
		];
	}

	/**
	 * Gets the value from the specified env, with validation processing.
	 *
	 * @link http://php.net/manual/en/function.gettype.php
	 * @param string $name
	 * @param string $type Type of the specified value. Default string.
	 * @return mixed
	 */
	private function getEnv( $name, $default = false, $type = 'string' ) {
		$get = getenv( $name );
		if ( ! empty( $get ) ) {
			if ( $type == 'boolean' ) {
				return filter_var( $get, FILTER_VALIDATE_BOOLEAN );
			} else {
				return $get;
			}
		} else {
			return $default;
		}
	}
}
