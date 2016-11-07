<?php
namespace TeaPress\Shortcode;

use Exception;
use TeaPress\Contracts\Core\Container;

class Shortcode
{

	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * Create the factory instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Container $container
	 * @return void
	 */
	public function __construct(Application $container)
	{
		$this->container = $container;
	}

	/**
	 * Add a new shortcode.
	 *
	 * @param  string   $name
	 * @param  mixed    $handler
	 * @return \TeaPress\Shortcode\Shortcode
	 */
	public function add($name, $handler = null)
	{
		add_shortcode($name, $shortcode = $this->createShortcode($name, $handler));

		return $shortcode;
	}

	/**
	 * Create a new shortcode.
	 *
	 * @param  string   $name
	 * @param  mixed    $handler
	 * @return \TeaPress\Shortcode\Shortcode
	 */
	protected function createShortcode($name, $handler = null)
	{
		$shortcode = new Shortcode($name);
		$shortcode->setContainer($this->container);

		if($handler){
			$shortcode->handler($handler);
		}

		return $shortcode;
	}
}
