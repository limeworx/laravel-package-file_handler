<?php

namespace Limeworx\FileHandler\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Eloquent\Criteria\EagerLoad;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Limeworx\FileHandler\Http\Requests;

use Limeworx\FileHandler\Http\Requests\Files\FileGetRequest;
use Limeworx\FileHandler\Http\Requests\Files\FileUploadRequest;
use Limeworx\FileHandler\Traits\SharedFileFunctions;
use Limeworx\FileHandler\Models\FileUploads;
use App\Http\Controllers\MainController;
use Limeworx\FileHandler\JsonResponseService;


/**
 * Class PermissionController
 * @group File Handling From Package
 * @package App\Http\Controllers
 */
class FilesController extends MainController
{
    //Instantiate the shared file traits.
    use SharedFileFunctions;

    //Temp / test  function - stream to server
    /**
     * TEST - Stream File
     * This is a temporary function to test file streaming in case the main one doesn't work! :)
     */
    function StreamFileToServer(Request $request)
    {
        
        $file = $request->file("file");

        $name = "My Cool Streamed File";
        $ext = $request->file('file')->getClientOriginalExtension();
        $filePath = getcwd().'/images/tests/';
        $r = Storage::disk('s3')->putFileAs('test', $file, $name.'.'.$ext);
        print_r($r);
    }


    //Upload a posted file.
    /**
     * Upload File 
     * Upload and process a file, making sure it is a valid file, and then send it to the S3 bucket specified in the lv ENV file.
     * ### Rules:
     * + File can be of any type and size.
     * + User must be logged in, and have permission to upload the file.
     * + File Name must be present.
     * + A file must be attached to the call to be processed by it.
     * @bodyParam file_type string required You should specify what kind of file this will be - EG: Draft, Final, Artwork, etc.
     * @bodyParam access_token string required The token sent to you in the email.
     * @authenticated
     * @responseFile responses/uploadFile200.json
     * @responseFile 401 responses/requiresAuth401.json
     */


    function UploadFile(FileUploadRequest $request)
    {
        $user = auth()->user();
        if($user==null){
            return $this->response->fail(
                array("message"=>'Unable to proceed - No login found.')
            );
        }
        $name = $this->sanitize_file_name($request->input('file_name'));
        $file = $request->file('file');
        $ext = $request->file('file')->getClientOriginalExtension();
        $fileType  = ucfirst(strtolower($request->input('file_type')));
        $upload_time = time();
        
        if($name==''){
            $name=$this->sanitize_file_name($file->getClientOriginalName());
        }
        $token = $this->GetFileUniqueToken($name);

        $ins = DB::table('file_uploads')->insertGetId([
            'file_name'=>$name,
            'S3_unique_token'=>$token,
            's3_bucket_name'=>'limeworx-application-test-bucket',//env('AWS_BUCKET'),
            'uploader_id'=>$user->id,
            'created_at'=>now(),
            'is_current_file'=>1,
            'file_extension'=>$ext,
            'file_type'=>$fileType,
            'upload_timestamp'=>$upload_time,
        ]);
        
        //If the insert has been successful, we need to move the file to S3!
        if(strlen($ins)>0 && $ins!=0)
        {
            //Upload the file to S3.
            
            $filePath = 'images/bupa-booking/'.$token.'/'.$fileType.'/'.$upload_time.'/'.$name.'.'.$ext;
            $upload = Storage::disk('s3')->put($filePath, fopen($file, 'r+'));

            if($upload)
            {
                $sql = "UPDATE file_uploads SET is_current_file = NULL, date_file_replaced = NOW() WHERE id != ? AND is_current_file = 1 AND file_name = ?";
                DB::select($sql, [$ins, $name]);

                $thumbs = $this->GenerateThumbnails($file);
                if($thumbs == false){
                    return $this->response->success(
                        array(
                                "message"=>"File upload successful, data stored in database and file moved to S3 bucket.",
                                "thumbnails"=>"Thumbnails failed to upload - added to queue for schedules upload.",
                            )
                    );
                }
                else
                {
                    $upload_tracker=array();
                    $c=0;
                    foreach($thumbs as $val)
                    {
                        //We need to upload the thumbnails to S3, too! :)
                        $verb = "large";
                        switch($c){
                            case 1: $verb = "medium"; break;
                            case 2: $verb = "small"; break;
                        }
                        $thumbPath = 'images/bupa-booking/'.$token.'/'.$fileType.'/'.$upload_time.'/thumbs/'.$name.'_'.$verb.'.png';
                        //$upload = Storage::disk('s3')->put($filePath, fopen($val, 'r+'));
                        $upload = Storage::disk('s3')->put($thumbPath, $val->__toString());
                      
                        
                        if(!$upload){
                            $upload_tracker[$c]="Failed to upload thumbnail $c: $thumbPath";
                        }else{
                            $upload_tracker[$c]="Upload $c successfully uploaded: $thumbPath";
                        }
                        $c++;
                    }

                    return $this->response->success(
                        array(
                                "message"=>"File upload successful, data stored in database and file moved to S3 bucket.",
                                "thumbnails_upload_track"=>$upload_tracker,
                            )
                    );
                }                
            }
            else
            {
                //Undo previous sql query
                $del = DB::table('file_uploads')->where('id','=',$ins)->delete();
                if($del)
                {
                    return $this->response->fail(
                        array("message"=>'File upload has failed.  Successfully removed data from the files table.')
                    );
                }
                else
                {
                    return $this->response->fail(
                        array("message"=>'File upload has failed.  Unable to remove data from the files table in the database, orphaned data requires manual deletion.', 'insert_id'=>$ins)
                    );
                }
            }
            
        }
        else
        {
            return $this->response->fail(
                array("message"=>'An issue arose when inserting the file details into the database.  Please try again')
            );
        } 
    }

