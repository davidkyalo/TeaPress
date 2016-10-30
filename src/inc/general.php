<?php
// namespace Tea;


/**
 * Wrap the given callable such that its dependencies will be injected when executed.
 *
 * @param  \Closure|string|array  $callable
 * @param  array  $parameters
 * @return \Closure
 */
// function injected($callable, array $parameters = []){
// 	return function () use ($callable, $parameters, $di) {
// 		return teapress()->call($callable, $parameters);
// 	};
// }

if( !function_exists('app') ):
	function app($key = null, $parameters = []){
		return teapress($key, $parameters);
	}
endif;


// add_action('load_app_service_container', function(){
// 	require_once __DIR__ . '/Arch/pluggable_app_loader.php';
// 	app();
// }, 9999);


if(!function_exists('add_form')):
function add_form( $id, $handler, $methods = null, $args = null ){
	return app('forms')->add( $id, $handler, $methods, $args );
}
endif;

if(!function_exists('get_form')):
function get_form( $name ){
	return app('forms')->get( $name );
}
endif;