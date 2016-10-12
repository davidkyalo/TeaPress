<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Signals\Hub;
use TeaPress\Tests\Base\ServiceTestCase;

use TeaPress\Tests\Signals\Mocks\Handler;

class HubTest extends ServiceTestCase
{

	/**
	* @var \TeaPress\Signals\Hub
	*/
	protected $signals;

	protected $serviceName = 'signals';

	protected $serviceClass = Hub::class;

	protected function methodTag($method)
	{
		return str_replace('::', ':', $method);
	}

	public function testRegisteredInIocContainer()
	{
		$this->runRegisteredTest();
	}

	public function testServiceAliases()
	{
		$this->runServiceAliasesTest();
	}

	public function testGetTagNormal()
	{
		$this->assertEquals( 'normal', $this->signals->getTag('normal') );
	}

	public function testGetTagNamespacedString()
	{
		$this->assertEquals( 'emitter:normal.event', $this->signals->getTag('emitter:normal.event') );
	}

	public function testGetTagArray()
	{
		$tag = ['some_namespace', 'the_event'];

		$this->assertEquals( join(':', $tag), $this->signals->getTag($tag) );
	}

	public function testGetTagRegisteredServiceName()
	{
		$tag = ['signals', 'event_name'];
		$this->assertEquals( join(':', $tag), $this->signals->getTag($tag) );
	}

	public function testGetTagRegisteredServiceClassName()
	{
		$tag = [ Hub::class, 'event_name'];
		$this->assertEquals( 'signals:event_name', $this->signals->getTag($tag) );
	}

	public function testGetTagRegisteredServiceInstance()
	{
		$tag = [ $this->signals, 'event_name'];
		$this->assertEquals( 'signals:event_name', $this->signals->getTag($tag) );
	}

	public function testAddAction()
	{
		$executed = false;
		$this->signals->addAction('some_action', function() use (&$executed){
			$executed = true;
		});
		do_action('some_action');
		$this->assertTrue($executed);
	}

	public function testAddFilter()
	{
		$expected = microtime(true);
		$this->signals->addFilter('some_filter', function($value) use($expected){
			return 1;
		});

		$this->signals->addFilter('some_filter', function($value) use($expected){
			return 2;
		}, 100);

		$this->signals->addFilter('some_filter', function($value) use($expected){
			return 3;
		});

		$this->assertEquals(2, apply_filters('some_filter', 0));
	}

	public function testDoAction()
	{
		$tag = $this->methodTag(__METHOD__);

		$args = [ 'arg1', 'arg2', 'arg3' ];

		$this->signals->addAction($tag, function() use($args){
			$this->assertEquals($args, func_get_args());
		});

		$this->signals->doAction($tag, $args[0], $args[1], $args[2]);
	}


	public function testEmit()
	{
		$tag = $this->methodTag(__METHOD__);

		$args = [ 'arg1', 'arg2', 'arg3' ];

		$this->signals->addAction($tag, function() use($args){
			$this->assertEquals($args, func_get_args());
		});

		$this->signals->emit($tag, $args[0], $args[1], $args[2]);
	}


	public function testEmitSignalWith()
	{
		$tag = $this->methodTag(__METHOD__);

		$args = [ 'arg1', 'arg2', 'arg3' ];

		$this->signals->addAction($tag, function() use($args){
			$this->assertEquals($args, func_get_args());
		});

		$this->signals->emitSignalWith($tag, $args);
	}

	public function testApplyFilters()
	{
		$tag = $this->methodTag(__METHOD__);


		$this->signals->addFilter($tag, function($value, $adds = 1){
			return $value + $adds;
		});

		$this->signals->addFilter($tag, function($value, $adds = 1) {

			return $value + $adds;
		});

		$this->assertEquals(2, $this->signals->applyFilters( $tag, 0, 1) );
	}

	public function testFilter()
	{
		$tag = $this->methodTag(__METHOD__);


		$this->signals->addFilter($tag, function($value, $adds = 1){
			return $value + $adds;
		});

		$this->signals->addFilter($tag, function($value, $adds = 1) {

			return $value + $adds;
		});

		$this->assertEquals(2, $this->signals->filter( $tag, 0, 1) );
	}

	public function testApplyFiltersWith()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->bind($tag, function($value, $mult){
			return $value * $mult;
		});

		$this->signals->bind($tag, function($value, $mult) {
			return $value * $mult;
		});

		$this->assertEquals(4, $this->signals->applyFiltersWith( $tag, [1,2]) );
	}

	public function testClassBasedCallback()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->bind( $tag, Handler::class.'@increment');
		$response = $this->signals->filter( $tag, 0, 2);
		$this->assertEquals(2, $response );
	}

	public function testClassBasedCallbackDefaultMethod()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->bind( $tag, Handler::class);

		$this->assertEquals(6, $this->signals->filter( $tag, 3, 'multiply', 2) );
	}


	public function testIsDoing()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->bind($tag, function() use($tag){
			return $this->signals->isDoing($tag);
		});

		$this->assertTrue($this->signals->filter($tag));
	}


	public function testCurrent()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->bind($tag, function(){
			return $this->signals->current();
		});

		$this->assertEquals($tag, $this->signals->filter($tag));
	}

	public function testOnce()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->once($tag, function($value){
			return $value+1;
		});

		$value_1 = $this->signals->filter( $tag, 1);
		$value_2 = $this->signals->filter( $tag, $value_1);

		$this->assertEquals($value_1, $value_2);
	}


	public function testIsBound()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->bind($tag, Handler::class);

		$this->assertTrue($this->signals->isBound(Handler::class, $tag));
	}

	public function testUnbind()
	{
		$tag = $this->methodTag(__METHOD__);
		$callback = function(){
			return 'Called';
		};

		$this->signals->bind($tag, $callback, 20);

		$has = $this->signals->has($tag, $callback);

		$this->signals->unbind($tag, $callback, 20);

		$is_bound = $this->signals->isBound($callback);

		$this->assertTrue( ( $has && !$is_bound ) );
	}

}