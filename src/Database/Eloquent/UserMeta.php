<?php

namespace TeaPress\Database\Models;


class UserMeta extends Model
{
	protected $primaryKey = 'meta_id';



	public function getTable()
	{
		return $this->getConnection()->db->prefix . 'usermeta';
	}
}