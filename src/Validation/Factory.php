<?php
namespace TeaPress\Validation;

use Illuminate\Validation\Factory as BaseFactory;
use TeaPress\Contracts\Validation\Factory as Contract;


class Factory extends BaseFactory implements Contract
{

	/**
	 * Resolve a new Validator instance.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @param  array  $messages
	 * @param  array  $customAttributes
	 * @return \Illuminate\Validation\Validator
	 */
	protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
	{
		if (is_null($this->resolver)) {
			return new Validator($this->translator, $data, $rules, $messages, $customAttributes);
		}

		return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
	}

}