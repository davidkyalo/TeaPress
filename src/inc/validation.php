<?php
namespace Tea;

function getUrlFactory(){
	return app('url');
}

function isWpAdminPath($value, $sanitize = true){
	$urls = getUrlFactory();
	$value = $sanitize ? $urls->sanitize($value): $value;
	if(lwrapslash($value) != $urls->parts($value, 'path') )
		return false;
	return $urls->isAdmin($value);
}

function isAnAlienUrl($value, $sanitize = true){
	return getUrlFactory()->isAlien($value);
}

function isWpAjaxUrl($value, $sanitize = true){
	$value = $sanitize ? getUrlFactory()->sanitize($value): $value;
	return getUrlFactory()->isAjax($value);
}

function isWpCronUrl($value, $sanitize = true){
	$value = $sanitize ? getUrlFactory()->sanitize($value): $value;
	return getUrlFactory()->isCron($value);
}

function isSiteUrl($value, $sanitize = true){
	$value = $sanitize ? getUrlFactory()->sanitize($value): $value;
	return (!isAnAlienUrl($value) && !isWpCronUrl($value) && !getUrlFactory()->isAdmin($value));
}

function isWpAdminUrl($value, $sanitize = true){
	$urls = getUrlFactory();
	return (!$urls->isAlien($value) && $urls->isAdmin($value));
}


_register_custom_validation_rules();

function _register_custom_validation_rules(){
	$factory = teapress('validation');

	$factory->extend('nonce', function($attribute, $value, $parameters, $validator){
		$value = sanitize_text_field($value);
		if(!$value)
			return false;

		$action = array_get($parameters, 0, -1);
		$expected = (array) array_get($parameters, 1, [1,2]);

		if($random_suffix_field = array_get($parameters, 2, null)){
			$random_suffix = array_get($validator->getData(), $random_suffix_field, null);
			$action = $random_suffix ? $action . $random_suffix : null;
		}

		if(is_null($action))
			return false;
		$result = wp_verify_nonce($value, $action);
		return $result === false ? false : in_array($result, $expected);
	});

	$factory->extend('admin_path', function($attribute, $value, $parameters, $validator){
		if(!$value)
			return $value;

		return isWpAdminPath($value);
	});

	$factory->extend('not_admin_path', function($attribute, $value, $parameters, $validator){
		if(!$value)
			return $value;
		return !(isWpAdminPath($value));
	});

	$factory->extend('admin_url', function($attribute, $value, $parameters, $validator){
		if(!$value)
			return $value;
		return isWpAdminUrl($value);
	});

	$factory->extend('site_url', function($attribute, $value, $parameters, $validator){
		if(!$value)
			return false;
		$URL = getUrlFactory();
		$allow_relative = array_get($parameters, 0);
		$value = getUrlFactory()->sanitize($value);
		if($allow_relative == 'relative' && !$URL->parts($value, 'host')){
			$path = $URL->path();
			return isWpAdminPath($path) ? false : true;
		}
		return isSiteUrl($value);
	});
}
