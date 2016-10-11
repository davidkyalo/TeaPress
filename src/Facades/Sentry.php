<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;


/**
*
*/
class Sentry extends Facade {
	protected static function getFacadeAccessor(){
		return 'auth';
	}
}