<?php

namespace TeaPress\Database\Models;

trait ModelTrait{

	use AliasesAttributes;

	//protected $dont_prefix_table = false;
	// protected $__full_table_name;

	// public function getTable() {
	// 	if(is_null( $this->__full_table_name )){
	// 		$table = isset( $this->table ) ? $this->table
	// 			: str_replace( '\\', '', snake_case( str_plural( class_basename( $this ) ) ) );

	// 		$dont_prefix = data_get( $this, 'dont_prefix_table', false );
	// 		$prefix = $this->getConnection()->db->prefix;
	// 		$this->__full_table_name = $dont_prefix || starts_with( $table, $prefix )
	// 							? $table : $prefix . $table;
	// 	}

	// 	return $this->__full_table_name;
	// }

	public function checkIfattributeDefined($key, $checkgetters = true, $checkmethods = false, $checkprops = false, $checksetters = false){
		if( $this->getColumnName($key) != $key || array_key_exists( $key, $this->attributes)
			||  array_key_exists($key, $this->relations))
			return true;

		if(($checkgetters && $this->hasGetMutator( $key )) || ($checkprops  && property_exists( $this, $key ))
			|| ($checkmethods && method_exists( $this, $key )) || ($checksetters && $this->hasSetMutator($key)))
			return true;

		return false;
	}

}
