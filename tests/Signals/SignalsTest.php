<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Signals\Signals;
use TeaPress\Tests\Base\ServiceTestCase;

use TeaPress\Tests\Signals\Mocks\Handler;

class SignalsTest extends ServiceTestCase
{

	/**
	* @var \TeaPress\Signals\Signals
	*/
	protected $signals;

	protected $serviceName = 'signals';

	protected $serviceClass = Signals::class;

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

	public function testTagInstance()
	{
		$tag = ['some_namespace', 'the_event'];
		$this->assertInstanceOf( 'TeaPress\Signals\Tag', $this->signals->tag($tag[1], $tag[0]) );
	}

	public function testGetTagWithTagInstance()
	{
		$tag = ['some_namespace', 'the_event'];
		$itag = $this->signals->tag($tag[1], $tag[0]);

		$this->assertEquals( join(':', $tag), $this->signals->getTag($itag) );
	}

	public function testGetTagRegisteredServiceName()
	{
		$tag = ['signals', 'event_name'];
		$this->assertEquals( join(':', $tag), $this->signals->getTag($tag[1], $tag[0]) );
	}

	public function testGetTagRegisteredServiceClassName()
	{
		$tag = [ Signals::class, 'event_name'];
		$this->assertEquals( 'signals:event_name', $this->signals->getTag($tag[1], $tag[0]) );
	}

	public function testGetTagRegisteredServiceInstance()
	{
		$tag = [ $this->signals, 'event_name'];
		$this->assertEquals( 'signals:event_name', $this->signals->getTag($tag[1], $tag[0]) );
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

		$this->assertEquals(6, $this->signals->filter( $tag, 3, ['multiply', 2]) );
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

	public function testUnbindClosure()
	{
		$tag = $this->methodTag(__METHOD__);
		$callback = function(){
			return 'Called';
		};

		$this->signals->bind($tag, $callback, 20);

		$has = $this->signals->has($tag, $callback);

		$this->signals->unbind($tag, $callback, 20);

		$removed = !$this->signals->has($tag, $callback);

		$this->assertTrue( ( $has && $removed ) );
	}

	public function testUnbindClassMethodString()
	{
		$tag = $this->methodTag(__METHOD__);

		$callback = 'Foo\SomeClass@handle';

		$this->signals->bind($tag, $callback, 20);

		$has = $this->signals->has($tag, $callback);

		$this->signals->unbind($tag, $callback, 20);

		$removed = !$this->signals->has($tag, $callback);

		$this->assertTrue( ( $has && $removed ) );
	}

	public function testUnbindStaticClassMethodArray()
	{
		$tag = $this->methodTag(__METHOD__);

		$callback = ['Foo\SomeClass','handle'];

		$this->signals->bind($tag, $callback, 20);

		$has = $this->signals->has($tag, $callback);

		$this->signals->unbind($tag, $callback, 20);

		$removed = !$this->signals->has($tag, $callback);

		$this->assertTrue( ( $has && $removed ) );
	}


	public function testUnbindClassMethodArray()
	{
		$tag = $this->methodTag(__METHOD__);

		$callback = [ $this,'handle'];

		$this->signals->bind($tag, $callback, 20);

		$has = $this->signals->has($tag, $callback);

		$this->signals->unbind($tag, $callback, 20);

		$removed = !$this->signals->has($tag, $callback);

		$this->assertTrue( ( $has && $removed ) );
	}

}