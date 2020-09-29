<?php 

namespace Limeworx\FileHandler\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Limeworx\FileHandler\Models\FileUploads;
use Limeworx\FileHandler\Models\CsvUploads;

use Image;

trait SharedFileFunctions
{
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

    protected function GetFileExistsOnS3($name, $token, $ts, $ft, $folder)
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
        $fp = 'images/'.strtolower(str_replace(' ','-', $folder))."/$token/$ft/$ts/$filename.$file_extension";
        //echo $fp;

        //Return object set up (mostly for figuring out what i need).
        $return = array(
                            'src'=>'', 
                            'thumbs'=>array(
                                'large'=>'',
                                'medium'=>'',
                                'small'=>''
                            )
                        );
        //Does file exist?
        $r=Storage::disk('s3')->exists('images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/'.$filename.'.'.$file_extension);
        if($r){
            $return['src']='images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/'.$filename.'.'.$file_extension;

            //Do thumbs exist?
            $l_thumb = Storage::disk('s3')->exists('images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/thumbs/'.$filename.'_large.png');
            $m_thumb = Storage::disk('s3')->exists('images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/thumbs/'.$filename.'_medium.png');
            $s_thumb = Storage::disk('s3')->exists('images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/thumbs/'.$filename.'_small.png');
            if($l_thumb){
                $return['thumbs']['large']='images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/thumbs/'.$filename.'_large.png';
            }else{
                $return['thumbs']['large']=false;
            }

            if($m_thumb){
                $return['thumbs']['medium']='images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/thumbs/'.$filename.'_medium.png';
            }else{
                $return['thumbs']['medium']=false;
            }

            if($s_thumb){
                $return['thumbs']['small']='images/'.strtolower(str_replace(' ','-', $folder)).'/'.$token.'/'.$ft.'/'.$ts.'/thumbs/'.$filename.'_small.png';
            }else{
                $return['thumbs']['small']=false;
            }


            return array(true, $return);
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
            $ts = $timestamp->upload_timestamp;
            return array(true, $ts);
        }
        
        return array(false, 'Unable to proceed - couldn\'t locate file in the database.');
        
    }


    /**
     * 
     * Thumbnail Functions
     * 
     */

     protected function GenerateThumbnails($file)
     {

        //Width
        $w = Image::make($file)->width();
        //Height
        $h = Image::make($file)->height();
        
        //Our start point values, which will be manipulated by our ratio value to calulate the thumbnail sizes.  Sizes in PX
        $large = 600;
        $medium = 400;
        $small = 250;
        $ratio = 1.5;


        if($w>$h)
        {
            //Landscape thumb required
            $l_height = $large/$ratio;
            $m_height = $medium/$ratio;
            $s_height = $small/$ratio;
            
            $l_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$large, 'height'=>$l_height),$file);
            $m_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$medium, 'height'=>$m_height),$file);
            $s_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$small, 'height'=>$s_height),$file);
        }
        elseif($w<$h)
        {
            //Portrait thumb required
            $l_width = $large/$ratio;
            $m_width = $medium/$ratio;
            $s_width = $small/$ratio;
            
            $l_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$l_width, 'height'=>$large), $file);
            $m_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$m_width, 'height'=>$medium), $file);
            $s_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$s_width, 'height'=>$small), $file);
        }
        else
        {
            //Square thumb required.
            
            $l_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$large, 'height'=>$large), $file);
            $m_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$medium, 'height'=>$medium), $file);
            $s_thumb = $this->GetThumbnailsFromDimensions(array('width'=>$small, 'height'=>$small), $file);
        }

        if(!empty($l_thumb) && !empty($m_thumb) && !empty($s_thumb)){
            return array($l_thumb, $m_thumb, $s_thumb);
        }else{
            return false;
        }
        

     }

     protected function GetThumbnailsFromDimensions($size_array, $file)
     {       
            $w = $size_array['width'];
            $h = $size_array['height'];

            //Width, Height, Callback(optional)
            $thumb = Image::make($file)->resize($w,$h)->stream('png','60');
            
            if($thumb){
                return $thumb;
            }else{
                return false;
            }
     }

     protected function CreateAndUploadCSV($header,$data,$name,$delimeter)
     {
        $user = auth()->user();
        if($user==null){
            return $this->response->fail(
                array("message"=>'Unable to proceed - No login found.')
            );
        }
        // Open Memory for saving CSV without writing it to the server.
        // Should probably establish a proper memory limit!
        $memcsv = fopen('php://temp/maxmemory:'.env('PHP_MEM_CSV_LIMIT'),'w');
        if($memcsv == FALSE){
            return array(false, 'Unable to access memory to write CSV.  Please try again.  If the problem persists, please contact the developers.');
        }

        $header  = json_decode($header,1);
        $records = json_decode($data,1);

        //Add headers
        //fwrite($memcsv,'sep=,'.PHP_EOL);
        fputcsv($memcsv, $header, "$delimeter");
        //Add all the data.
        foreach($records as $r){
            fputcsv($memcsv, $r, "$delimeter");
        }

        rewind($memcsv);
        $csv = stream_get_contents($memcsv);
        $fp = 'csv/'.strtolower(str_replace(' ','-', env('APP_NAME')))."/$name";

        $upload = Storage::disk('s3')->put($fp, $csv);
        if($upload)
        {

            //Now get the temp URL for this file. :D
            $remote_addr = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR']:'127.0.0.1';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT']:'php-unit-test';
            $ins_id = CsvUploads::insertGetId([
                'csv_name'=>$name,
                'generated_by'=>$user->id,
                'generated_by_ip'=>$remote_addr,
                'generated_by_browser'=>$user_agent,
                'created_at'=>now()
            ]);
            if($ins_id){
                $exp = now()->addMinutes(1);
                $url = Storage::disk('s3')->temporaryUrl($fp, $exp);
                return array(true, $url);
            }else{
                return array(false, "CSV upload success, but unable to write to the database: ".$ins_id);
            }

            
        }
        else
        {
            return array(false, "CSV upload failed.  Please try again.");
        }
         
     }
}