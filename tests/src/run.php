<?php

use TeaPress\Tests\Container;


$GLOBALS['executions'] = $GLOBALS['wp_actions'];

add_action('all', function($tag){
	global $executions, $wp_actions;

	if(isset($wp_actions[$tag])){
		$executions[$tag] = $wp_actions[$tag];
	}
	else
	{
		if(!isset($executions[$tag]))
			$executions[$tag] = 1;
		else
			++$executions[$tag];
	}

});



if(!function_exists('dump')){
	function dump()
	{
		static $started=false;

		if(!$started){
			echo "\n";
			$started = true;
		}

		foreach (func_get_args() as $arg) {
			if( is_string($arg))
				echo $arg;
			else
				var_dump($arg);
			echo "\n";
		}
	}
}

if(!function_exists('pprint')){
	function pprint($k, $v = NOTHING, $l=5)
	{
		static $started=false;

		if(!$started){
			echo "\n";
			$started = true;
		}

		$pad = ($l - strlen($k)) > 1 ? str_repeat(' ', ($l - strlen($k))) : '';
		echo "{$k}{$pad} ";
		if($v !== NOTHING){
			echo ": ";
			var_export($v);
			// var_dump($v);
		}
		echo "\n";
	}
}

echo "\n";

$app = new Container( dirname(__DIR__) );

function testapp($service = null, $parameters = null)
{
	$app = Container::getInstance();
	return is_null($service) ? $app : $app->make($service, (array) $parameters);
}

$app->boot();
