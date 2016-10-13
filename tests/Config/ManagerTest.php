<?php
namespace TeaPress\Tests\Config;

use TeaPress\Utils\Arr;
use TeaPress\Config\Manager;
use TeaPress\Config\Repository;
use TeaPress\Tests\Base\ServiceTestCase;
use TeaPress\Contracts\Config\Manager as ManagerContract;
use TeaPress\Contracts\Config\Repository as RepositoryContract;


class ManagerTest extends ServiceTestCase
{
	protected $serviceName = 'config';
	protected $serviceClass = Manager::class;

	protected function fullyLoaded()
	{

	}

	public function testRegisteredInIocContainer()
	{
		$this->runRegisteredTest();
	}

	public function testServiceAliases()
	{
		$this->runServiceAliasesTest();
	}

	public function testAddPathFolder()
	{
		$this->config->addPath(__DIR__.'/data/later');
		$this->assertTrue( $this->config->get('late.loaded') );
	}

	public function testAddNamedFolderPath()
	{
		$this->config->addPath(__DIR__.'/data/later', 'later');
		$this->assertTrue($this->config->get('later.late.loaded') );
	}

	public function testAddPathFile()
	{
		$this->config->addPath(__DIR__.'/data/defer.php');
		$this->assertTrue( $this->config->get('deferred') );
	}

	public function testAddNamedFilePath()
	{
		$this->config->addPath(__DIR__.'/data/defer.php', 'defer');
		$this->assertTrue( $this->config->get('defer.deferred') );
	}

	public function testAddPathToNamespace()
	{
		$this->config->addPath(__DIR__.'/data/later', 'deferred', 'auth');
		$this->assertTrue($this->config->get('auth::deferred.late.loaded') );
	}

	public function testGetDefaultRepository()
	{
		$this->assertEquals('*', $this->config->getRepository()->getNamespace() );
	}

	public function testGetUnkownRepository()
	{
		$this->assertNull( $this->config->getRepository('xxxxxx') );
	}

	public function testGetOrCreateRepository()
	{
		$this->assertEquals('aliens', $this->config->getOrCreateRepository('aliens')->getNamespace() );
	}

	public function testAddNamespace()
	{
		$this->config->addNamespace('auth', __DIR__.'/data/auth');
		$this->assertTrue( $this->config->hasNamespace('auth') );
	}

	public function testHas()
	{
		$this->assertTrue($this->config->has('nested.gmail'));
	}

	public function testHasNot()
	{
		$this->assertFalse($this->config->has('xxxxxx::key'));
	}

	public function testHasNamespaced()
	{
		$this->assertTrue($this->config->has('auth::session.cookie'));
	}

	public function testGet()
	{
		$this->assertTrue($this->config->get('app.loaded'));
	}

	public function testFilters()
	{
		$expected = 'filtered';
		$original = 'original';
		$this->config->filter('app.filtered', function($value, $config) use($original, $expected){
			pprint('Filtering', current_filter());
			pprint('Filtering', current_filter());
			return $value === $original ? $expected : $value;
		});

		$result = $this->config->get('app.filtered');

		$this->assertEquals($expected, $result);
	}

	public function testGetFromNamespace()
	{
		$this->assertTrue($this->config->get('auth::session.loaded'));
	}


	public function testGetMissing()
	{
		$this->assertNull($this->config->get('xxxxxx::key', null));
	}


	public function testSet()
	{
		$case = $this->getName().'-default';
		$this->config->set('set_test.current', $case);
		$this->assertEquals(['current' => $case ], $this->config->get('set_test'));
	}

	public function testSetInNonRegisteredRepository()
	{
		$case = $this->getName().'-foo';
		$this->config->set('foo::set_test.current', $case);
		$this->assertEquals(['current' => $case ], $this->config->get('foo::set_test'));
	}

	public function testPrepend()
	{
		$expected = $this->config->get('app.list');
		$this->config->prepend('app.list', 'item 0');
		array_unshift($expected, 'item 0');
		$this->assertEquals($expected, $this->config->get('app.list'));
	}

	public function testPush()
	{
		$expected = $this->config->get('app.list');
		$this->config->push('app.list', 'pushed item');
		$expected[] = 'pushed item';
		$this->assertEquals($expected, $this->config->get('app.list'));
	}

	public function testMerge()
	{
		$this->config->merge( $this->config->getRepository(), 'foo');

		$this->assertTrue($this->config->get('foo::app.loaded'));
	}

	public function testGetFilteredAfterMerge()
	{
		$this->assertEquals( 'filtered', $this->config->get('foo::app.filtered'));
	}
}