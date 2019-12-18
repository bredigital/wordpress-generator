<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Models;

use TWPG\Services\Configuration;

use PDO;

class Models {
	protected $PDO_ALL;
	protected $config;

	public function __construct() {
		$this->config = new Configuration();

		$dsn = 'mysql:host=' . $this->config->database->host . ';port=' . $this->config->database->port . ';dbname=' . $this->config->database->database . ';charset=utf8';
		$opt = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];

		$this->PDO_ALL = new PDO(
			$dsn,
			$this->config->database->user,
			$this->config->database->password,
			$opt
		);
	}

	/**
	 * Gets a list of databases that are appended with the provided site ID.
	 *
	 * @param integer $id
	 * @return array
	 */
	public function tables( int $id ):array {
		$result     = $this->PDO_ALL->query( 'show tables' );
		$collection = [];

		while ( $row = $result->fetch( PDO::FETCH_NUM ) ) {
			if ( ! $id ) {
				array_push( $collection, $row[0] );
			} else {
				if ( strpos( $row[0], 't' . $id ) !== false ) {
					array_push( $collection, $row[0] );
				}
			}
		}
		return $collection;
	}

	/**
	 * Gets MySQL Version.
	 *
	 * @return string
	 */
	public function version():string {
		return $this->PDO_ALL->query( 'select version()' )->fetchColumn();
	}
}
