<?php
namespace TeaPress\Contracts\Http;


interface Request{

	/**
	* Get the cookie bag instance.
	*
	* @return \Symfony\Component\HttpFoundation\ParameterBag
	*/
	public function getCookieBag();
}