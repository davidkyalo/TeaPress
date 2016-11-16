<?php
namespace TeaPress\Tests\Signals\Mocks;

use TeaPress\Contracts\Signals\Signals;

class Handler
{
	protected $hub;

	public function __construct(Signals $hub)
	{
		$this->hub = $hub;
	}

	public function increment($value, $step = 1)
	{
		return $value + $step;
	}

	public function multiply($value, $multiple = 1)
	{
		return $value * $multiple;
	}

	public function handle($subject, $method, $other = 1)
	{
		return call_user_func( [$this, $method], $subject, $other );
	}
}
