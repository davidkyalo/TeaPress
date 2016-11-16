<?php
namespace TeaPress\Contracts\Signals;

interface Online {

	/**
	* Get the hooks hub instance
	*
	* @return \TeaPress\Contracts\Signals\Signals
	*/
	public static function getSignals();

	/**
	* Set the hooks hub instance
	*
	* @param  \TeaPress\Contracts\Signals\Signals	$signals
	*
	* @return void
	*/
	public static function setSignals(Signals $signals);

}