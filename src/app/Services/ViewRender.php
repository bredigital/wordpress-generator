<?php
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ViewRender {
	protected $loader;
	protected $renderer;
	public function __construct() {
		$this->loader   = new FilesystemLoader( __DIR__ . '/../View' );
		$this->renderer = new Environment( $this->loader );
	}

	/**
	 * Renders the desired view to the page.
	 *
	 * @param string $view     The desired view name.
	 * @param array $variables Variables to pass to the view.
	 * @return void Prints to the page.
	 */
	public function render( $view, $variables ) {
		echo $this->renderer->render(
			"{$view}.html.twig",
			$variables
		);
	}
}
