<?php
namespace TeaPress\Contracts\General;

use ArrayAccess;

interface Actionable
{
	/**
	 * Executed General before the requested action is called.
	 * This method
	 * @param  string 			$action
	 * @param  \ArrayAccess 	$parameters
	 * @return null|string
	 */
	public function beforeAction($action, ArrayAccess $parameters);

	/**
	 * Executed after the requested action is called.
	 *
	 * @param  mixed 			$response
	 * @param  string 			$action
	 * @param  \ArrayAccess 	$parameters
	 * @return mixed
	 */
	public function afterAction($response, $action, ArrayAccess $parameters);

	/**
	 * Executed if the requested action is missing.
	 *
	 * @param  string 			$action
	 * @param  \ArrayAccess 	$parameters
	 * @return mixed
	 */
	public function missingAction($action, ArrayAccess $parameters);
}