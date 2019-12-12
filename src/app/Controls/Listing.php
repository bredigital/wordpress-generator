<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Controls;

use TWPG\Controls\Controls;
use TWPG\Services\Configuration;
use TWPG\Services\Com;
use TWPG\Services\SystemLog;
use TWPG\Models\Sitelog;

use Symfony\Component\Filesystem\Filesystem;
use Carbon\Carbon;

/**
 * Visual functions for listing and displaying the current and expired sites.
 */
class Listing extends Controls {
	protected $config;
	protected $fs;
	protected $log;
	protected $db;
	protected $com;

	public function __construct( Configuration $config, Filesystem $fs, SystemLog $log, Sitelog $sitelog, Com $com ) {
		$this->config = $config;
		$this->fs     = $fs;
		$this->log    = $log;
		$this->db     = $sitelog;
		$this->com    = $com;
	}

	public function showListing() {
		$listings = $this->db->getAll( false );

		$listCollection = [];
		if ( count( $listings ) > 0 ) {
			foreach ( $listings as $listing ) {
				@include __DIR__ .  "/../../{$listing["id"]}/wp-includes/version.php";

				$useSSL = ( $listing["secure"] === 1 ) ? 'https://' : 'http://';

				$listCollection[] = [
					'name'       => ( empty( $listing['name'] ) ) ? '<i>Purpose not set</i>' : $listing['name'],
					'version'    => ( empty( $wp_version ) ) ? null : $wp_version,
					'daysRemain' => $this->daysRemaining( Carbon::parse( $listing['created_date'] ), $listing['extensiondays'] ),
					'urls'       => [
						'site'   => $useSSL . getenv( 'GN_DOMAIN' ) . '/' . $listing['id'],
						'delete' => "index.php?control=delete&id=" . $listing['id'],
						'export' => "index.php?control=export&id=" . $listing['id'],
						'extend' => "index.php?control=extend&id=" . $listing['id'],
						'log'    => "index.php?control=log&id="    . $listing['id']
					],
					'dbExists'   => ( count( $this->db->tables( $listing['id'] ) ) > 0 ) ? true : false
				];
			}
		} else {
			$aa = "No visible entries in the system.";
		}

		echo $this->twigSetup()->render(
			'listing.html.twig',
			[
				'page_title'    => 'Home',
				'ssl_available' => $this->config->general->sslAvailable,
				'listings'      => $listCollection,
				'versions'      => [
					'php'   => phpversion(),
					'wpcli' => $this->com->wpcli_version(),
				],
				'banner'        => $this->showBannerMessage(),
			]
		);
	}

	private function showBannerMessage() {
		$dir = $this->config->directories->rootpath;
		if ( $this->fs->exists( "{$dir}/problem.txt" ) ) {
			return [
				'type'    => 'problem',
				'message' => file_get_contents( "{$dir}/problem.txt" ),
			];
		}

		$dir = $this->config->directories->rootpath;
		if ( $this->fs->exists( "{$dir}/warning.txt" ) ) {
			return [
				'type'    => 'warning',
				'message' => file_get_contents( "{$dir}/warning.txt" ),
			];
		}

		$dir = $this->config->directories->rootpath;
		if ( $this->fs->exists( "{$dir}/info.txt" ) ) {
			return [
				'type'    => 'info',
				'message' => file_get_contents( "{$dir}/info.txt" ),
			];
		}

		return null;
	}
}