    /**
     * Fetch File 
     * This function will search the database and the S3 bucket for a file by the provided name, and return a temporary URL if it exists.   If it doesn't exist, it will present an error to the user.
     * ### Rules:
     * + Valid access token must be present.
     * + Valid file name must be present.
     * + Return object to contain temporary URL, not a permalink to a file.
     * + Temporary URLs will last for 20 minutes and afterwards become invalid.
     * @bodyParam file_name string required This will represent how the file will be stored on the S3 bucket.
     * @bodyParam file_type string required You should specify what kind of file this will be - EG: Draft, Final, Artwork, etc.
     * @bodyParam access_token string required The token sent to you in the email.
     * @responseFile responses/fetchFile200.json
     * @responseFile 401 responses/requiresAuth401.json
     * @authenticated
     */
    function GetFile(FileGetRequest $request)
    {
        //Validation takes place in Http/Requests/FileGetRequest before landing here.
        $name = $this->sanitize_file_name($request->input('file_name'));
        $token = $this->GetFileUniqueToken($name);
        $ft = $request->input('file_type');

        //Does file exist in database?
        
        if($this->GetIsFileInDB($name)===false)
        {
            return $this->response->fail(
                array("message"=>"File '$name' does not exist in the database.  Please check the file name and try again.")
            );
            
        }
        else 
        {   
            //Has user provided stimetamp?  If not, get latest
            $ts = ($request->input('time_stamp') ? $request->input('time_stamp') : $this->GetFileTimeStamp($name));
            if($ts[0]==true){
                $ts=$ts[1];
            }else{
                return $this->response->fail(
                    array("message"=>"Unable to find file timestamp in database - please check that the file exists.")
                );
            }

            $r=$this->GetFileExistsOnS3($name, $token, $ts, $ft);
            if($r[0]==true)
            {
                //print_r($r);
                //Get file key
                $src = $r[1]['src'];
                $lth = $r[1]['thumbs']['large'];
                $mth = $r[1]['thumbs']['medium'];
                $sth = $r[1]['thumbs']['small'];

                //echo "SRC: $src, LTH: $lth, MTH: $mth, STH: $sth";
                $exp = now()->addMinutes(20);
                
                $url = Storage::disk('s3')->temporaryUrl($src, $exp);
                $lth_url = Storage::disk('s3')->temporaryUrl($lth, $exp);
                $mth_url = Storage::disk('s3')->temporaryUrl($mth, $exp);
                $sth_url = Storage::disk('s3')->temporaryUrl($sth, $exp);

                $data=array('src'=>$url, 'thumbnails'=>array('large'=>$lth_url, 'medium'=>$mth_url, 'small'=>$sth_url));
                
                return $this->response->success(
                    array("message"=>"File retrieved, temporarily URL expires at: ".date("d/m/Y H:i", $exp->getTimeStamp()), 'data'=>$data)
                );
            }
            else
            {
                return $this->response->fail(
                    array("message"=>"File 'images/bupa_booking/$token/$ft/$ts/$name' does not exist in the S3 bucket.  Please check the file name and try again.")
                );
            }
        }
    }

