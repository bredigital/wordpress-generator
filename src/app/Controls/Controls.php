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

class Controls
{
	/**
	 * Returns how many days are remaining between today and the provided date.
	 *
	 * @var Carbon $startDate The created date.
	 * @var Carbon $endDate   The expiry date.
	 * @return Integer Value between 0 and the specified days.
	 */
	public function daysRemaining(Carbon $startDate, Carbon $endDate):int
	{
		return $startDate->diffInDays($endDate, false);
	}

	/**
	 * Creates a wpgen-config.json file.
	 *
	 * @param integer $id   Sitelog ID.
	 * @param string  $name Sitelog Name.
	 * @param string  $url  URL of the site within generator.
	 * @return void Dumps the file inside the working directory.
	 */
	public function setSiteGenConfig($id, $name, $url):void
	{
		$setfile = "{$this->config->directories->sites}/{$id}/wpgen-config.json";
		if ($this->fs->exists($setfile)) {
			$this->fs->remove($setfile);
		}
		file_put_contents($setfile, json_encode([
			'genver' => 1,
			'id'     => $id,
			'name'   => $name,
			'prefix' => "wp_t{$id}_",
			'url'    => $url,
		]));
	}

	/**
	 * Copies over the generator identity plugin, as well as user-defined plugins and themes.
	 *
	 * @param integer $id                   Sitelog ID.
	 * @param boolean $copyUserSetupPlugins Whether the user plugins are copied in.
	 */
	public function copySetupFiles($id, $copyUserSetupPlugins = true)
	{
		$dir = "{$this->config->directories->sites}/{$id}";
		$this->log->info('Copying in plugins & themes.');
		if ($copyUserSetupPlugins) {
			$this->fs->mirror("{$this->config->directories->wordpressInstall}/mu-plugins", "{$dir}/wp-content/mu-plugins");
			$this->fs->mirror("{$this->config->directories->wordpressInstall}/plugins", "{$dir}/wp-content/plugins");
			$this->fs->mirror("{$this->config->directories->wordpressInstall}/themes", "{$dir}/wp-content/themes");
		}
		$this->fs->copy("{$this->config->directories->assets}/generator.php", "{$dir}/wp-content/mu-plugins/generator.php");
	}
}
