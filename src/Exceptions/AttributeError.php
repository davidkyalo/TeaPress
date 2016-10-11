<?php
namespace TeaPress\Exceptions;

use OutOfBoundsException;

class AttributeError extends OutOfBoundsException {

	public static function notFound($key, $object){
		$object_name = is_object($object) ? get_class($object) : $object;
		return new static( "Attribute {$key} does not exist in {$object_name}." );
	}

	public static function illegal($key, $object){
		$object_name = is_object($object) ? get_class($object) : $object;
		return new static( "Illegal attribute '{$key}' provided in '{$object_name}'." );
	}

	public static function readOnly($key, $object){
		$object_name = is_object($object) ? get_class($object) : $object;
		return new static( "Can't set read only attribute '{$key}' in '{$object_name}'." );
	}

	public static function accessDenied($key, $object){
		$object_name = is_object($object) ? get_class($object) : $object;
		return new static( "Can't access protected attribute '{$key}' in '{$object_name}'." );
	}

}