<?php

namespace TeaPress\Database\ORM;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent {

	use ModelTrait;

	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';

}
