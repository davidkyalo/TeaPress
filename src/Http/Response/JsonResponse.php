<?php
namespace TeaPress\Http\Response;

use TeaPress\Events\EmitterInterface;
use Illuminate\Http\JsonResponse as BaseJsonResponse;

class JsonResponse extends BaseJsonResponse implements EmitterInterface
{
	use ResponseTrait;

}
