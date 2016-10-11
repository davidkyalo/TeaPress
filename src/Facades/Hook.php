<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class Hook extends Facade {

	protected static function getFacadeAccessor(){
		return 'hooks';
	}
}