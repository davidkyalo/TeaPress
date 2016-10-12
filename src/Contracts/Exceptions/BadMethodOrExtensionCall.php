<?php
namespace TeaPress\Contracts\Exceptions;

use BadMethodCallException;

class BadMethodOrExtensionCall extends BadMethodCallException {

	public function __construct($method, $object){
		$object_name = is_object($object) ? get_class($object) : $object;
		parent::__construct( "Method or Extension '{$method}' does not exist on class {$object_name}." );
	}

}