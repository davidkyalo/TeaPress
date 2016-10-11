<?php
namespace TeaPress\View;

use TeaPress\Utils\Traits\Fluent;
use BadMethodCallException;
use TeaPress\Utils\Traits\Extendable;
use Illuminate\Contracts\Support\Renderable;

class View implements Renderable {
	use Fluent;
	use Extendable {
		__call as extensionMagicCall;
	}

	const PROPERTY_CONTAINER = 'data';
	const LOCK_CLASS_VARS = true;

	protected $fillable_properties = ['*'];

	protected $data = [];

	protected $view;
	protected $factory;

	protected $extensions = [];

	public function __construct(Factory $factory, $view, array $data = []){
		$this->factory = $factory;
		$this->view = $view;
		$this->setProperties($data);
	}

	public function name(){
		return $this->view;
	}

	public function getName(){
		return $this->view;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getData(){
		return $this->data;
	}

	protected function gatherData(){
		$data = $this->getData();
		foreach ($data as $key => $value) {
			if ($value instanceof Renderable) {
				$data[$key] = $value->render();
			}
		}
		return $data;
	}

	protected function renderContents(){
		$this->factory->callComposers( $this );
		return $this->factory->getContents( $this->view, $this->gatherData() );
	}

	public function render(){
		return $this->renderContents();
	}

	public function display()
	{
		echo $this->render();
	}

	public function with($key, $value = null){
		if (is_array($key)) {
			$this->setProperties( $key );
		} else {
			$this->setProperty( $key, $value );
		}
		return $this;
	}

	public function nest($key, $view, array $data = []){
		$nested = $this->factory->make($view, $data);
		$this->with($key, $nested);
		return $nested;
	}

	public function __toString(){
		return $this->render();
	}

	public function __call($method, $parameters){
		if (strpos($method, 'with') === 0) {
			return	$this->with(snake_case(substr($method, 4)), $parameters[0]);
		}

		return $this->extensionMagicCall( $method, $parameters );

		// throw new BadMethodCallException("Method [$method] does not	exist on view.");
	}
}
