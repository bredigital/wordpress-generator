<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Controls;

use TWPG\Services\Configuration;
use TWPG\Services\ViewRender;
use TWPG\Controls\Controls;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Handles the display of collected logs, either system-wide or individual debug-mode sites.
 */
class Log extends Controls
{
	protected $config;
	protected $fs;
	protected $view;

	public function __construct(Configuration $config, Filesystem $fs, ViewRender $view)
	{
		$this->config = $config;
		$this->fs     = $fs;
		$this->view   = $view;
	}

	public function display(int $id = 0, bool $showAll = false):void
	{
		$logContents = null;
		if ($id == 0) {
			$content = "{$this->config->directories->rootpath}/error.log";
			if ($this->fs->exists($content)) {
				if (! $showAll) {
					$logContents = $this->excludeFull(file_get_contents($content));
				} else {
					$logContents = file_get_contents($content);
				}
			}
		} else {
			$content =  "{$this->config->directories->sites}/$id/wp-content/debug.log";
			if ($this->fs->exists($content)) {
				$logContents = file_get_contents($content);
			}
		}

		$this->view->render(
			'log',
			[
				'page_title'   => ( $id == 0 ) ? 'System Log' : "Site {$id} Log",
				'log_header'   => ( $id == 0 ) ? 'Error Log for generator' : "Error Log for site {$id}",
				'log_contents' => $logContents,
				'return_url'   => $this->config->general->domain,
				'show_exluder' => ( $id == 0 ) ? true : false
			]
		);
	}

	private function excludeFull(string $blob):?string
	{
		$document = explode("\n", $blob);
		$filtered = '';

		foreach ($document as $line) {
			if (! strpos($line, '.INFO') && ! strpos($line, '.DEBUG')) {
				$filtered .= $line . "\n";
			}
		}

		return ( $filtered !== "\n" ) ? $filtered : null;
	}
}
