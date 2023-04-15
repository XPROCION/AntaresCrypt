<?php 
set_time_limit(0);
class LavaCipher
{
    private $iv;
    private $key;

    public function __construct($key, $iv = null) {
        $this->iv = $iv;
        $this->key = $key;
    }

    public function encrypt_data($type, $data, $algorithm, $dir = null) {
        $options = OPENSSL_RAW_DATA;

        if ($type == "folder") {
            if (!is_dir($dir)) {
                echo "Error: The directory does not exist! Change __DIR__.'".$dir."'";
                return;
            }
            $files = array_diff(scandir($dir), array('.', '..'));

            foreach ($files as $file) {
                $file_path = $dir . DIRECTORY_SEPARATOR . $file;

                if (is_dir($file_path)) {
                    $ht1 = new LavaCipher($this->key, $this->iv);
                    $ht1->encrypt_data("folder", null, $algorithm, $file_path);
                } else {
                    if (substr($file, -4) === "_enc") {
                        continue;
                    }
                    $file_content = file_get_contents($file_path);
                    if (empty($file_content)) {
                        file_put_contents($file_path . "_enc","");
                        file_put_contents($file_path, openssl_random_pseudo_bytes(32));
                        LavaCipherEraser::Eraser3($file_path);
                        continue;
                    }
                    $encrypted_content = openssl_encrypt($file_content, $algorithm, $this->key, $options, $this->iv);
                    file_put_contents($file_path . "_enc", $encrypted_content);
                    LavaCipherEraser::Eraser3($file_path);
                }
            }
        }else if ($type == "file") {
            if(file_exists($data) && substr($data, -4) !== "_enc"){
                $file_content = file_get_contents($data);
                if (empty($file_content)) {
                    file_put_contents($data . "_enc","");
                    file_put_contents($data, openssl_random_pseudo_bytes(32));
                    LavaCipherEraser::Eraser3($data);
                    return;
                }
                $encrypted_content = openssl_encrypt($file_content, $algorithm, $this->key, $options, $this->iv);
                file_put_contents($data . "_enc", $encrypted_content);
                LavaCipherEraser::Eraser3($data);
            } else {
                echo "Error: The file does not exist!";
            }
        } else if ($type == "text") {
            $encrypted_content = openssl_encrypt($data, $algorithm, $this->key, $options, $this->iv);
            return $encrypted_content;
        }

        return null;
    }

    public function decrypt_data($type, $data, $algorithm, $dir=null) {
        $options = OPENSSL_RAW_DATA;

        if ($type == "folder") {
            if (!is_dir($dir)) {
                echo "Error: The directory does not exist! Change __DIR__.'".$dir."'";
                return;
            }
            $files = array_diff(scandir($dir), array('.', '..'));

            foreach ($files as $file) {
                $file_path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($file_path)) {
                    $ht2 = new LavaCipher($this->key, $this->iv);
                    $ht2->decrypt_data("folder", null, $algorithm, $file_path);
                } else {
                    if (substr($file, -4) !== "_enc") {
                        continue;
                    }
                    $file_content = file_get_contents($file_path);
                    if (empty($file_content)) {
                        file_put_contents(substr($data, 0, -4), "");
                        LavaCipherEraser::Eraser3($data);
                        continue;
                    }
                    try {
                        $decrypted_content = openssl_decrypt($file_content, $algorithm, $this->key, $options, $this->iv);
                        if ($decrypted_content === false) {
                            throw new Exception("Failed to decrypt file content");
                        }
                    } catch (Exception $e) {
                        echo "Error decrypting file $file_path: " . $e->getMessage() . "\n";
                        continue;
                    }
                    file_put_contents(substr($file_path, 0, -4), $decrypted_content);
                    LavaCipherEraser::Eraser3($file_path);
                }
            }
        } else if ($type == "file") {
            if(file_exists($data)){
                $file_content = file_get_contents($data);
                if (empty($file_content)) {
                    file_put_contents(substr($data, 0, -4), "");
                    LavaCipherEraser::Eraser3($data);
                    return;
                }
                try {
                    $decrypted_content = openssl_decrypt($file_content, $algorithm, $this->key, $options, $this->iv);
                    if ($decrypted_content === false) {
                        throw new Exception("Failed to decrypt file content");
                    }
                } catch (Exception $e) {
                    echo "Error decrypting file $data: " . $e->getMessage() . "\n";
                    return;
                }
                file_put_contents(substr($data, 0, -4), $decrypted_content);
                LavaCipherEraser::Eraser3($data);
            } else {
                echo "Error: The file does not exist!";
                return;
            }
        } else if ($type == "text") {
            if (empty($data)) {
                throw new Exception("Data content is empty");
            }
            try {
                $decrypted_content = openssl_decrypt($data, $algorithm, $this->key, $options, $this->iv);
                if ($decrypted_content === false) {
                    throw new Exception("Failed to decrypt file content");
                }
            } catch (Exception $e) {
                echo "Error decrypting : " . $e->getMessage() . "\n";
                return;
            }
            return $decrypted_content;
        }