    /**
     * Delete File 
     * Removes the file by the presented name from the S3 bucket and from the database.
     * ### Rules:
     * + Valid access token must be present.
     * + Valid file name must be present.
     * + File is removed permanently from the S3 bucket
     * + File is soft removed from the database.
     * @bodyParam file_name string required This will represent how the file will be stored on the S3 bucket.
     * @bodyParam access_token string required The token sent to you in the email.
     * @responseFile responses/fileDeleted200.json
     * @responseFile 401 responses/requiresAuth401.json
     * @authenticated
     */
    function DeleteFile(FileGetRequest $request)
    {
        //Validation takes place in Http/Requests/FileGetRequest before landing here.
        $name = $this->sanitize_file_name($request->input('file_name'));
        $token = $this->GetFileUniqueToken($name);
        $ft = $request->input('file_type');
        if($this->GetIsFileInDB($name)===false)
        {
            return $this->response->fail(
                array("message"=>"File does not exist in the database.  Please check the file name and try again.")
            );   
        }
        else 
        {
            $ts = ($request->input('time_stamp') ? $request->input('time_stamp') : $this->GetFileTimeStamp($name));
            if($ts[0]==true){
                $ts=$ts[1];
            }else{
                return $this->response->fail(
                    array("message"=>"Unable to find file timestamp in database - please check that the file exists.")
                );
            }
            $r = $this->GetFileExistsOnS3($name, $token, $ts, $ft); 
            if($r[0])
            {
                //Delete from S3
                $r= Storage::disk('s3')->delete($r[1]['src']);
                //Delete thumbs
                Storage::disk('s3')->deleteDirectory('images/bupa-booking/'.$token.'/'.$ft.'/'.$ts.'/thumbs');

                //We don't strictly need to know if the file has been removed or not.  We can assume that it has either been deleted, or that it was never there to begin with.
                //Either way, we still need to proceed with removing the file from the database.
                $r=$this->FlagFileAsDeleted($name);
                if($r==true)
                {   
                    //$r=Storage::disk('s3')->delete('images/bupa-booking/'.$token.'/'.$ft.'/'.$ts);
                    return $this->response->success(
                        array("message"=>"File Deleted Successfully")
                    );
                }
                else
                {
                    return $this->response->fail(
                        array("message"=>"File does not exist in the database.  Please check the file name and try again.")
                    );  
                }
            }
            else
            {
                //If it doesn't exist on the S3 bucket, we can just strip out the file table.
                $r=$this->FlagFileAsDeleted($name);
                if($r==true)
                {
                    return $this->response->success(
                        array("message"=>"File Deleted Successfully")
                    );
                }
                else
                {
                    return $this->response->fail(
                        array("message"=>"File does not exist in the database.  Please check the file name and try again.")
                    );  
                }
            }
        }
        
    }

}
