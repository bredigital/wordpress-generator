<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

include __DIR__ . '/vendor/autoload.php';

use TWPG\Services\Configuration;
$di     = new DI\Container();
$config = new Configuration();

if ( $config->general->debug ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
}

$control     = ( !empty( $_GET['control'] ) ) ? $_GET['control'] : null;
$id          = ( !empty( $_GET['id'] ) ) ? $_GET['id'] : 0;
$name        = ( !empty( $_GET['name'] ) ) ? $_GET['name'] : null;
$email       = ( !empty( $_GET['email'] ) ) ? $_GET['email'] : null;
$useSSL      = ( $config->general->sslAvailable ) ? ( !empty( $_GET['secure'] ) ) ? (bool)$_GET['secure'] : false : false;
$fulloutput  = ( isset( $_GET['full'] ) ) ? true : false;

if ( $control === null ) {
	$di->get( TWPG\Controls\Listing::class )->showListing();
} else {
	switch ($control) {
		case 'create':
		case 'extend':
			$create = $di->get( TWPG\Controls\Create::class );
			if( $control == 'extend' ) {
				$create->extend( $_GET["id"] );
			} else {
				$result = $create->newSandbox( $email, $name, $useSSL );
				if( isset( $result ) ) {
					header( 'Location: ' . $result );
				} else {
					echo 'An error has occurred in your request. The error has been logged. Please see the system log for more details.';
				}
			}
			break;
		case 'delete':
			$di->get( TWPG\Controls\Delete::class )->deleteSite( $id );
			header( 'Location: http://' . $config->general->domain );
			break;
		case 'log':
			$di->get( TWPG\Controls\Log::class )->display( $id, $fulloutput );
			break;
		case 'export':
			$file = $di->get( TWPG\Controls\Export::class )->createExportArchive( $id );

			$file_url = "{$config->directories->assets}/exports/{$file}";
			header( 'Content-Type: application/zip' );
			header( 'Content-Transfer-Encoding: Binary' );
			header( 'Content-disposition: attachment; filename="' . basename( $file_url ) . '"' );
			readfile( $file_url );
			break;
		case "cron":
			$di->get( TWPG\Controls\Cron::class )->shedule();
			break;
		default:
			echo 'Invalid control received.';
			break;
	}
}
