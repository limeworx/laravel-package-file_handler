<?php

namespace Limeworx\FileHandler\Http\Requests\Files;
use Limeworx\FileHandler\Http\Requests\Request;

class FileUploadRequest extends Request
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
            'file' => 'required|file',
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
            'file_type.required'=>'Please provide a file type.  Many of the Wraps files share the same name, but belong in different places - for example "Finals", "Drafts", etc.  Providing a file type helps us identify where to correctly save this file.'
        ];
    }
}
