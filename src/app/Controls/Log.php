<?php
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
class Log extends Controls {
	protected $config;
	protected $fs;
	protected $view;

	public function __construct( Configuration $config, Filesystem $fs, ViewRender $view ) {
		$this->config = $config;
		$this->fs     = $fs;
		$this->view   = $view;
	}

	public function display( $id = 0, $hideInfo = false ) {
		$logContents = 'No log file exists.';
		if ( $id == 0 ) {
			$content = "{$this->config->directories->rootpath}/error.log";
			if ( $this->fs->exists( $content ) ) {
				if ( $hideInfo ) {
					$logContents = $this->excludeInfo( file_get_contents( $content ) );
				} else {
					$logContents = file_get_contents( $content );
				}
			}
		} else {
			$content =  "{$this->config->directories->rootpath}/$id/wp-content/debug.log";
			if ( $this->fs->exists( $content ) ) {
				$logContents = file_get_contents( $content );
			}
		}

		echo $this->view->render(
			'log',
			[
				'page_title'   => ( $id == 0 ) ? 'System Log' : "Site {$id} Log",
				'log_header'   => ( $id == 0 ) ? 'WPDS Error Log' : "WPDS Error Log for site {$id}",
				'log_contents' => $logContents,
				'return_url'   => $this->config->general->domain,
				'show_exluder' => ( $id == 0 ) ? true : false
			]
		);
	}

	private function excludeInfo( $blob ) {
		$document = explode( "\n", $blob );
		$filtered = '';

		foreach ( $document as $line ) {
			if ( ! strpos( $line, '.INFO' ) ) {
				$filtered .= $line . "\n";
			}
		}

		return $filtered;
	}
}
