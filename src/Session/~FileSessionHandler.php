<?php

namespace TeaPress\Session;

use TeaPress\Http\CookieJar;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Session\FileSessionHandler as IlluminateFileSessionHandler;

class FileSessionHandler extends IlluminateFileSessionHandler {

	protected $request;
	protected $cookies;
	protected $expires_on_close;

	public function __construct(Filesystem $files, CookieJar $cookie_jar, $path, $expires_on_close = false){
		parent::__construct($files, $path);
		$this->cookies = $cookie_jar;
		$this->expires_on_close = $expires_on_close;

	}

	public function setMinutes($minutes){
		$this->minutes = $minutes;
	}

	public function setRequest($request){
		$this->request = $request;
	}

	public function getSessionId($session_name, $default = null){
		return $this->cookies->get( $session_name, $default );
	}

	public function saveSessionId( $name, $id ){
		$current = $this->getSessionId( $name );
		if($current && $current === $id)
			return;

		$cookie = $this->expires_on_close
				? $this->cookies->make( $name, $id, 0 )
				: $this->cookies->forever( $name, $id );
		$this->cookies->queue($cookie);
	}

	public function read($sessionId){
		return parent::read($sessionId);
	}

	protected function destroyIfExpired($sessionId, $minutes){
		$seconds = $minutes * 60;
		$files = Finder::create()
					->in($this->path)
					->files()
					->name($sessionId)
					->ignoreDotFiles(true)
					->date('<= now - '.$seconds.' seconds');

		$deleted = 0;
		foreach ($files as $file) {
			$this->files->delete($file->getRealPath());
			$deleted +=1;
		}
		return $deleted;
	}

	public function gc($minutes, $sessionId = null){
		return !is_null($sessionId) ? $this->destroyIfExpired($sessionId, $minutes) : parent::gc($minutes * 60);
	}

}