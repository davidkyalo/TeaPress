<?php
namespace TeaPress\Contracts\Utils;

interface Actionable
{
	/**
	 * Executed before the requested action is called.
	 *
	 * @param  mixed 	$request
	 * @param  string 	$action
	 * @param  array 	$parameters
	 * @return array
	 */
	public function beforeAction($request, $action, $parameters = []);

	/**
	 * Executed after the requested action is called.
	 *
	 * @param  mixed 	$response
	 * @param  mixed 	$request
	 * @param  string 	$action
	 * @param  array 	$parameters
	 * @return mixed
	 */
	public function afterAction($response, $request, $action, $parameters = []);

	/**
	 * Executed if the requested action is missing.
	 *
	 * @param  mixed 		$request
	 * @param  string 		$action
	 * @param  array 		$parameters
	 * @return array|null
	 */
	public function missingAction($request, $action, $parameters = []);

}