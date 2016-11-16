<?php
namespace TeaPress\Contracts\Utils;

use Countable;
use ArrayAccess;

interface ArrayBehavior extends ArrayAccess, Countable
{

	/**
	 * Get an array of all the offsets (keys)
	 *
	 * @return array
	 */
	public function offsets();
}