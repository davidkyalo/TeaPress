<?php

use TeaPress\Tests\Container;

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
	function pprint($k, $v = NOTHING, $l=15)
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
			var_dump($v);
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
