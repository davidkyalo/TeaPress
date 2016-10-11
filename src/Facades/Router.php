<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
use TeaPress\Http\Router as Factory;

class Router extends Facade {

	const DONT_PREFIX_ROUTE = Factory::DONT_PREFIX_ROUTE;

	protected static function getFacadeAccessor(){
		return 'classic_router';
	}
}