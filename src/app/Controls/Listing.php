<?php declare(strict_types=1);
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
use TWPG\Services\ViewRender;
use TWPG\Models\Sitelog;

use Symfony\Component\Filesystem\Filesystem;
use Carbon\Carbon;

/**
 * Visual functions for listing and displaying the current and expired sites.
 */
class Listing extends Controls
{
	protected $config;
	protected $fs;
	protected $log;
	protected $sitelog;
	protected $com;
	protected $view;

	public function __construct(
		Configuration $config,
		Filesystem $fs,
		SystemLog $log,
		Sitelog $sitelog,
		Com $com,
		ViewRender $view
	) {
		$this->config  = $config;
		$this->fs      = $fs;
		$this->log     = $log;
		$this->sitelog = $sitelog;
		$this->com     = $com;
		$this->view    = $view;
	}

	public function showListing():void
	{
		$listings = $this->sitelog->getAll(false);

		$listCollection = [];
		if (count($listings) > 0) {
			foreach ($listings as $listing) {
				@include "{$this->config->directories->sites}/{$listing["id"]}/wp-includes/version.php";

				$useSSL = ( $listing["secure"] === 1 ) ? 'https://' : 'http://';

				$listCollection[] = [
					'name'        => ( empty($listing['name']) ) ? '<i>Purpose not set</i>' : $listing['name'],
					'version'     => ( empty($wp_version) ) ? null : $wp_version,
					'daysRemain'  => $this->daysRemaining(Carbon::parse($listing['created_date']), Carbon::parse($listing['expiry_date'])),
					'isProtected' => ( ! empty($listing['expiry_date']) ) ? false : true,
					'urls'        => [
						'site'   => $useSSL . $_ENV['GN_DOMAIN'] . '/sites/' . $listing['id'],
						'delete' => "index.php?control=delete&id=" . $listing['id'],
						'export' => "index.php?control=export&id=" . $listing['id'],
						'extend' => "index.php?control=extend&id=" . $listing['id'],
						'log'    => "index.php?control=log&id="    . $listing['id']
					],
					'dbExists'    => ( count($this->sitelog->tables($listing['id'])) > 0 ) ? true : false,
					'fsExists'    => ($this->fs->exists("{$this->config->directories->sites}/{$listing["id"]}/index.php")) ? true : false
				];
			}
		} else {
			$aa = "No visible entries in the system.";
		}

		$this->view->render(
			'listing',
			[
				'page_title'    => 'Home',
				'ssl_available' => $this->config->general->sslAvailable,
				'listings'      => $listCollection,
				'banner'        => $this->showBannerMessage(),
			]
		);
	}

	private function showBannerMessage():?array
	{
		$dir = $this->config->directories->rootpath;
		if ($this->fs->exists("{$dir}/problem.txt")) {
			return [
				'type'    => 'problem',
				'message' => file_get_contents("{$dir}/problem.txt"),
			];
		}

		$dir = $this->config->directories->rootpath;
		if ($this->fs->exists("{$dir}/warning.txt")) {
			return [
				'type'    => 'warning',
				'message' => file_get_contents("{$dir}/warning.txt"),
			];
		}

		$dir = $this->config->directories->rootpath;
		if ($this->fs->exists("{$dir}/info.txt")) {
			return [
				'type'    => 'info',
				'message' => file_get_contents("{$dir}/info.txt"),
			];
		}

		return null;
	}
}
