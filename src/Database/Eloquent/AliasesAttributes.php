<?php

namespace TeaPress\Database\Models;

trait AliasesAttributes {

	public static function getColumnName($attribute){
		return array_get( static::getAttributeAliases(), $attribute, $attribute );
	}

	public static function getAttributeAliases() {
		return static::$attribute_alliases;
	}

	public function getAttribute($key){
		if(!$this->hasGetMutator($key)){
			$key = static::getColumnName($key);
		}
		return parent::getAttribute( $key );
	}

	public function setAttribute($key, $value){
		if(!$this->hasSetMutator($key)){
			$key = $this->getColumnName($key);
		}
		return parent::setAttribute( $key, $value );
	}
}