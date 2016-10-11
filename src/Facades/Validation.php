<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class Validation extends Facade {

	protected static function getFacadeAccessor(){
		return 'validation';
	}
}