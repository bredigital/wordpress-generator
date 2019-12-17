<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use TWPG\Services\Com;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ViewRender {
	protected $com;
	protected $loader;
	protected $renderer;
	public function __construct( Com $com ) {
		$this->com      = $com;

		$this->loader   = new FilesystemLoader( __DIR__ . '/../View' );
		$this->renderer = new Environment( $this->loader );
	}

	/**
	 * Renders the desired view to the page.
	 *
	 * @param string  $view      The desired view name.
	 * @param array   $variables Variables to pass to the view.
	 * @param boolean $return    Return the view instead of printing.
	 * @return void Prints to the page.
	 */
	public function render( $view, $variables, $return = false ) {
		$variables['versions'] = [
			'app'   => json_decode( file_get_contents( __DIR__ . '/../../composer.json' ) )->version,
			'php'   => phpversion(),
			'wpcli' => $this->com->wpcli_version(),
		];

		$content = $this->renderer->render(
			"{$view}.html.twig",
			$variables
		);

		if ( ! $return ) {
			echo $content;
		} else {
			return $content;
		}
	}
}
