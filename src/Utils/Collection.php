<?php
namespace TeaPress\Utils;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection {


	public function one($key, $value, $default = null, $strict = true){
		return $this->first( function($index, $item) use ($key, $value, $strict)
		{
			return $strict ? data_get($item, $key) === $value
					: data_get($item, $key) == $value;
		}, $default);
	}

	public function update($items){
		$this->items = array_merge($this->items, $this->getArrayableItems($items));
		return $this;
	}
}