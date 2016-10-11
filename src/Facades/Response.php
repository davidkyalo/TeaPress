<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
/**
*
*/
class Response extends Facade {

	protected static function getFacadeAccessor(){
		return 'response.factory';
	}
}