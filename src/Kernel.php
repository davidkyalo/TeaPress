<?php

namespace TeaPress;

use TeaPress\Arch\BaseKernel;
use TeaPress\Utils\Validator;
use TeaPress\Utils\Validation;
use TeaPress\Messages\Notifier;
use TeaPress\Messages\Bag as MessageBag;
use TeaPress\Encryption\Enigma;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Validation\DatabasePresenceVerifier;

class Kernel extends BaseKernel {

	public function register(){
		$this->share([Enigma::class => 'enigma'], '.makeEnigma');
		$this->share([Filesystem::class => 'files'], '.makeFilesystem');
		$this->share([Notifier::class => 'notices'], '.makeNotifier');

		$this->registerValidationFactory();

		MessageBag::setMessageLoader([$this, 'messageBagLoadMessage']);
	}

	public function makeEnigma($app){
		return new Enigma( $app['config']->get('enigma') );
	}

	public function makeFilesystem($app){
		return new Filesystem;
	}

	public function makeNotifier($app){
		return new Notifier($app['session']);
	}

	public function messageBagLoadMessage($key, $default = null){
		return call_user_func_array( [$this->app['lang'], 'get'], func_get_args());
	}

	protected function registerValidationFactory(){

		$this->share('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db_conn.resolver']);
        });
		$this->share([ Validation::class => 'validation'], '.makeValidationFactory');
		$this->bind(Validator::class);
	}

	public function makeValidationFactory($app){
		$factory = new Validation($app['lang'], $app);
		if (isset($app['validation.presence'])) {
			$factory->setPresenceVerifier($app['validation.presence']);
		}

		$factory->resolver($this->getValidatorResolver());
		return $factory;
	}

	protected function getValidatorResolver(){
		return function(){
			return $this->app->make(Validator::class, func_get_args());
		};
	}

	public function exists($user, $field = 'email'){
		if(!in_array($field, ['ID', 'email', 'login'])){
			trigger_error("Checking user existence by '{$field}'' field is highly discouraged.
				Recommended '". implode(', ', $allowed) ."'.");
		}
		$user = $this->getWpUserBy($field, $user);
		return $user ? $user->ID : false;
	}

	public function getWpUserBy($field, $value){
		return get_user_by($field, $value);
	}

	public function run(){
		$this->loadCustomValidations();

		$this->action('.sendNotices', 'admin_notices', 99);
		$this->action('.sendNotices', 'wp_footer', 99);
		$this->action('.sendNotices', 'login_footer', 99);
	}

	public function sendNotices(){
		$notices = $this->app->make('notices');
		echo $notices;
	}



	public function generateUserLogin($prefix = 'usr', $size = 24, $unique = true){
		$login = uniqid( $prefix );
		if( ($rand = $size - strlen($login)) > 0 )
			$login .= str_random($rand);

		return $unique && $this->exists( strtolower($login), 'login')
			? $this->generateUserLogin($prefix, $size, $unique) : strtolower($login);
	}

	protected function loadCustomValidations(){
		require_once __DIR__.'/inc/validation.php';
	}
}