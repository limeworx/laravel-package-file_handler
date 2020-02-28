<?php

namespace Limeworx\FileHandler\Http\Requests\Files;
use Limeworx\FileHandler\Http\Requests\Request;

class FileGetRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file_name' => 'required|string',
            'file_type' => 'required|string'
            
        ];
    }

      /**
    * Get the error messages for the defined validation rules.
    *
    * @return array
    */
    
    public function messages()
    {
        return[
            'file_type.required'=>'Please provide a file type. For example, is this file a "Draft" or "Final" or "Artwork", etc?'
        ];
    }
}
