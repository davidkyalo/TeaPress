<?php

namespace TeaPress\Auth;

use TeaPress\Arch\BaseKernel;

class Kernel extends BaseKernel {

	public function run(){
		$this->setupTokenManager();
		$this->configurePermit();
		$this->includes();
	}

	protected function setupTokenManager(){
		UserTokens::setUserModel($this->userConfig('model'));
		UserTokens::currentSessionSetupHooks();
		$this->filter('.getTokenManager', 'session_token_manager', 999);
	}

	protected function configurePermit(){
		Permit::setUrlBase( $this->userConfig('permits.url_base') );
		Permit::setCipherKeys( $this->userConfig('permits.cipher_keys') );
		Permit::setSerializables( $this->userConfig('permits.serializables') );
	}

	public function getTokenManager(){
		return UserTokens::class;
	}

	public function register(){
		$this->share([Sentry::class => 'auth'], '.makeSentry');
		$this->app->alias(Sentry::class, 'sentry');

	}

	public function makeSentry($app){
		return new Sentry($app, $app['config']['users']);
	}

	protected function userConfig($key = null, $default = null){
		return $key ? $this->app['config']['users.'.$key] : $this->app['config']['users'];
	}

	protected function includes(){
		// require_once(__DIR__. '/hooks.php');
	}

	protected function actions(){

	}
}