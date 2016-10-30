<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Http\Request;
use TeaPress\Core\AliasLoader;
use TeaPress\Signals\Hub as Signals;
use TeaPress\Contracts\Core\Application;

class Initialize
{
	/**
	* Bootstrap the given application.
	*
	* @param  \TeaPress\Contracts\Core\Application  $app
	*
	* @return void
	*/
	public function bootstrap(Application $app)
	{
		$this->registerSignals($app);
		$this->captureRequest($app);
	}

	protected function registerSignals($app)
	{
		$app->instance('signals', new Signals($app));
	}

	protected function captureRequest($app)
	{
		$app->instance('request', $request = Request::capture());
	}

}