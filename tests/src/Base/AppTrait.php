<?php

namespace TeaPress\Tests\Base;

use TeaPress\Tests\Container;

trait AppTrait
{

	public function app($service = null, $parameters = null)
	{
		return $this->container($service, $parameters);
	}

	public function container($service = null, $parameters = null)
	{
		$app = Container::getInstance();
		return is_null($service) ? $app : $app->make($service, (array) $parameters);
	}
}