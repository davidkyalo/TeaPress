<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class View extends Facade {

	protected static function getFacadeAccessor(){
		return 'view';
	}
}
