<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Models;

use TWPG\Models\Models;

use PDOException;

/**
 * Accesses WordPress user tables to pull across admin details, for communication purposes.
 */
class Owner extends Models {

	/**
	 * Returns the founding user (user 1) from the specified site ID.
	 *
	 * @param integer $id
	 * @return array|boolean
	 */
	public function getOwnerBySiteId( $id ) {
		$stmt = null;
		try {
			$stmt = $this->PDO_ALL->query(
				"SELECT user_nicename, user_email FROM wp_t{$id}_users where ID = 1"
			);
		} catch( PDOException $e ) {
			$stmt = false;
		}

		if ( $stmt !== false ) {
			return $stmt->fetch();
		} else {
			return false;
		}
	}
}
