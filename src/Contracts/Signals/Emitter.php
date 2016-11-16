<?php
namespace TeaPress\Contracts\Signals;

interface Emitter extends Online {

	/**
	* Get this emitter's events namespace.
	*
	* @return string|null
	*/
	public static function getSignalsNamespace();


	/**
	*  Get the event's array name used for binding with the dispatcher.
	*
	* @param  string $event
	*
	* @return string
	*/
	public static function getSignalTag( $event );

}