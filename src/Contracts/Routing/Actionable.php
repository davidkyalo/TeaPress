<?php
namespace TeaPress\Contracts\Routing;

use TeaPress\Contracts\Http\Request;

interface Actionable
{
	/**
	 * Executed before the requested action is called.
	 *
	 * @param  \TeaPress\Contracts\Http\Request $request
	 * @param  string 							$method
	 * @param  array 							$parameters
	 * @return array
	 */
	public function beforeAction($request, $action, $parameters = []);

	/**
	 * Executed after the requested action is called.
	 *
	 * @param  mixed 							$response
	 * @param  \TeaPress\Contracts\Http\Request $request
	 * @param  string 							$method
	 * @param  array 							$parameters
	 * @return mixed
	 */
	public function afterAction($response, $request, $action, $parameters = []);

	/**
	 * Executed if the requested action is missing.
	 *
	 * @param  \TeaPress\Contracts\Http\Request $request
	 * @param  string 							$method
	 * @param  array 							$parameters
	 * @return array|null
	 */
	public function missingAction($request, $action, $parameters = []);

}