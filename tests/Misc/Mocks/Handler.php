<?php
namespace TeaPress\Tests\Misc\Mocks;

use Closure;
use TeaPress\Contracts\Core\Container;

class Handler
{
	protected $app;
	protected $callback;

	public function __construct(Container $app, Closure $callback = null)
	{
		$this->app = $app;
		$this->callback = function($params) use($callback){
			return call_user_func_array( $callback, $params);
		};
	}

	public function increment($value, $step = 1)
	{
		return $value + $step;
	}

	public function multiply($value, $multiple = 1)
	{
		return $value * $multiple;
	}

	protected function callCallback($params)
	{
		$callback = $this->callback;
		return $callback($params);
	}

	public function handle()
	{
		return $this->callCallback(func_get_args());
	}

	public function __invoke()
	{
		return call_user_func_array([$this, 'handle'], func_get_args());
	}
}
