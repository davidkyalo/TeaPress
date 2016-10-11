<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class URL extends Facade {

	protected static function getFacadeAccessor(){
		return 'url';
	}
}