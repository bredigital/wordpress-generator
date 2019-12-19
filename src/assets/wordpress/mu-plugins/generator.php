<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 *
 * @wordpress-plugin
 * Plugin Name: Generator configuration
 * Description: Passes synchronisation configurations from the main generator.
 * version: 0.2
 * Author: BRE Digital
 * Author URI: http://digital.bre.co.uk/
 * License: MIT
 */

use Carbon\Carbon;

$comp_path = __DIR__ . '/../../../vendor/autoload.php';
$table     = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", 'wpmgr_sitelog' ) );

if ( file_exists( $comp_path ) && ! empty( $table ) ) {
	require_once $comp_path;

	$config = new TWPG\Services\Configuration();

	add_action( 'phpmailer_init', function ( $phpmailer ) use ( $config ) {
		$phpmailer->Host     = $config->mail->SMTP;
		$phpmailer->Port     = $config->mail->Port;
		$phpmailer->Username = $config->mail->user;
		$phpmailer->Password = $config->mail->password;
		$phpmailer->SMTPAuth = $config->mail->auth;

		$phpmailer->IsSMTP();
	});

	add_filter( 'wp_mail_from', function( $original ) use ( $config ) {
		return $config->mail->fromAddress;
	});

	add_filter( 'wp_mail_from_name', function( $original ) use ( $config ) {
		return $config->mail->fromName;
	});

	add_action( 'admin_bar_menu', function( $admin_bar ) use ( $config ) {
		global $wpdb;

		$site_id   = get_option('_wp_generator_id', -1);
		$site_conf = $wpdb->get_results( "SELECT * FROM wpmgr_sitelog WHERE id = {$site_id}" );
		$remaining = Carbon::parse( $site_conf[0]->created_date )->diffInDays(
			Carbon::parse( $site_conf[0]->expiry_date ),
			false
		);
		$time_warn = ( $remaining <= 5 ) ? 'color:red;' : '';
		$site_name = ( ! empty( $site_conf[0]->name ) ) ? " - {$site_conf[0]->name}" : '';

		if ( $site_id >= 0 ) {
			$admin_bar->add_menu([
				'id'    => 'wpgen-menu',
				'title' => 'Generator',
			]);

			$admin_bar->add_node([
				'id'     => 'wpgen-site-id',
				'title'  => "Site {$site_id}{$site_name}",
				'parent' => 'wpgen-menu',
			]);

			$admin_bar->add_node([
				'id'     => 'wpgen-killdate',
				'title'  => "Remaining days: <span style='{$time_warn}font-weight:bold'>{$remaining}</span>",
				'parent' => 'wpgen-menu',
			]);

			$admin_bar->add_group([
				'id'     => 'wpgen-return-g',
				'parent' => 'wpgen-menu',
				'meta'      => [
					'class' => 'ab-sub-secondary ab-submenu'
				]
			]);

			$admin_bar->add_node([
				'id'     => 'wpgen-return',
				'title'  => 'Back to Generator',
				'href'   => "//{$config->general->domain}",
				'parent' => 'wpgen-return-g',
			]);
		}
	}, 30 );
} else {
	add_action( 'admin_notices', function () {
		echo wp_kses_post('
			<div class="notice notice-error">
				<p>
				This site appears to have been detached from the generator. Please delete the file
				<strong>wp-content/mu-plugins/generator.php</strong>.
				</p>
			</div>'
		);
	});
}
