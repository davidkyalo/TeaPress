<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;

class Lang extends Facade {

	protected static function getFacadeAccessor(){
		return 'lang';
	}
}