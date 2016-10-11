<?php

namespace TeaPress\Database\Models;

use WP_Term;
use TeaPress\Utils\Collection;
use TeaPress\Utils\Traits\Fluent;

abstract class Term {

	use Fluent;

	const PROPERTY_CONTAINER = 'wp_term';

	// protected static $taxonomy = 'taonomy_name';

	protected $wp_term;

	protected $_parent = null;

	public function __construct($term)
	{
		$this->wp_term = static::createWpTerm($term);
	}

	public function taxonomyGetter()
	{
		return static::getTaxonomy();
	}

	public function idGetter()
	{
		return $this->term_id;
	}

	public function getKey()
	{
		return $this->term_id;
	}

	public function parentIdGetter()
	{
		return $this->wp_term->parent;
	}

	public function isRootGetter()
	{
		return ($this->wp_term->parent == 0);
	}

	public function parentGetter()
	{
		if( !$this->is_root && is_null($this->_parent) )
		{
			$this->_parent = static::find($this->parent_id);
		}
		return $this->_parent;
	}

	public function childrenGetter()
	{
		return $this->children();
	}

	public function children($args = [])
	{
		$args['parent']	= $this->id;
		return static::search($args);
	}

	public static function getTaxonomy()
	{
		return static::$taxonomy;
	}

	public static function createWpTerm( $term )
	{
		if( ($term instanceof WP_Term) )
		{
			return $term;
		}

		if(!$term || is_wp_error($term))
		{
			return null;
		}

		if (is_object($term))
		{
			return new WP_Term($term);
		}
		else
		{
			return static::createWpTerm( WP_Term::get_instance($term, static::getTaxonomy() ) );
		}
	}

	public static function get($term){
		if($term instanceof Term)
			return $term;


		$wp_term = static::createWpTerm($term);
		return $wp_term ? new static( $wp_term ) : null;
	}

	public static function find($id){
		return is_array($id) ? static::findMany($id) : static::get( $id );
	}

	public static function findMany($ids){
		return !empty($ids) ? static::search(['include'=> $ids]) : static::newCollection();
	}

	public static function findBy($field, $value){
		return static::get( get_term_by( $field, $value, static::getTaxonomy() ) );
	}

	public static function findBySlug($value){
		return static::findBy('slug', $value);
	}

	public static function findByName($value){
		return static::findBy('name', $value);
	}

	protected static function wpGetTermsArgs($args = []){
		$default = [
				'taxonomy'	=> static::getTaxonomy(),
				'hide_empty'=> false,
			];
		return array_merge($default, $args);
	}

	public static function mapCollectedTerm($term){
		return static::get($term);
	}

	public static function mapCollectionKey($term){
		return $term->term_id;
	}

	public static function collect( (array) $terms){
		return static newCollection($terms);
	}

	public static function newCollection( $terms = [] ){
		if(!is_array($terms)){
			$terms = [];
		}
		$cls = get_called_class();
		$collection = Collection::make( $terms )
					->map([$cls, 'mapCollectedTerm']);
					// ->keyBy( [$cls, 'mapCollectionKey'] );
		return $collection;
	}

	public static function all(){
		return static::search();
	}

	public static function roots(array $args = []){
		$args['parent']	= 0;
		return static::search($args);
	}

	public static function search(array $args = []){
		$args = static::wpGetTermsArgs($args);
		$terms = get_terms( static::getTaxonomy(), $args );
		return static::newCollection( $terms );
	}

}