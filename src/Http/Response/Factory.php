<?php
namespace TeaPress\Http\Response;

use Closure;
use JsonSerializable;
use TeaPress\Http\Request;
use TeaPress\Http\CookieJar;
use TeaPress\Http\UrlFactory;
use TeaPress\Events\Dispatcher;
use TeaPress\Events\EmitterInterface;
use TeaPress\Http\Response\JsonResponse;
use TeaPress\View\Factory as ViewFactory;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Renderable;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Factory {

	use Macroable;

	protected $view;

	protected $request;

	protected $urls;

	protected $cookies;

	protected $events;


	public function __construct(ViewFactory $view, Request $request, UrlFactory $urls, CookieJar $cookies, Dispatcher $events)
	{
		$this->view = $view;
		$this->request = $request;
		$this->urls = $urls;
		$this->cookies = $cookies;
		$this->events = $events;
	}

	public function isResponse($thing){
		return ($thing instanceof BaseResponse);
	}

	public function cast($content){
		if( $this->isResponse($content) )
			return $content;

		if (is_null($content) || is_string($content) || ($content instanceof Renderable))
			return $this->make($content);


		if (is_array($content) || $content instanceof Jsonable || $content instanceof JsonSerializable)
			return $this->json($content);

		return false;
	}

	public function make($content = '', $status = 200, array $headers = []){
		$response = new Response($content, $status, $headers);
		$this->prepareResponse($response, true);
		return $response;
	}

	public function view($view, $data = null, $status = 200, array $headers = []){
		return $this->make($this->view->make($view, $data), $status, $headers);
	}

	public function json($data = [], $status = 200, array $headers = [], $options = 0)
	{
		if ($data instanceof Arrayable && ! $data instanceof JsonSerializable) {
			$data = $data->toArray();
		}

		$response = new JsonResponse($data, $status, $headers, $options);
		$this->prepareResponse($response, true);
		return $response;
	}

	public function redirect($location, $status = 302, $headers = [], $scheme = null)
	{
		if($scheme){
			$location = $this->urls->setScheme($location, $scheme);
		}

		$response = new RedirectResponse( (string) $location, $status, $headers);
		$this->prepareResponse($response, false);
		return $response;
	}

	public function redirectTo($path, $query = null, $status = 302, $headers = [], $scheme = null)
	{
		return $this->redirect($this->urls->to($path, $query), $status, $headers, $scheme);
	}

	public function redirectToRoute($name, $args = null, $status = 302, $headers = [], $scheme = null)
	{
		return $this->redirect($this->urls->route($name, $args), $status, $headers, $scheme);
	}

	public function redirectToEndPoint($name, $args = null, $status = 302, $headers = [], $scheme = null)
	{
		return $this->redirect($this->urls->get($name, $args), $status, $headers, $scheme);
	}

	public function safeRedirect($location, $status = 302, $headers = [], $scheme = null){
		$location = $this->validateRedirectUrl($location, $status);
		return $this->redirect($location, $status, $headers, $scheme);
	}


	public function back($status = 302, $headers = [], $scheme = null)
	{
		$location = $this->request->referer()
				? $this->request->referer()
				: ( $this->request->previous()
						? $this->request->previous()
						: $this->urls->home() );

		return $this->redirect($location, $status, $headers, $scheme);
	}

	public function refresh($status = 302, $headers = [], $fullUrl = true, $scheme = null)
	{
		$location = $fullUrl ? $this->request->fullUrl() : $this->request->url();
		return $this->redirect($location, $status, $headers, $scheme);
	}


	protected function prepareResponse($response, $call_prepare = false)
	{
		$this->addResponseEventListeners($response);

		if($call_prepare){
			$response->prepare($this->request);
		}

		return $response;
	}

	protected function validateRedirectUrl($url, $status){
		return $this->urls->validateRedirect(
				$this->urls->sanitizeRedirect($url),
				$this->safeRedirectFallbackUrl($status) );
	}

	protected function safeRedirectFallbackUrl($status){
		return $this->events->fire( 'wp_safe_redirect_fallback', $this->urls->home(), $status );
	}

	protected function addResponseEventListeners($response)
	{
		if( $response instanceof EmitterInterface){

			$response->on('send', function($response){
				$response->withCookies($this->cookies->getQueuedCookies());
				$this->cookies->flush();
			});

			if($response->isRedirect()){

				$response->on('send', function($response){
					$target = $this->events->fire(
								'wp_redirect',
								$response->getTargetUrl(),
								$response->getStatusCode()
							);

					$target = (string) $this->urls->sanitizeRedirect($target);
					$target->setTargetUrl($target);

					$status = $this->events->fire(
								'wp_redirect_status',
								$response->getStatusCode(),
								$response->getTargetUrl()
							);

					$response->setStatusCode($status);

				});

			}

		}
		return $response;
	}

}