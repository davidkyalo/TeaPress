<?php

namespace TeaPress\Http\Response;

use TeaPress\Events\EmitterTrait;

use Symfony\Component\HttpFoundation\Request;


trait ResponseTrait {

	use EmitterTrait;

	protected $request;

	public function setRequest(Request $request){
		$this->request = $request;
		return $this;
	}

	public function prepare(Request $request = null ){
		if( is_null($request) ) $request = $this->request;

		if($request)
			return parent::prepare($request);

		trigger_error('Request not set on response.');
		return $this;
	}

	public function send($exit = false)
	{
		$this->fireSendEvent();

		$result = parent::send();

		if(!$exit)	return $result;

		die;
	}


	public function sendHeaders()
	{
		$this->fireSendEvent();
		return parent::sendHeaders();
	}

	public function sendContent()
	{
		$this->fireSendEvent();
		return parent::sendContent();
	}

	public function __toString()
	{
		$this->fireSendEvent();
		return parent::__toString();
	}



	public function withCookies(array $cookies)
	{
		foreach ($cookies as $cookie) {
			$this->headers->setCookie($cookie);
		}
		return $this;
	}


	public function isError()
	{
		return $this->isClientError() || $this->isServerError();
	}


	protected function fireSendEvent()
	{
		if(!$this->hasEmitted('send'))
			$this->emit('send', $this);
	}

}