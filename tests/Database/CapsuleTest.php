<?php
namespace TeaPress\Tests\Database;

use TeaPress\Database\Capsule;
use TeaPress\Tests\Base\ServiceTestCase;

/**
*
*/
class CapsuleTest extends ServiceTestCase
{

	/**
	* @var \TeaPress\Database\Capsule
	*/
	protected $capsule;
	protected $serviceName = 'db.manager';
	protected $servicePropertyName = 'capsule';
	protected $serviceClass = Capsule::class;

	public function testRegisteredInIocContainer()
	{
		$this->runRegisteredTest();
	}

	public function testServiceAliases()
	{
		$this->runServiceAliasesTest();
	}


	public function testHasTable()
	{
		$this->assertTrue( $this->capsule->schema()->hasTable('posts') );
	}



}