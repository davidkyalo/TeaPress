<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
/**
*
*/
class Cookies extends Facade {

	protected static function getFacadeAccessor(){
		return 'cookie_jar';
	}
}