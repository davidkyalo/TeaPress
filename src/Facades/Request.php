<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
/**
*
*/
class Request extends Facade {

	protected static function getFacadeAccessor(){
		return 'request';
	}
}