<?php

namespace TeaPress\View;

use TeaPress\Utils\Traits\Extendable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Renderable;

class Factory {

	use Extendable;

	protected $files;
	protected $view_paths = [];

	public function __construct(Filesystem $files, array $paths = []){
		$this->files = $files;
		$this->addPaths($paths);
	}

	public function addPaths(array $paths){
		foreach ($paths as $name => $path) {
			$this->addPath($name, $path);
		}
	}

	public function addPath($name, $path) {
		if(substr($name, -1) != ':'){
			trigger_error("Paths names are required to end with a ':'.");
			$name .= ':';
		}
		$this->view_paths[$name] = $path;
	}

	public function viewOutputBufferStart(){
		$output_buffer_level = ob_get_level();
		ob_start([ $this , '_viewOutputBufferCallback'], 0, PHP_OUTPUT_HANDLER_CLEANABLE ^ PHP_OUTPUT_HANDLER_REMOVABLE );
	}

	public function _viewOutputBufferCallback($buffer, $phase){
		return $buffer;
	}

	public function extractPathName($view){
		$npos = strpos($view, ':');
		if($npos === false){
			return $view[0] != '/' && isset($this->view_paths[':'])
			? $this->extractPathName(':'. $view) : [null, $view];
		}

		$path = substr($view, 0, $npos + 1);
		$view = substr($view, $npos + 1);
		return [$path, $view];
	}

	/* PROXY */
	public function getpath($view){

		if(!ends_with($view, '.php') && !ends_with($view, '.html'))
			$view .= '.php';

		list( $path, $view ) = $this->extractPathName($view);

		// $npos = strpos($view, ':');
		// if($npos === false){
		// 	return $view[0] != '/' && isset($this->view_paths[':'])
		// 	? $this->getpath(':'. $view) : $view;
		// }

		// $path = substr($view, 0, $npos + 1);
		// $view = substr($view, $npos + 1);
		return $path ? join_paths($this->view_paths[$path], $view) : $view;
	}

	/* PROXY */
	public function get($view){
		return $this->getpath($view);
	}

	/* PROXY */
	public function exists($view){
		return $this->files->exists( $this->getpath($view) );
	}

	public function isRenderable( $thing ){
		return $thing instanceof Renderable;
	}

	public function isView( $thing ){
		return $thing instanceof View;
	}


	public function getContents($view, $data){
		return $this->evaluateFileContents($this->getPath($view), $data);
	}

	protected function evaluateFileContents($_view_file_to_parse, $_view_data_to_parse = [], $_require_file_only_once_ = false){
		extract($_view_data_to_parse, EXTR_SKIP);
		$this->viewOutputBufferStart();
		if( $_require_file_only_once_ ){
			require_once($_view_file_to_parse);
		}
		else{
			require($_view_file_to_parse);
		}
		$__markup = ob_get_clean();
		// ob_end_clean();
		return $__markup;
	}

	/* PROXY */
	public function make($view, $data = null){
		setifnull($data, []);

		$view = new View( $this, $view, $data );
		$view->setExtensions( $this->getExtensions() );

		$this->callCreators( $view );

		return $view;
	}

	/* PROXY */
	public function render($view, $data = null){
		_deprecated_function("View::render()", 'Backbone 1.0', 'echo View::make()');
		$view = $this->make( $view, $data );
		echo $view;
	}

	public function callComposers(View $view){
		list( $path, $name ) = $this->extractPathName($view->name());

		if($path)
			do_action('composing_view_'.rtrim($path, ':'), $view);

		do_action('composing_view_'.$view->name(), $view);
	}

	public function callCreators(View $view) {
		list( $path, $name ) = $this->extractPathName($view->name());

		if($path)
			do_action('creating_view_'.$path, $view);

		do_action('creating_view_'.$view->name(), $view);
	}
}