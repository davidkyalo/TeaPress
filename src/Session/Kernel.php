<?php

namespace TeaPress\Session;

use TeaPress\Http\CookieJar;
use TeaPress\Carbon\TimeDelta;
use TeaPress\Arch\BaseKernel;

class Kernel extends BaseKernel {

	protected $session_handled = false;

	public function run(){
		$this->bootTheSession();
		$this->action('.shutdownTheSession', 'shutdown', 99);
		$this->action('.flushUserData', 'wp_login', 999, 1);
		$this->action('.flushUserData', 'wp_logout', 999, 1);
		$this->filter('.getNonceToken', 'nonce_user_logged_out', 0, 2);

	}

	public function register(){
		$this->share([ Store::class => 'session' ], '.makeSessionStore');
	}

	public function makeSessionStore($app){
		return new Store(
				$this->sessionConfig('cookie', 'user_session'),
				$this->createSessionHandler(),
				TimeDelta::minutes($this->sessionConfig('lifetime', 120))
			);
	}

	protected function createSessionHandler(){
		return new FileSessionHandler(
						$this->app['files'],
						$this->app->make(CookieJar::class),
						$this->sessionConfig('files'),
						$this->sessionConfig('expire_on_close', false)
				);
	}

	public function bootTheSession(){
		$this->session_handled = true;

		$request = $this->app['request'];
		$session = $this->app['session'];
		$session->start();
		$request->setSession($session);

		$this->collectSessionGarbage($session->getHandler());
	}


	public function shutdownTheSession($redirect = false){
		if(!$this->session_handled)
			return;

		$session = $this->app['session'];
		$request = $this->app['request'];
		if(!$session->isStarted())
			return;

		/*$data = ['ID' => $session->getId(), 'time' => (string) as_instanceof_datetime( time() ), 'redirect' => $redirect ? 'YES' : 'NO'];
		$data['errors'] = $session->flashedErrors('ebook_categories');
		fwd_debug('Session Shutdown', $data);*/
		$session->save();
	}

	protected function collectSessionGarbage($handler, $session_id = null){
		return $handler->gc($this->sessionConfig('lifetime', 360), $session_id);
	}

	public function flushUserData($userlogin = null){
		$session  = $this->app['session'];
		$keep = apply_filters('keep_previous_user_session_data', [], $userlogin, $session);
		$session->flushUserData( $keep );
		$session->regenerateToken();
	}

	public function getNonceToken($uid){
		$session  = $this->app['session'];
		return !$uid ? $session->token() : $uid;
	}

	protected function sessionConfig($key = null, $default = null){
		return $key ? $this->app['config']['session.'.$key] : $this->app['config']['session'];
	}
}