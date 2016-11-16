<?php
namespace TeaPress\Tests\Shortcode\Mocks;

use ArrayAccess;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\General\Actionable;


class Controller implements Actionable
{

	public function changed(Container $container)
	{
		return 'changed';
	}

	public function param($id, $param, $foo)
	{
		return "{$id},{$param},{$foo}";
	}

	/**
	 * Executed General before the requested action is called.
	 * This method
	 * @param  string 			$action
	 * @param  \ArrayAccess 	$parameters
	 * @return null|string
	 */
	public function beforeAction($action, ArrayAccess $parameters)
	{
		$parameters['param'] = 'param';
		if($action == 'change')
			return 'changed';
	}

	/**
	 * Executed after the requested action is called.
	 *
	 * @param  mixed 			$response
	 * @param  string 			$action
	 * @param  \ArrayAccess 	$parameters
	 * @return mixed
	 */
	public function afterAction($response, $action, ArrayAccess $parameters)
	{
		return ($action == 'changed') ? 'response changed' : $response;
	}

	/**
	 * Executed if the requested action is missing.
	 *
	 * @param  string 			$action
	 * @param  \ArrayAccess 	$parameters
	 * @return mixed
	 */
	public function missingAction($action, ArrayAccess $parameters)
	{
		return "{$action} missing";
	}
}