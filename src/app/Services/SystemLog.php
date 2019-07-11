<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SystemLog extends Logger {
	public function __construct() {
		parent::__construct( 'MAIN' );
		$this->pushHandler( new StreamHandler( __DIR__ . '/../../error.log', Logger::DEBUG ) );
	}
}
