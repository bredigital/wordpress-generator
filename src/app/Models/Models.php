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

	public function doIExist( $tableName ) {
		try {
			$this->PDO_ALL->query( "SELECT 1 FROM {$tableName} LIMIT 1" );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function createSitelog() {
		$sql = "CREATE TABLE IF NOT EXISTS `wpmgr_sitelog` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) DEFAULT NULL,
			`secure` tinyint(1) NOT NULL DEFAULT '0',
			`emailreminder` tinyint(4) NOT NULL DEFAULT '0',
			`created_by` varchar(45) DEFAULT NULL,
			`created_date` datetime DEFAULT NULL,
			`expiry_date` datetime DEFAULT NULL,
			`deleted_by` varchar(45) DEFAULT NULL,
			`deleted_date` datetime DEFAULT NULL,
			PRIMARY KEY (`id`)
		  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
		  ";

		$this->PDO_ALL->exec( $sql );
	}
}
