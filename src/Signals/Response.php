<?php
namespace TeaPress\Signals;

use TeaPress\Utils\Bag;

//courier.

class Response extends Bag
{
	const HALTED_EMPTY = 1;
	const HALTED_WITH_VALUE = 2;

	/**
	 * @var int
	 */
	protected $halted = 0;

	/**
	 * @var mixed
	 */
	protected $haltedWith;

	/**
	 * Halt the response stack with
	 *
	 * @param  mixed  $with
	 * @return $this
	 */
	public function halt($with = NOTHING, $force = false)
	{
		if($this->halted && !$force)
			return $this;

		if($with !== NOTHING){
			$this->halted = self::HALTED_WITH_VALUE;
			$this->append($with);
		}
		else{
			$this->halted = self::HALTED_EMPTY;
		}

		return $this;
	}

	/**
	 * Determine if the response was halted.
	 *
	 * @return int
	 */
	public function halted()
	{
		return $this->halted;
	}

	/**
	 * Get the given response value.
	 * If key is not given, returns the last item or all items
	 * depending on whether the response was halted.
	 *
	 * @param  null|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key = null, $default = null)
	{
		if(is_null($key)){
			switch ($this->halted) {
				case self::HALTED_WITH_VALUE:
					return $this->last($default);

				case self::HALTED_EMPTY:
				default:
					return $this->all();
			}
		}

		return parent::get($key, $default);
	}

	/**
	 * Merge the items with a new set.
	 *
	 * @param  array  $items
	 * @param  bool   $recursive
	 * @return $this
	 */
	public function merge($items, $recursive = false)
	{
		if($items instanceof self)
			$this->halted = $items->halted();

		return parent::merge($items, $recursive);
	}


}