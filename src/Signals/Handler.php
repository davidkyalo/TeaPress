<?php
namespace TeaPress\Signals;


class Callback
{

	public $id;

	public $hub;

	public $abstract;

	public function __construct($id, $abstract, Hub $hub)
	{
		$this->id = $id;
		$this->hub = $hub;
		$this->abstract = $abstract;
	}

	public function __invoke()
	{

	}



}