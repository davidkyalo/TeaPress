<?php
namespace TeaPress\Utils;

use TeaPress\Utils\MessageBag;
use Symfony\Component\Translation\TranslatorInterface;
use TeaPress\Contracts\Validation\Validator as Contract;
use Illuminate\Validation\Validator as IlluminateValidator;

class Validator extends IlluminateValidator implements Contract
{

	public function __construct(TranslatorInterface $translator, array $data, array $rules,
		array $messages = [], array $customAttributes = [])
	{
		$rules = $this->rulesReloveValuesRaw($rules);
		parent::__construct($translator, $data, $rules, $messages, $customAttributes);
	}

	protected function rulesReloveValuesRaw($rules){
		foreach ($rules as $key => $value) {
			$rules[$key] = value($value);
		}
		return $rules;
	}

	public function passes()
	{
		$this->messages = new MessageBag;

		foreach ($this->rules as $attribute => $rules) {
			foreach ($rules as $rule) {
				$this->validate($attribute, $rule);
			}
		}

		foreach ($this->after as $after) {
			call_user_func($after);
		}

		return count($this->messages->all()) === 0;
	}
}