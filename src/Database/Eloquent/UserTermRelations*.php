<?php

namespace TeaPress\Database\Models;

use TeaPress\ORM\Facades\DB;
use TeaPress\Utils\Collection;
use TeaPress\Utils\Traits\Fluent;
/**
*
*/
abstract class UserTermRelations {

	use Fluent;
	protected $readonly_properties = ['related'];

	protected $model;
	protected $related;

	protected $context_key;
	protected $related_key;
	protected $users_key = 'user_id';
	protected $terms_key = 'term_id';

	protected static $table = 'user_term_relations';
	// protected static $terms_model = null;
	// protected static $users_model = null;

	public function __construct($model) {
		$this->setContext($model);
		$this->loadRelated();
	}

	protected function setContext($model){
		$this->model = $model;

		$users_model = static::getUsersModel();
		$terms_model = static::getTermsModel();
		if( $model instanceof  $users_model){
			$this->context_key = $this->users_key;
			$this->related_key = $this->terms_key;
		}
		elseif($model instanceof $terms_model){
			$this->context_key = $this->terms_key;
			$this->related_key = $this->users_key;
		}
	}

	protected function loadRelated(){
		$related =  $this->fetchRelatedModels($this->retrieveRelated());
		$this->setRelated( $related );
	}

	protected function fetchRelatedModels($ids){
		$model = $this->getRelatedModel();
		return $model::findMany( $ids );
	}

	protected function setRelated($related){
		$this->related = $related->keyBy( [$this, '__mapRelatedKey'] );
	}

	public function __mapRelatedKey($item){
		return $item->getKey();
	}

	public function all(){
		return $this->related->all();
	}

	public function set( $items, $commit = true ){
		$items = Collection::make($items);
		$this->setRelated($items);
		if($commit){
			return $this->save();
		}
	}

	public function add($item){
		if(!$this->contains($item)){
			$this->related->put( $item->getKey(), $item );
		}
	}

	public function contains($item){
		return $this->has( $item->getKey() );
	}

	public function has($key){
		return $this->related->has($key);
	}

	public function remove($item){
		return $this->forget( $item->getKey() );
	}

	public function forget($key){
		return $this->related->forget($key);
	}

	protected function persistingChanges(){}

	protected function changesPersisted(){}

	public function save(){

		$this->persistingChanges();

		$originals = $this->retrieveRelated();
		$erased = !empty($originals)
			? array_transform($originals, function($value, $key){
					return $value;
			}): [];

		$new = empty($originals) ? $this->related->keys()->all() : [];

		if( !empty($originals) ){
			foreach ($this->related as $key => $items) {
				if( !in_array( $key, $originals ) ){
					$new[] = $key;
				}
				else{
					unset( $erased[$key] );
				}
			}
		}


		if(!empty($erased))
			$this->deleteFromDB( $erased );
		if(!empty($new))
			$this->insertIntoDB( $new );

		$this->loadRelated();

		$this->changesPersisted();

	}

	protected function deleteFromDB( $keys ){
		return $this->contextQuery()->whereIn($this->related_key, $keys )->delete();
	}

	protected function insertIntoDB( $keys ){
		$records = [];
		$own_id = $this->model->getKey();
		foreach ($keys as $key) {
			$records[] = [ $this->context_key => $own_id, $this->related_key => $key ];
		}
		static::DB()->insert( $records );
	}


	public function retrieveRelated(){
		return $this->contextQuery()->lists($this->related_key );
	}

	public function contextQuery(){
		return static::DB()->where( $this->context_key, '=', $this->model->getKey() );
	}

	public function getRelatedModel(){
		$users_model = static::getUsersModel();
		return ($this->model instanceof $users_model)
				? static::getTermsModel() : static::getUsersModel();
	}

	public static function getTable(){
		return static::$table;
	}

	public static function getTermsModel(){
		return static::$terms_model;
	}

	public static function getUsersModel(){
		return static::$users_model;
	}

	public static function load($model){
		return new static( $model );
	}

	public static function DB(){

		return DB::table( static::getTable() );
	}

	public function __call($method, $params){
		if(method_exists( $this->related, $method )){
			return call_user_func_array( [$this->related, $method], $params );
		}
	}

}