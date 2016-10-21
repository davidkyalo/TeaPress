<?php
namespace TeaPress\Contracts\Signals;

interface Online {

	/**
	* Get the hooks hub instance
	*
	* @return \TeaPress\Contracts\Signals\Hub
	*/
	public static function getSignals();

	/**
	* Set the hooks hub instance
	*
	* @param  \TeaPress\Contracts\Signals\Hub		$hub
	*
	* @return void
	*/
	public static function setSignals(Hub $hub);

}