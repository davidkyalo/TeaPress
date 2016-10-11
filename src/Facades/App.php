<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
/**
*
*/
class App extends Facade {

	protected static function getFacadeAccessor(){
		return 'app';
	}
}