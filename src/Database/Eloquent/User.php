<?php

namespace WeDevs\ORM\WP;


use WeDevs\ORM\Eloquent\Model;

class User extends Model
{

	protected static $wp_class_config = [
		'getter'		=> 'wpuser'
	];

	protected static $attribute_alliases = [
		'email'		=> 'user_email',
		'login'		=> 'user_login',
		'nicename'	=> 'user_nicename',
		'status'	=> 'user_status'
	];

	protected $_ebook_categories = null;

	protected $meta_attributes = [
		'first_name', 'last_name', 'nickname', 'company',
		'country_code', 'occupation', 'newsletter_url', 'url'
	];



	protected function createWpInstance(){
		return new WP_User($this->ID ? $this->ID : 0 );
	}

	public function getEbookCategoriesAttribute(){
		if(is_null( $this->_ebook_categories )){
			$this->_ebook_categories = new EbookCategories($this);
		}
		return $this->_ebook_categories;
	}

	public function getRoleAttribute(){
		return array_get( $this->roles(), 0, null);
	}

	public function getFullNameAttribute(){
		return trim( $this->first_name . ' ' . $this->last_name );
	}

	public function setUserEmailAttribute($value){
		$old_value = $this->attributes['user_email'];
		$this->attributes['user_email'] = $value;
		$this->attributes['display_name'] = $value;
		if(($this->ID && !$this->nickname) || ($this->nickname == $old_value))
			$this->setMeta('nickname', $value);
	}

	public function getCountryAttribute(){
		$code = $this->country_code;
		return $code ? Config::get('countries.'.$code) :'';
	}

	public function getCompanyAttribute(){
		return $this->is('partner') ? $this->wpuser->company : '';
	}

	public function is($role){
		return in_array( $role, $this->roles() );
	}

	public function roles(){
		return $this->wpuser && $this->wpuser->roles ? $this->wpuser->roles : [];
	}

	public function hasRole($role){
		return in_array( $role, $this->roles() );
	}

	public function hasCap($cap){
		return $this->wpuser->has_cap($cap);
	}

	public function can($capability){
		return $this->wpuser->has_cap( $capability );
	}

	public function checkPassword($password){
		return wp_check_password( $password, $this->user_pass, $this->ID );
	}

	public function changePassword($value){
		if($this->ID){
			wp_set_password($value, $this->ID);
			return true;
		}
		return false;
	}


	public function save(array $options =[]){
		$saved = parent::save($options);
		if($saved && $this->_ebook_categories){
			$this->ebook_categories->save();
		}
		return $saved;
	}

	public static function get($user, $columns = ['*']){
		if( ($user instanceof WP_User) && $user->ID ){
			if($model = static::find($user->ID, $columns)){
				$model->setWpInstance($user);
				return $model;
			}
		}
		elseif( $user instanceof User ){
			return $user;
		}
		elseif( is_numeric($user) ){
			return static::find($user, $columns);
		}
		elseif( is_string($user) && !empty($user) ){
			return is_email( $user )
				? static::findByEmail($user, null, true)
				: static::findByLogin($user, null, true);
		}
	}

	public static function findBy($attribute, $value, $columns = null){
		$attribute = static::getColumnName($attribute);
		return static::where($attribute, '=', $value)->first( (is_null($columns) ? ['*'] : $columns) );
	}

	public static function findByEmail($value, $columns = null, $sanitize = false){
		return static::findBy('email', ($sanitize ? sanitize_email($value) : $value), $columns);
	}

	public static function findByLogin($value, $columns = null, $sanitize = false){
		return static::findBy('login', ($sanitize ? sanitize_user($value) : $value), $columns );
	}

	public static function generateUsername($unique = true, $prefix = null, $size = null){
		setifnull($prefix, 'usr');
		setifnull($size, 24);
		$login = uniqid( $prefix );
		if( ($rand = $size - strlen($login)) > 0 )
			$login .= str_random($rand);

		return $unique && get_user_by(strtolower($login), 'login')
			? static::generateUsername($unique, $prefix, $size) : strtolower($login);

	}

	public function authkeys(){
		return $this->hasMany(AuthKey::class, 'user_id');
	}

	public function getPermitsKey(){
		return $this->getAuthkey();
	}

	public function getAuthkey(){
		$authkey = $this->authkeys()->active()->first();
		return $authkey ? $authkey : $this->generateNewAuthKey();
	}

	public function generateNewAuthKey(){
		return $this->authkeys()->save( AuthKey::generate() );
	}

	public static function generatePassword( $length = 12, $special_chars = true, $extra_special_chars = false ){
		return wp_generate_password($length, $special_chars, $extra_special_chars);
	}

	public function getMeta($key, $default = null){
		$value = get_user_meta($this->ID, $key, true);
		return empty($value) ? $default : $value;
	}

	public function getMetaAll($key, $default = [] ){
		$value = get_user_meta($this->ID, $key, false);
		return empty($value) && is_array($value) ? $default : $value;
	}

	public function setMeta($key, $value, $prev_value = ''){
		update_user_meta($this->ID, $key, $value, $prev_value);
		return $this;
	}


	public function deleteMeta($key, $value = ''){
		delete_user_meta($this->ID, $key, $value);
		return $this;
	}

	public function addMeta($key, $value, $unique = false){
		add_user_meta($this->ID, $key, $value, $unique);
		return $this;
	}

	public function hasSetMutator($key){
		$real_name = $this->getColumnName($key);
		return (in_array($real_name, $this->meta_attributes) || parent::hasSetMutator($key));
	}

	public function __call($method, $args){
		if(starts_with($method, 'set') && ends_with($method, 'Attribute') && !empty($args)){
			$key = snake_case( substr( $method, 3, -9 ) );
			if(in_array($key, $this->meta_attributes)){
				$this->setMeta($key, $args[0]);
				$this->wpSetAttr($key, $args[0]);
				return;
			}

		}
		return parent::__call($method, $args);
	}
}