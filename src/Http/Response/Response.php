<?php

namespace TeaPress\Http\Response;

use InvalidArgumentException;
use TeaPress\Events\EmitterInterface;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Http\Response as BaseResponse;

class Response extends BaseResponse implements EmitterInterface
{

	use ResponseTrait;

	public function content($content = null){
		if( func_num_args() === 1 ){
			$this->setContent( $content );
		}
		return $this->getContent();
	}


	public function __get($key){
		if( property_exists($this, $key) )
			return $this->{$key};

		throw new InvalidArgumentException("Property [$key] does not exist on response.");
	}
}