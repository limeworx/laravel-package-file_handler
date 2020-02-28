<?php

namespace Limeworx\FileHandler\Http\Requests;

use Limeworx\FileHandler\Services\JsonResponseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class Request
 * @package App\Http\Requests
 */
abstract class Request extends LaravelFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    abstract public function authorize();

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            (new JsonResponseService())->fail([
                'errors' => $validator->errors()->messages(),
            ])
        );
    }
}
