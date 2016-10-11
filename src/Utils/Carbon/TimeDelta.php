<?php

namespace TeaPress\Utils\Carbon;

use DateTime;
use DateInterval;
use Carbon\CarbonInterval;

class TimeDelta extends CarbonInterval {

	const DAY_IN_SECS = 86400;
	const HOUR_IN_SECS = 3600;
	const MINUTE_IN_SECS = 60;

	protected $days_count = false;

	private $_constructor_args_map = [
		'years', 'months', 'weeks',
		'dayz', 'hours',
		'minutes', 'seconds'
	];

	public function __construct($years = 1, $months=null, $weeks=null, $days=null, $hours = null, $minutes = null, $seconds = null){

		parent::__construct($years, $months, $weeks, $days, $hours, $minutes, $seconds);

		if( $inverted = $this->extractNegativeValues(func_get_args()) ){
			foreach ($inverted as $index => $value){
				$key = $this->_constructor_args_map[$index];
				$this->$key = $value;
			}
		}
	}

	public static function inverted($years=null, $months=null, $weeks=null, $days = null, $hours = null, $minutes = null, $seconds = null)
	{
		$instance = new static($years, $months, $weeks, $days, $hours, $minutes, $seconds);
		$instance->invert = 1;
		return $instance;
	}

	private function extractNegativeValues($args){
		if(is_array($args)){
			$inverted = [];
			foreach ($args as $key => $value) {
				if($value < 0)
					$inverted[$key] = $value;
			}
			return empty($inverted) ? false: $inverted;
		}
		return $args < 0 ? $args : false;
	}

	public function signed( $value ){
		return $this->invert === 1 ? $value * -1 : $value;
	}

	public function totalSeconds($signed = true){
		$total = static::DAY_IN_SECS * $this->d;
		$total += static::HOUR_IN_SECS * $this->h;
		$total += static::MINUTE_IN_SECS * $this->i;
		$total += $this->s;
		return !$signed ? $total : $this->signed($total);
	}

	/**
	 * Add the current instance to the passed DateTime object or UNIX timestamp
	 *
	 * @param DateTime|int|string $dt
	 *
	 * @return TeaPress\Carbon\Carbon
	 */
	public function addTo($dt = 'now'){
		$dt = Carbon::cast($dt, true);
		return $dt ? $dt->add($this) : false;
	}


	public function __get($key){
		switch ($key) {
			case 'totalSeconds':
				return $this->totalSeconds();
				break;

			default:
				return parent::__get($key);
				break;
		}
	}

	public function __call($key, $args){
		switch ($key) {
			case 'invert':
			case 'inverted':
				$invert = count($args) === 0 ? true : $args[0];
				$this->invert = $invert ? 1 : 0;
				break;

			default:
				parent::__call($key, $args);
				break;
		}
		return $this;
	}

}