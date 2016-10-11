<?php

namespace TeaPress\ORM;

use WeDevs\ORM\WP;
use \WP_Post;


class Post extends WP\Post {

	use ModelTrait;
	use AliasesAttributes;

	protected static $wp_class_config = [
				'getter'		=> 'wp_post'
		];

	protected function createWpInstance(){
		return new WP_Post($this->ID);
	}

	public static function get($post){
		if(!is_object( $post ))
			return static::find($post);

		if( $post instanceof WP_Post ){
			$model = static::find($post->ID);
			if($model){
				$model->setWpInstance($post);
			}
		}
		elseif( $post instanceof Post ){
			$model = $post;
		}

		return $model;
	}

}