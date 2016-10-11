<?php
namespace TeaPress\Facades;

use TeaPress\Arch\Facade;
/**
*
*/
class Notice extends Facade {

	protected static function getFacadeAccessor(){
		return 'notices';
	}
}