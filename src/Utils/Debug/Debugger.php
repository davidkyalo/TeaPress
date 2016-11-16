<?php
namespace TeaPress\Utils\Debug;

use Symfony\Component\Debug\Debug;

class Debugger
{
	protected static $instance;

	protected $debug;
	protected $display_errors;
	protected $level;

	protected $started = false;

	public function __construct($debug = false, $display_errors = false, $level = E_ALL){
		$this->debug = $debug;
		$this->display_errors = $display_errors;
		$this->level = $level;
	}

	public function start()
	{
		if($this->started)
			return true;

		if(!$this->debug)
			return $this->started = true;

		Debug::enable( $this->level , $this->display_errors);
		return $this->started = true;
	}

	public static getInstance($debug = null, $display_errors = false, $level = E_ALL)
	{

	}
}