        return null;
    }
    public function add_key($new_key) {
        $this->key = $new_key;
    }
    public static function Hash($b, $c = 64)
    {
        $a = null;
        $d = 0;
        while ($d <= $c / 64) {
            if($a==null){$a = $a . hash("gost", $b);}else{
            $a = $a . hash("gost", $b . hex2bin($a));
            }
            $d++;
        }
        $b = substr($a, 0, $c);
        return $b;
    }
}
class LavaCipherEraser
{
public static function Eraser1($filename){if(!file_exists($filename)){return false;}$file=fopen($filename,"w");for($i=0;$i<35;$i++){$data='';for($j=0;$j<filesize($filename);$j++){$data.=chr(mt_rand(0,255));}fwrite($file,$data);fflush($file);fseek($file,0);}for($i=0;$i<filesize($filename);$i++){fwrite($file,"\x00");fflush($file);fseek($file,0);}for($i=0;$i<filesize($filename);$i++){fwrite($file,"\xFF");fflush($file);fseek($file,0);}fclose($file);unlink($filename);}
public static function Eraser2($filename){if(!file_exists($filename)){return false;}$size=filesize($filename);if(!$size||!is_writable($filename)){return false;}$patterns=array("\x00\xFF","\xFF\x00","\x55\xAA","\xAA\x55","\x92\x49","\x49\x92","\x24\x92","\x92\x24","\x6D\xB6","\xB6\x6D","\xDB\xDB","\x6D\xB6","\xFF\xFF","\x00\x00","\x11\x11","\x22\x22","\x33\x33","\x44\x44","\x55\x55","\x66\x66","\x77\x77","\x88\x88","\x99\x99","\xAA\xAA","\xBB\xBB","\xCC\xCC","\xDD\xDD","\xEE\xEE","\xFF\xFF","\x00\x00","\x00\x00","\xFF\xFF","\xAA\xAA","\x55\x55","\x00\x00","\xFF\xFF","\x00\x00","\xFF\xFF","\x55\x55","\xAA\xAA","\xFF\xFF","\x00\x00","\xAA\xAA","\x55\x55","\xFF\xFF","\x00\x00","\x55\x55","\xAA\xAA");$pattern_count=count($patterns);for($i=0;$i<5;$i++){$pattern=$patterns[$i%$pattern_count];$handle=fopen($filename,"w");for($j=0;$j<$size;$j+=strlen($pattern)){fwrite($handle,$pattern,strlen($pattern));}fclose($handle);}unlink($filename);return true;}
public static function Eraser3($file_path){if(!file_exists($file_path)){return false;}$file_handle=fopen($file_path,'r+');$file_size=filesize($file_path);for($i=0;$i<$file_size;$i++){fwrite($file_handle,chr(0));}$half_file_size=intval($file_size/2);for($i=0;$i<3;$i++){fseek($file_handle,0);for($j=0;$j<$half_file_size;$j++){$rand_num=rand(0,255);fwrite($file_handle,chr($rand_num));}}fseek($file_handle,$half_file_size);for($i=0;$i<3;$i++){for($j=$half_file_size;$j<$file_size;$j++){$rand_num=rand(0,255);fwrite($file_handle,chr($rand_num));}}fclose($file_handle);unlink($file_path);}
public static function Eraser4($file_path){if(!file_exists($file_path)){return false;}$fp=fopen($file_path,"r+");$file_size=filesize($file_path);$passes=array(str_repeat(chr(0x00),$file_size),str_repeat(chr(0xFF),$file_size),str_repeat(chr(0x55),$file_size),str_repeat(chr(0xAA),$file_size),str_repeat(chr(0x92),$file_size),str_repeat(chr(0x49),$file_size),str_repeat(chr(0xB6),$file_size),str_repeat(chr(0xDB),$file_size),str_repeat(chr(0xE5),$file_size),str_repeat(chr(0x24),$file_size),str_repeat(chr(0x6D),$file_size),str_repeat(chr(0x8C),$file_size),str_repeat(chr(0xB2),$file_size),str_repeat(chr(0xCC),$file_size),str_repeat(chr(0xE1),$file_size),str_repeat(chr(0xF0),$file_size));foreach($passes as $pass){fseek($fp,0);fwrite($fp,$pass);fflush($fp);}fclose($fp);unlink($file_path);}
public static function Eraser5($file_path){if(!file_exists($file_path)){return false;}$fp=fopen($file_path,"r+");$pattern=pack("H*","55").pack("H*","AA").pack("H*","FF");$file_size=filesize($file_path);for($i=0;$i<$file_size;$i+=strlen($pattern)){fseek($fp,$i);fwrite($fp,$pattern);}fclose($fp);unlink($file_path);}
public static function Eraser6($filePath){if(!file_exists($filePath)){return false;}$fp=fopen($filePath,'r+');$fileSize=filesize($filePath);for($i=0;$i<$fileSize;$i++){fwrite($fp,"\0");}fflush($fp);fclose($fp);unlink($filePath);clearstatcache(true,$filePath);}
public static function Eraser7($file_path){if(!file_exists($file_path)){return false;}$fp=fopen($file_path,"r+");$pattern=str_repeat(chr(0xff),rand(1,3)).str_repeat(chr(0x00),rand(1,3));$file_size=filesize($file_path);for($i=0;$i<$file_size;$i+=strlen($pattern)){fseek($fp,$i);fwrite($fp,$pattern);}fclose($fp);unlink($file_path);}
public static function Eraser8($filePath){if(!file_exists($filePath)){return false;}$fileSize=filesize($filePath);$fileHandle=fopen($filePath,"r+");$fileContent=fread($fileHandle,$fileSize);$secureContent=str_repeat("x",$fileSize);fseek($fileHandle,0);fwrite($fileHandle,$secureContent);fflush($fileHandle);fclose($fileHandle);unlink($filePath);}
public static function Eraser9($filename){if(!file_exists($filename)){return false;}$handle=fopen($filename,"wb");$filesize=filesize($filename);$prData=openssl_random_pseudo_bytes($filesize);fwrite($handle,$prData);fclose($handle);unlink($filename);}
public static function Eraser10($file_path){if(!file_exists($file_path)){return false;}$passes=35;$byteCount=filesize($file_path);$handle=fopen($file_path,"r+");if(!$handle){return false;}for($pass=0;$pass<$passes;$pass++){for($i=0;$i<$byteCount;$i++){fseek($handle,$i);fwrite($handle,chr($pass));}}fclose($handle);return unlink($file_path);}
public static function Eraser11($file_path){if(!file_exists($file_path)){return false;}$fp=fopen($file_path,"r+");$pattern1=str_repeat(chr(0x55),1024);$pattern2=str_repeat(chr(0xAA),1024);$pattern3=str_repeat(chr(0x92),1024);$file_size=filesize($file_path);for($i=0;$i<$file_size;$i+=strlen($pattern1)){fseek($fp,$i);fwrite($fp,$pattern1);fflush($fp);fseek($fp,$i);fwrite($fp,$pattern2);fflush($fp);fseek($fp,$i);fwrite($fp,$pattern3);fflush($fp);}fclose($fp);unlink($file_path);}
public static function Eraser12($file_path){if(!file_exists($file_path)){return false;}$patterns=array("1111111111111111111111111111111111111111111111111111111111111111","2222222222222222222222222222222222222222222222222222222222222222","3333333333333333333333333333333333333333333333333333333333333333","4444444444444444444444444444444444444444444444444444444444444444","5555555555555555555555555555555555555555555555555555555555555555","6666666666666666666666666666666666666666666666666666666666666666","7777777777777777777777777777777777777777777777777777777777777777","8888888888888888888888888888888888888888888888888888888888888888","9999999999999999999999999999999999999999999999999999999999999999","aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc","dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd","eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee","ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff","0000000000000000000000000000000000000000000000000000000000000000","0000000000000000000000000000000000000000000000000000000000000000");$handle=fopen($file_path,"a");$file_size=filesize($file_path);$iterations=intval(($file_size+511)/512);for($i=0;$i<$iterations;$i++){foreach($patterns as $pattern){fwrite($handle,$pattern,512);}}fclose($handle);unlink($file_path);}
}
?>