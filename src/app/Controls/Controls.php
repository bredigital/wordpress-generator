<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Controls;

use Carbon\Carbon;

class Controls {
	/**
	 * Returns how many days are remaining between today and the provided date.
	 *
	 * @var Carbon $startDate The created date.
	 * @var Carbon $endDate   The expiry date.
	 * @return Integer Value between 0 and the specified days.
	 */
	public function daysRemaining( Carbon $startDate, Carbon $endDate ):int {
		return $startDate->diffInDays( $endDate, false );
	}
}
