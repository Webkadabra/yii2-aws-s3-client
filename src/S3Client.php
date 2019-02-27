<?php 
namespace farhan928\AwsS3;

use Yii;
use yii\base\Component;
use Aws\S3\S3Client as AwsS3Client;
use Aws\S3\Exception\S3Exception;

class S3Client extends Component
{
    public $key;
    public $secret;
    public $bucket;
    public $version = 'latest';
    public $region = 'ap-southeast-1';  
    private $s3Client;  
    
    public function __construct($config = [])
    {
        // ... initialization before configuration is applied

        parent::__construct($config);
    }

    public function init()
    {
        parent::init(); // Call parent implementation;

        $this->s3Client = new AwsS3Client([
            //'profile' => 'default',
            'version' => $this->version,
            'region'  => $this->region,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ],
        ]);

    }

    /*
     *  Get the URL for the given file.
     */
    public function url($file){
        return $this->s3Client->getObjectUrl($this->bucket, $file);
    }

    /*
     *  Create a temporary URL to a given file.
     */
    public function temporaryUrl($file, $duration){
        $cmd = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $file
        ]);
        
        $request =  $this->s3Client->createPresignedRequest($cmd, '+'.$duration.' minutes');
        $presignedUrl = (string)$request->getUri();

        return $presignedUrl;
    }

    /*
     *  Store raw file contents to bucket.
     */
    public function put($file, $content){
        
        $finfo = finfo_open();
        $mime_type = finfo_buffer($finfo, $content, FILEINFO_MIME_TYPE);
        finfo_close($finfo);
        // get file type png. jpg
        // $ext = $mime_type ? str_replace('image/', '', $mime_type) : 'png';
        
        try {
            return $this->s3Client->putObject([
                'ACL' => 'public-read',
                'Body' => $content,
                'Bucket' => $this->bucket,
                'Key' => $file,
                'ContentType' => $mime_type,
                'ServerSideEncryption' => 'AES256',
            ]);
        } catch (S3Exception $e) {
            return false;
        }        
    }

    /*
     *  Store from file resource to bucket.
     */
    public function store($file, $resource){
       
        try {
            return $this->s3Client->putObject([
                'ACL' => 'public-read',              
                'SourceFile' => $resource->tempName,
                'Bucket' => $this->bucket,
                'Key' => $file,
                'ContentType' => $resource->type,
                'ServerSideEncryption' => 'AES256',
            ]);
        } catch (S3Exception $e) {
            return false;
        }      
    }

    /*
     *  Copy an existing file to a new location.
     */
    public function copy($old_file, $new_file){
           
        $object = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $old_file, // REQUIRED
        ]);           
        
        $new_file = self::getNewFile($new_file); 
        
        try {
            return $this->s3Client->copyObject([
                'ACL' => 'public-read',
                'Bucket' => $this->bucket,
                'CopySource' => '/'.$this->bucket.'/'.$old_file,
                'Key' => "{$new_file}",
                'ContentType' => $object->get('ContentType'),
                'ServerSideEncryption' => 'AES256',
            ]);
        } catch (S3Exception $e) {
            return false;
        }  
    }

    /*
     *  Rename or move an existing file to a new location.
     */
    public function move($old_file, $new_file){
        try {
            if (self::copy($old_file, $new_file)){
                return $this->s3Client->deleteObject([               
                    'Bucket' => $this->bucket,                
                    'Key' => $old_file,                
                ]);
            } else {
                return false;
            }
        } catch (S3Exception $e) {
            return false;
        }  
    }

    /*
     *  Check if file exists.
     */
    public function fileExists($file){
        $file_full_path = 's3://'.$this->bucket.'/'.$file;
        
        $this->s3Client->registerStreamWrapper();

        try {
            if (file_exists($file_full_path) && is_file($file_full_path)){
                return true;
            } else {
                return false;
            }
        } catch (S3Exception $e) {
            return false;
        }  
    }

    /*
     *  Get all files from a path
     */
    public function files($path = '', $extension = []){
        $files = [];
        
        if ($path) {
            // remove slash at beginning
            if(substr($path, 0, 1) == '/') {
                $path = ltrim($path, '/');
            }

            // add slash at the end 
            if(substr($path, -1) != '/') {
                $path .= '/';
            }
        }        
        
        $dir = "s3://".$this->bucket."/".$path;

        $this->s3Client->registerStreamWrapper();

        if (is_dir($dir) && ($dh = opendir($dir))) {
            while (($file = readdir($dh)) !== false) {
                $ext = pathinfo($dir . $file, PATHINFO_EXTENSION);
                //echo "filename: {$file}, filetype: " . filetype($dir . $file) . " extension: " . $ext .  "<br/>";
                
                if (filetype($dir . $file) == 'file') {
                    if (count($extension) > 0){
                        if ( in_array($ext, $extension) ) {
                            $files[] = $file;
                        }
                    } else {
                        $files[] = $file;
                    }                    
                }
            }
            closedir($dh);  
        }

        return $files;
    }

    /*
     *  Get a new file name if there's file with same name
     */
    private function getNewFile($new_file){
        $new_file_full_path = 's3://'.$this->bucket.'/'.$new_file;
        $store_file_path = $new_file;

        $this->s3Client->registerStreamWrapper();

        $i=1;
        while(file_exists($new_file_full_path) && is_file($new_file_full_path)){
            $new_file_arr = explode('.', basename($new_file));
            $new_file_ext = end($new_file_arr);
            $new_file_name = basename($new_file, '.'.$new_file_ext);
            $new_file_path = str_replace(basename($new_file), '', $new_file);
            $store_file_name = $new_file_name . '-' . $i . '.' . $new_file_ext;
            $store_file_path = $new_file_path.$store_file_name;
            $new_file_full_path = 's3://'.$this->bucket.'/'.$store_file_path;
            $i++;
        }

        return $store_file_path;
    }

    /*
     *  Just for lulz
     */
    public static function kamen()
    {
        return 'Henshin!!!';
    }
}