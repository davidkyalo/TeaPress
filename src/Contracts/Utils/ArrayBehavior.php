<?php
namespace TeaPress\Contracts\Utils;

use Countable;
use ArrayAccess;
use IteratorAggregate;

interface ArrayBehavior extends ArrayAccess, Countable, IteratorAggregate {

}