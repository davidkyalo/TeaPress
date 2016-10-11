<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
// use TeaPress\Html\Forms as Factory;


/**
*
*/
class Forms extends Facade {
	protected static function getFacadeAccessor(){
		return 'forms';
	}
}