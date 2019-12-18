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
	 * @var Carbon  $date      The created date.
	 * @var Integer $extension Optional date extension.
	 * @var Integer $days      Optional expiry, default is 61 days.
	 * @return Integer Value between 0 and the specified days.
	 */
	public function daysRemaining( Carbon $date, int $extension = 0, int $days = 61 ):int {
		$remaining = Carbon::now()->diffInDays( $date->addDays( $days + $extension ), false );

		return ($remaining <= 0) ? 0 : $remaining;
	}
}
