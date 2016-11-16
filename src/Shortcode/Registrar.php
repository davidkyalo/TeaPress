<?php
namespace TeaPress\Shortcode;

use Exception;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Shortcode\Registrar as Contract;

class Registrar implements Contract
{

	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * Create the registrar instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Container $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Add a new shortcode.
	 *
	 * @param  string   $tag
	 * @param  mixed    $handler
	 * @return \TeaPress\Shortcode\Shortcode
	 */
	public function add($tag, $handler = null)
	{
		add_shortcode($tag, $shortcode = $this->createShortcode($tag, $handler));

		return $shortcode;
	}

	/**
	 * Compiles all registered shortcodes in the provided content.
	 *
	 * @param  string  $tag
	 * @param  bool    $ignoreHtml
	 * @return string
	 */
	public function compile($content, $ignoreHtml = false)
	{
		return do_shortcode($content, $ignoreHtml);
	}

	/**
	 * Check if the given shortcode is registered.
	 *
	 * @param  string  $tag
	 * @return bool
	 */
	public function has($tag)
	{
		return shortcode_exists($tag);
	}

	/**
	 * Checks whether the content passed contains a specific short code,
	 *
	 * @param  string  $content
	 * @param  string  $tag
	 * @return bool
	 */
	public function in($content, $tag)
	{
		return has_shortcode($content, $tag);
	}

	/**
	 * Remove a shortcode.
	 *
	 * @param  string   $tag
	 * @return void
	 */
	public function remove($tag)
	{
		return remove_shortcode($tag);
	}

	/**
	 * Create a new shortcode.
	 *
	 * @param  string   $tag
	 * @param  mixed    $handler
	 * @return \TeaPress\Shortcode\Shortcode
	 */
	protected function createShortcode($tag, $handler = null)
	{
		$shortcode = (new Shortcode($tag))->setContainer($this->container);

		return !is_null($handler) ? $shortcode->handler($handler) : $shortcode;
	}
}
