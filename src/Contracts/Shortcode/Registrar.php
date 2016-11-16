<?php
namespace TeaPress\Contracts\Shortcode;

interface Registrar
{

	/**
	 * Add a new shortcode.
	 *
	 * @param  string   $name
	 * @param  mixed    $handler
	 * @return void
	 */
	public function add($name, $handler = null);

}