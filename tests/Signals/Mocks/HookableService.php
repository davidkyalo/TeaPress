<?php
namespace TeaPress\Tests\Signals\Mocks;

use TeaPress\Signals\Traits\Hookable as HookableTrait;
use TeaPress\Contracts\Signals\Hookable as Contract;

class HookableService implements Contract
{
	use HookableTrait;

	public function __construct()
	{
		$this->boot();
	}

	public function booting($callback, $priority=null, $args=null)
	{
		$this->bindCallback('booting', $callback, $priority, $args);
	}

	public function boot()
	{
		$this->fireSignal('booting', $this);
	}

	public function start()
	{
		$this->fireSignal('starting', $this);
	}

	public function runFilters()
	{
		return $this->applyFilters('filters', 300, $this);
	}

	public function checkTag()
	{
		return $this->applyFilters('check_tag', null, $this);
	}
}
