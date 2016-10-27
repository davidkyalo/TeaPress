<?php
namespace TeaPress\Tests\Core\Mocks\Bootstrap;

use TeaPress\Core\Bootstrap\Factory as BaseFactory;

class Factory extends BaseFactory
{

	/**
	 * @var string
	 */
	protected $appClass = 'TeaPress\Tests\Core\Mocks\Application';

	protected function initialize()
	{
		$this->app->setBasePath( dirname( dirname(__DIR__) ) );

	}
}