<?php

namespace TeaPress\Tests\Base;

use TeaPress\Tests\Container;

trait AppTrait
{
	protected static $_app_depreciated_noticed = false;

	public function app($service = null, $parameters = null)
	{
		if(!static::$_app_depreciated_noticed){
			trigger_error("NOTICE: Method ".__METHOD__." is depreciated. Use container() instead.");
			static::$_app_depreciated_noticed = true;
		}
		return $this->container($service, $parameters);
	}

	public function container($service = null, $parameters = null)
	{
		$app = Container::getInstance();
		return is_null($service) ? $app : $app->make($service, (array) $parameters);
	}
}