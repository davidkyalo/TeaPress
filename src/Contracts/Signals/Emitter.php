<?php
namespace TeaPress\Contracts\Signals;

interface Emitter {

	/**
	* Get the hooks hub instance
	*
	* @return \TeaPress\Contracts\Signals\Hub
	*/
	public static function getSignalsHub();

	/**
	* Set the hooks hub instance
	*
	* @param  \TeaPress\Contracts\Signals\Hub		$hub
	*
	* @return void
	*/
	public static function setSignalsHub(Hub $hub);


	/**
	* Get this emitter's events namespace.
	*
	* @return string|null
	*/
	public static function getSignalsNamespace();


	/**
	*  Get the event's array name used for binding with the dispatcher.
	*
	* @param  string		$hook
	*
	* @return string|array
	*/
	public static function getHookTag( $hook );

}