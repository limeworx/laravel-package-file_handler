<?php

namespace Limeworx\FileHandler\Http\Requests\Files;
use Limeworx\FileHandler\Http\Requests\Request;

class CsvDownloadRequest extends Request
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
            'header_data' => 'required|json',
            'body_data' => 'required|json',
            
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
            'header_data.required'=>'Please provide an array of data to serve as your title row, in JSON format.',
            'body_data.required' => 'Please provide an array of arrays to serve as your body data, in JSON format.'
        ];
    }
}
