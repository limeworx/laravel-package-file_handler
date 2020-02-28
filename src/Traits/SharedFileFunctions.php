<?php 

namespace Limeworx\FileHandler\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Limeworx\FileHandler\Models\FileUploads;

trait SharedFileFunctions
{
    //protected function showSearchResults(Request $request)
    //{
        // Stuff
    //}

    protected function FlagFileAsDeleted($name)
    {
        $r=FileUploads::where([
            ['file_name','=',$name],            
        ])
        ->whereNull("FLAG_DELETED")
        ->update([
            'FLAG_DELETED' => 1,
            'DELETED_DATE' => now()
        ]);

        return $r;
    }

    protected function GetIsFileInDB($name){
        $findFile = FileUploads::where('file_name','=',$name)
                                    ->whereNull("FLAG_DELETED")
                                    ->get()->count();
        $r = ($findFile == 0 ? false : true);
        return $r;
    }

    protected function GetFileUniqueToken($name)
    {
        //Check if this file already has a tag.
        $getTag = FileUploads::where('file_name','=',$name)
                                    ->get()->first();
        //We need this little hook to make sure that the process doesn't fail (for example, if it's a new file upload with nothing in the DB, 
        //The SQL call will return null, and when we try to fetch the object value, it will fail.)
        if($getTag ==NULL || $getTag == "NULL"){ $tag = ''; }
        else { $tag = $getTag->S3_unique_token; }
        
        //If the tag is blank or null in the database, we need to get a new tag.
        $getNewTag =  (strlen($tag)>0 ? $tag : false);
        if($getNewTag==false)
        {
            //First we need to know all the tokens in the DB, because we want this tag to be unique.
            $tokens = FileUploads::distinct('S3_unique_token')
                            ->pluck('S3_unique_token')->toArray();
            //Generate New Tag
            $newToken = substr(md5(microtime()),rand(0,26),15);

            //Make sure token doesn't exist in DB already.
            while(in_array($newToken, $tokens))
            {
                $newToken = substr(md5(microtime()),rand(0,26),15);
            }

            //Update file with new token.  This basically sets any old versions of this file to have the new tag.  This should never come up after dev is finished.
            $sql = "UPDATE file_uploads SET S3_unique_token = ? WHERE file_name = ?";
            FileUploads::where([
                ['file_name','=',$name],            
            ])
            ->update([
                'S3_unique_token' => $newToken,
            ]);

            //Set tag to be 
            $tag = $newToken;
        }
        return $tag;
    }

    protected function sanitize_file_name($name)
    {
        $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $name);
        //$name = str_replace(array(" ", "-"), "_", $name);
        //$name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $name);
        //$name = preg_replace("/\W|_/", "", $name);
        return $name;
    }

    protected function GetFileExistsOnS3($name, $token, $ts, $ft)
    {
        $stored = FileUploads::where([
                ['file_name','=',$name],
                ['is_current_file','=',1]
        ])->get()->first(); 

        if(empty($stored)){
            return array(false, 'Unable to proceed - couldn\'t locate file in the database.');
        }
        $ft= ucfirst(strtolower($ft));
        $filename = $stored->file_name;
        $file_extension = $stored->file_extension;
        $fp = "images/bupa-booking/$token/$ft/$ts/$filename.$file_extension";
        //echo $fp;

        
        
        $r=Storage::disk('s3')->exists('images/bupa-booking/'.$token.'/'.$ft.'/'.$ts.'/'.$filename.'.'.$file_extension);
        if($r){
            return array(true, 'images/bupa-booking/'.$token.'/'.$ft.'/'.$ts.'/'.$filename.'.'.$file_extension);
        }else{
            return array(false, "File doesn't exist on S3.  Please check the name and try again.");
        }
    }

    protected function GetFileTimeStamp($var){
        $timestamp = FileUploads::where([
            ['file_name','=',$var],
            ['is_current_file','=',1]
        ])->get()->first();

        if($timestamp != null || strlen($timestamp)>0 || empty(!$timestamp))
        {
            return array(true, $timestamp->upload_timestamp);
        }
        
        return array(false, 'Unable to proceed - couldn\'t locate file in the database.');
        
    }
}