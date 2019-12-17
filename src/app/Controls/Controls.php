<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Controls;

use Carbon\Carbon;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controls {
	/**
	 * Setup function for a twig environment.
	 *
	 * @todo Improve structure from procedural into an OO class layout.
	 * @return Twig_Environment
	 */
	public function twigSetup() {
		$loader = new FilesystemLoader( __DIR__ . '/../View' );
		$twig   = new Environment( $loader );

		return $twig;
	}

	/**
	 * Returns how many days are remaining between today and the provided date.
	 *
	 * @var Carbon  $date      The created date.
	 * @var Integer $extension Optional date extension.
	 * @var Integer $days      Optional expiry, default is 61 days.
	 * @return Integer Value between 0 and the specified days.
	 */
	public function daysRemaining( $date, $extension = 0, $days = 61 ) {
		$remaining = Carbon::now()->diffInDays( $date->addDays( $days + $extension ), false );

		return ($remaining <= 0) ? 0 : $remaining;
	}
}
