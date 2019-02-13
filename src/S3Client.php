<?php 
namespace farhan928\AwsS3;

use Yii;
use yii\base\Component;
use Aws\S3\S3Client as AwsS3Client;
use Aws\Exception\AwsException;

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
     *  The put method may be used to store raw file contents on a disk.
     */
    public function put($file, $content){
        
        $finfo = finfo_open();
        $mime_type = finfo_buffer($finfo, $content, FILEINFO_MIME_TYPE);
        finfo_close($finfo);
        // get file type png. jpg
        // $ext = $mime_type ? str_replace('image/', '', $mime_type) : 'png';
        
        return $this->s3Client->putObject([
            'ACL' => 'public-read',
            'Body' => $content,
            'Bucket' => $this->bucket,
            'Key' => $file,
            'ContentType' => $mime_type,
            'ServerSideEncryption' => 'AES256',
        ]);
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
        
        return $this->s3Client->copyObject([
            'ACL' => 'public-read',
            'Bucket' => $this->bucket,
            'CopySource' => '/'.$this->bucket.'/'.$old_file,
            'Key' => "{$new_file}",
            'ContentType' => $object->get('ContentType'),
            'ServerSideEncryption' => 'AES256',
        ]);
    }

    /*
     *  Move an existing file to a new location.
     */
    public function move($old_file, $new_file){
        if(self::copy($old_file, $new_file)){
            return $this->s3Client->deleteObject([               
                'Bucket' => $this->bucket,                
                'Key' => $old_file,                
            ]);
        }
    }

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

    public static function kamen()
    {
        return 'Henshin!!!';
    }
}