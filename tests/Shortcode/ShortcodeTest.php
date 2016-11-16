<?php
namespace TeaPress\Tests\Shortcode;

use TeaPress\Utils\Arr;
use TeaPress\Tests\Base\TestCase;
use TeaPress\Shortcode\Shortcode;
use TeaPress\Contracts\Shortcode\Registrar;

class ShortcodeTest extends TestCase
{
	protected $registrar;


	public function testRegistered()
	{
		$registrar = $this->registrar;
		$tag = 'a_test_registered_shortcode';
		$shortcode = $registrar->add($tag);

		$this->assertTrue($registrar->has($tag));

	}

	public function testRemove()
	{
		$registrar = $this->registrar;
		$tag = 'test_removing_shortcode';
		$shortcode = $registrar->add($tag);

		$registrar->remove($tag);

		$this->assertFalse($registrar->has($tag));

	}

	public function testCreatesInstance()
	{
		$registrar = $this->registrar;
		$tag = 'test_creates_instance_shortcode';

		$shortcode = $registrar->add($tag);

		$this->assertInstanceOf(Shortcode::class, $shortcode);

	}

	public function testSetsHandler()
	{
		$registrar = $this->registrar;
		$tag = 'test_sets_handler_shortcode';

		$handler = function(){};
		$shortcode = $registrar->add($tag, $handler);

		$this->assertSame($handler, $shortcode->getHandler());
	}

	public function testInvoke()
	{
		$registrar = $this->registrar;
		$tag = 'test_invokes_shortcode';

		$handler = function(){
			return 'invoked';
		};

		$shortcode = $registrar->add($tag, $handler);
		$content = $registrar->compile("[{$tag}]");
		$this->assertEquals('invoked', $content);
	}

	public function testInvokeWithAttributes()
	{
		$registrar = $this->registrar;
		$tag = 'test_attributes';

		$handler = function($id, $name){
			return "{$id},{$name}";
		};

		$shortcode = $registrar->add($tag)
					->handler($handler)
					->attribute('id', 1);

		$content = $registrar->compile("[{$tag} id=25 name='foo']");

		$this->assertEquals("25,foo", $content);
	}

	public function testInvokeWithOptionalAttributes()
	{
		$registrar = $this->registrar;
		$tag = 'test_attributes';

		$handler = function($id, $name, $foo, $content){
			return "{$id},{$name},{$foo},{$content}";
		};

		$shortcode = $registrar->add($tag)
					->handler($handler)
					->attributes(['foo' => 'foo', 'name' => 'none' ]);

		$content = $registrar->compile("[{$tag} name='Name' id=25]The Content[/{$tag}]");
		$this->assertEquals("25,Name,foo,The Content", $content);
	}

	public function testResolvesHandlerParameters()
	{
		$registrar = $this->registrar;
		$tag = 'test_resolve';

		$handler = function(Registrar $shortcodes, $id, $name, $foo=null)
		{
			$this->assertInstanceOf(Registrar::class, $shortcodes);
		};

		$shortcode = $registrar->add($tag)
					->handler($handler);

		$content = $registrar->compile("[{$tag}]");

	}

	public function testInvokeController()
	{
		$registrar = $this->registrar;
		$tag = 'test_controller';

		$handler = 'TeaPress\Tests\Shortcode\Mocks\Controller@param';

		$shortcode = $registrar->add($tag)
					->handler($handler)
					->attributes(['foo' => 'foo']);

		$content = $registrar->compile("[{$tag} id=25]");
		$this->assertEquals("25,param,foo", $content);
	}

	public function testInvokeControllerChangeAction()
	{
		$registrar = $this->registrar;
		$tag = 'test_changing_controller';

		$handler = 'TeaPress\Tests\Shortcode\Mocks\Controller@change';

		$shortcode = $registrar->add($tag)
					->handler($handler);

		$content = $registrar->compile("[{$tag}]");
		$this->assertEquals("response changed", $content);
	}


	public function testInvokeControllerMissingAction()
	{
		$registrar = $this->registrar;
		$tag = 'test_missing_controller';

		$handler = 'TeaPress\Tests\Shortcode\Mocks\Controller@foo';

		$shortcode = $registrar->add($tag)
					->handler($handler);

		$content = $registrar->compile("[{$tag}]");
		$this->assertEquals("foo missing", $content);
	}



	protected function setUp()
	{
		$this->registrar = $this->container('shortcode');
	}
}
