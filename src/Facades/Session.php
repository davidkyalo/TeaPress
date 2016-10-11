<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
use TeaPress\Http\SessionManager;

/**
*
*/
class Session extends Facade {

	protected static function getFacadeAccessor(){
		return 'session';
	}
}