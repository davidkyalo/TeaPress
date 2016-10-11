<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class Config extends Facade {
	protected static function getFacadeAccessor(){
		return 'config';
	}
}