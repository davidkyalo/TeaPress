<?php
namespace TeaPress\Signals;


class Handler
{

	protected $id;

	protected $hub;

	protected $callback;

	protected $priority;

	public function __construct($callback, $priority, Hub $hub)
	{
		// $this->id = $id;
		$this->hub = $hub;
		$this->callback = $callback;
		$this->priority = $priority;
	}

	public function __invoke()
	{
		return $this->hub->invokeCallback($this->callback, func_get_args(), $this->priority);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getCallback()
	{
		return $this->callback;
	}

	public function getPriority()
	{
		return $this->priority;
	}

}