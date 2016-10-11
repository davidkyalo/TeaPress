<?php
namespace TeaPress\Config;

use Countable;
use TeaPress\Utils\Str;
use TeaPress\Utils\Arr;
use TeaPress\Utils\Collection;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\TranslatorInterface;
use TeaPress\Contracts\Config\Text as Contract;

class Text extends Repository implements Contract {

	protected $locale;

	protected $selector;

	protected $raw_placeholders = [];

	protected $placeholders = [];

	protected $translation_domain = 'default';


	/**
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function set($key, $value = null)
	{
		if (is_array($key)){
			foreach ($key as $innerKey => $innerValue)
				$this->set($innerKey, $innerValue);
		}
		else{
			if(is_array($value)){

			}
			Arr::set($this->items, $key, $value);
		}

	}


	protected function readFileOptions($filepath){
		$options = parent::readFileOptions($filepath);
		$key = basename($filepath, '.php');
		$this->setRawPlaceholders($key, Arr::get($options, '__placeholders', []));
		return $options;
	}


	protected function hasLoaded(){}

	protected function placeholderKey($key){
		return str_replace('.', '>', $key);
	}

	/* PROXY */
	public function placeholders($key = null, $default = []){
		$key = $key ? $this->placeholderKey($key) : $key;

		$placeholders = Arr::get($this->placeholders, $key);

		if($placeholders || is_null($key) || !strpos($key, '>') )
			return $placeholders;

		$root = explode('>', $key, 2)[0];
		return Arr::get($this->placeholders, $root, $default);
	}

	protected function setRawPlaceholders($root, array $placeholders){
		$default = Arr::pull( $placeholders, '__default', []);

		if(!empty($default))
			$this->setPlaceholders($root, $default);

		foreach ($placeholders as $key => $holders) {
			$this->setPlaceholders($root.'.'.$key, array_merge($default, $holders) );
		}
	}

	/* PROXY */
	public function setPlaceholders($key, array $placeholders){
		$key = $this->placeholderKey($key);

		if( isset( $this->placeholders[$key] ) )
			$placeholders = array_merge( $this->placeholders[$key], $placeholders );
		_n()
		$this->placeholders[$key] = $placeholders;
		return $this;
	}

	/* PROXY */
	public function raw($key, $default = null, $default_is_key = false, $final_default = null){
		return parent::get( $key, $default, $default_is_key, $final_default);
	}


	/* PROXY */
	public function get($key, $replace = [], $default = null, $domain = null){

		if( is_string($replace) && func_num_args() === 2 ){
			$default = $replace;
			$replace = [];
		}

		$message = $this->raw($key);

		$not_found = (is_null($message) || empty($message) );

		if ($not_found && is_null($default))
			return $key;

		$message = $not_found ? value($default) : $message;
		return is_string($message)
				? $this->translate( $this->transform($message, $replace, $key), $domain )
				: $message;
	}

    /**
     * Translates the given message.
     *
     * @param string      $key        The message key (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
	/* PROXY */
	public function trans($key, array $parameters = array(), $domain = null, $locale = null){
		return $this->get($key, $parameters, null, $domain);
	}

	/* PROXY */
	public function translate( $message, $domain = null, $locale = null){
		setifnull($domain, $this->translation_domain);
		return is_string($message) && function_exists('__') ? __($message, $domain) : $message;
	}


	/* PROXY */
	public function choice($key, $number, array $replace = [], $default = null){
		$message = $this->get($key, $default);

		if (is_array($number) || $number instanceof Countable) {
			$number = count($number);
		}

		$replace['count'] = $number;

		return $this->makeReplacements($this->getSelector()->choose($line, $number, $this->getLocale()), $replace);
	}

	/* PROXY */
	public function transform($message, array $replace = [], $key = null){
		if(!is_null($key))
			$replace = array_merge( $this->placeholders($key), $replace );
		return is_string($message) ? $this->makeReplacements($message, $replace) : $message;
	}

	   /**
     * Make the place-holder replacements on a line.
     *
     * @param  string  $line
     * @param  array   $replace
     * @return string
     */
	protected function makeReplacements($line, array $replace)
	{
		$replace = $this->sortReplacements($replace);

		foreach ($replace as $key => $value) {
			$value = value($value);
			$line = str_replace(
				[':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
				[$value, Str::upper($value), Str::ucfirst($value)],
				$line
				);
		}

		return $line;
	}

	/**
	 * Sort the replacements array.
	 *
	 * @param  array  $replace
	 * @return array
	 */
	protected function sortReplacements(array $replace){

		return (new Collection($replace))->sortBy(function ($value, $key) {
			return mb_strlen($key) * -1;
		});
	}



    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param int         $number     The number to use to find the indice of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
	/* PROXY */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null){
    	return $this->choice($id, $number, $parameters);
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The locale
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    public function setLocale($locale)
    {
    	$this->locale = $locale;
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     */
	public function getLocale()
	{
		return $this->locale ? $this->locale : 'en_US';
	}

	/**
	 * Get the message selector instance.
	 *
	 * @return \Symfony\Component\Translation\MessageSelector
	 */
	public function getSelector()
	{
		if (! isset($this->selector)) {
			$this->selector = new MessageSelector;
		}

		return $this->selector;
	}
}