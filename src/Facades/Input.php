<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
/**
*
*/
class Input extends Facade {

	protected static function getFacadeAccessor(){
		return 'input';
	}
}