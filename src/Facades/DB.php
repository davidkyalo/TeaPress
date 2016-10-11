<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class DB extends Facade {

	protected static function getFacadeAccessor(){
		return 'DB';
	}
}