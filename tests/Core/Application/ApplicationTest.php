<?php
namespace TeaPress\Tests\Core\Application;


class ApplicationTest extends TestCase
{
	protected $serviceName='app.shared';


	public function testSharedAppIsSingleton()
	{
		$this->assertSame( $this->sharedApp(), $this->sharedApp());
	}

	public function testSharedAppIsNotNewApp()
	{
		$this->assertNotSame( $this->sharedApp(), $this->newApp());
	}

	public function testNewAppAlwaysNew()
	{
		$this->assertNotSame( $this->newApp(), $this->newApp());
	}


}