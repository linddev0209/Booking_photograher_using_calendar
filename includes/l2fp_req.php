<?php

require_once('vendor/autoload.php');

ini_set('session.cookie_lifetime', 2678400); // 30 Days
ini_set('session.gc-maxlifetime', 2678400); // 30 Days

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    // last request was more than 1 hour ago
    session_destroy();   // destroy session data in storage
    session_unset();     // unset $_SESSION variable for the runtime
	header("location: index.php?flag=timed");
}
$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 2700) {
    // session started more than 30 minates ago
    session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
    $_SESSION['CREATED'] = time();  // update creation time
}

date_default_timezone_set('US/Eastern');

function site_crypt( $string, $action = 'e' ) {
    $secret_key = 'L2CBH2017';
    $secret_iv = '98YvGg213';
 
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash( 'sha256', $secret_key );
    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
 
    if( $action == 'e' ) {
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }
    else if( $action == 'd' ){
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }
 
    return $output;
}

function logout(){	
	unset($_SESSION['SESS_MEMBER_ID']);
	unset($_SESSION['SESS_FIRST_NAME']);
	unset($_SESSION['SESS_LAST_NAME']);
	unset($_SESSION['SESS_LEVEL']);
	unset($_SESSION['LAST_ACTIVITY']);
	unset($_SESSION['CREATED']); 
	//destroy any previously set cookie
	setcookie('MEMBER_ID', '', time() - 30*24*60*60);
	setcookie('FIRST_NAME', '', time() - 30*24*60*60);
	setcookie('LAST_NAME', '', time() - 30*24*60*60);
	setcookie('LEVEL', '', time() - 30*24*60*60);
	return(header("Location: index.php?flag=signout"));
}


function SendEmail($from, $namefrom, $to, $nameto, $subject, $message, $h_email)
{
    
    include_once('Mail.php');
	include_once('Mail/mime.php');

	$crlf = "\n";
	$hdrs = array(
              'From'    => sprintf("'%s' <%s>", $namefrom, $from),
              'Subject' => $subject
            );

	$mime = new Mail_mime($crlf);

	$mime->setTXTBody($message);
	$mime->setHTMLBody($h_email);
	
	//do not ever try to call these lines in reverse order
	$body = $mime->get();
	$hdrs = $mime->headers($hdrs);
	
	$mail = Mail::factory('mail');
	$mail->send($to, $hdrs, $body);

}

function delete_folder($folder) {
    $glob = glob($folder);
    foreach ($glob as $g) {
        if (!is_dir($g)) {
            unlink($g);
        } else {
            delete_folder("$g/*");
            rmdir($g);
        }
    }
}

function statsgrab($view,$oid){
	global $dbname, $connection, $sqluser, $sqlpw;
	if (!($link = mysqli_connect($connection, $sqluser, $sqlpw, $dbname))){
		printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit() ;
	}
	$date = date("Y-m-d H:i:s");
	$remoteADDY = $_SERVER['REMOTE_ADDR'];
	if(!($stats = mysqli_query($link,"INSERT INTO stat_hit VALUES ('','$remoteADDY','$oid','$date','$view')"))){
	printf("%s", sprintf("internal error %d:%s\n", mysqli_errno($link), mysqli_error($link)));
		exit();
	}
	mysqli_close ($link);
}

function DisplayErrMsg($message)
{
	printf("<blockquote><blockquote><blockquote><h3><font color=\"#cc0000\">        %s</font></h3></blockquote></blockquote></blockquote>\n", $message);
}

$state_list = array('AL'=>"Alabama",'AK'=>"Alaska",'AZ'=>"Arizona",'AR'=>"Arkansas",'CA'=>"California",'CO'=>"Colorado",'CT'=>"Connecticut",'DE'=>"Delaware",'DC'=>"District Of Columbia",'FL'=>"Florida",'GA'=>"Georgia",'HI'=>"Hawaii",'ID'=>"Idaho",'IL'=>"Illinois",'IN'=>"Indiana",'IA'=>"Iowa",'KS'=>"Kansas",'KY'=>"Kentucky",'LA'=>"Louisiana",'ME'=>"Maine",'MD'=>"Maryland",'MA'=>"Massachusetts",'MI'=>"Michigan",'MN'=>"Minnesota",'MS'=>"Mississippi",'MO'=>"Missouri",'MT'=>"Montana",'NE'=>"Nebraska",'NV'=>"Nevada",'NH'=>"New Hampshire",'NJ'=>"New Jersey",'NM'=>"New Mexico",'NY'=>"New York",'NC'=>"North Carolina",'ND'=>"North Dakota",'OH'=>"Ohio",'OK'=>"Oklahoma",'OR'=>"Oregon",'PA'=>"Pennsylvania",'RI'=>"Rhode Island",'SC'=>"South Carolina",'SD'=>"South Dakota",'TN'=>"Tennessee",'TX'=>"Texas",'UT'=>"Utah",'VT'=>"Vermont",'VA'=>"Virginia",'WA'=>"Washington",'WV'=>"West Virginia",'WI'=>"Wisconsin",'WY'=>"Wyoming");

function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', realpath($file));

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
	$zip->deleteName('home/look2hom/public_html/l2fp/floorplan/');
	$zip->deleteName('home/look2hom/public_html/l2fp/');
	$zip->deleteName('home/look2hom/public_html/');
	$zip->deleteName('home/look2hom/');
	$zip->deleteName('home/');
    return $zip->close();
}

/**
* FlxZipArchive, Extends ZipArchiv.
* Add Dirs with Files and Subdirs.
*
* <code>
*  $archive = new FlxZipArchive;
*  // .....
*  $archive->addDir( 'test/blub', 'blub' );
* </code>
*/
// class FlxZipArchive extends ZipArchive {
//     /**
//      * Add a Dir with Files and Subdirs to the archive
//      *
//      * @param string $location Real Location
//      * @param string $name Name in Archive
//      * @author Nicolas Heimann
//      * @access private
//      **/

//     public function addDir($location, $name) {
//         $this->addEmptyDir($name);

//         $this->addDirDo($location, $name);
//      } // EO addDir;

//     /**
//      * Add Files & Dirs to archive.
//      *
//      * @param string $location Real Location
//      * @param string $name Name in Archive
//      * @author Nicolas Heimann
//      * @access private
//      **/

//     private function addDirDo($location, $name) {
//         $name .= '/';
//         $location .= '/';

//         // Read all Files in Dir
//         $dir = opendir ($location);
//         while ($file = readdir($dir))
//         {
//             if ($file == '.' || $file == '..') continue;

//             // Rekursiv, If dir: FlxZipArchive::addDir(), else ::File();
//             $do = (filetype( $location . $file) == 'dir') ? 'addDir' : 'addFile';
//             $this->$do($location . $file, $name . $file);
//         }
//     } // EO addDirDo();
// }

/*
* File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/

class SimpleImage {

   var $image;
   var $image_type;

   function load($filename) {

      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {

         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {

         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {

         $this->image = imagecreatefrompng($filename);
      }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {

      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {

         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {

         imagepng($this->image,$filename);
      }
      if( $permissions != null) {

         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {

      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {

         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {

         imagepng($this->image);
      }
   }
   function getWidth() {

      return imagesx($this->image);
   }
   function getHeight() {

      return imagesy($this->image);
   }
   function resizeToHeight($height) {

      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }

   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }

   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }

   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
   }      

}

//
// SimpleImage2
//
//  A PHP class that makes working with images as simple as possible.
//
//  Developed and maintained by Cory LaViska <https://github.com/claviska>.
//
//  Copyright A Beautiful Site, LLC.
//
//  Source: https://github.com/claviska/SimpleImage
//
//  Licensed under the MIT license <http://opensource.org/licenses/MIT>
//

class SimpleImage2 {

  const
    ERR_FILE_NOT_FOUND = 1,
    ERR_FONT_FILE = 2,
    ERR_FREETYPE_NOT_ENABLED = 3,
    ERR_GD_NOT_ENABLED = 4,
    ERR_INVALID_COLOR = 5,
    ERR_INVALID_DATA_URI = 6,
    ERR_INVALID_IMAGE = 7,
    ERR_LIB_NOT_LOADED = 8,
    ERR_UNSUPPORTED_FORMAT = 9,
    ERR_WEBP_NOT_ENABLED = 10,
    ERR_WRITE = 11;

  protected $image, $mimeType, $exif;

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Magic methods
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Creates a new SimpleImage object.
  //
  //  $image (string) - An image file or a data URI to load.
  //
  public function __construct($image = null) {
    // Check for the required GD extension
    if(extension_loaded('gd')) {
      // Ignore JPEG warnings that cause imagecreatefromjpeg() to fail
      ini_set('gd.jpeg_ignore_warning', 1);
    } else {
      throw new \Exception('Required extension GD is not loaded.', self::ERR_GD_NOT_ENABLED);
    }

    // Load an image through the constructor
    if(preg_match('/^data:(.*?);/', $image)) {
      $this->fromDataUri($image);
    } elseif($image) {
      $this->fromFile($image);
    }
  }

  //
  // Destroys the image resource
  //
  public function __destruct() {
    if($this->image !== null && get_resource_type($this->image) === 'gd') {
      imagedestroy($this->image);
    }
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Loaders
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Loads an image from a data URI.
  //
  //  $uri* (string) - A data URI.
  //
  // Returns a SimpleImage object.
  //
  public function fromDataUri($uri) {
    // Basic formatting check
    preg_match('/^data:(.*?);/', $uri, $matches);
    if(!count($matches)) {
      throw new \Exception('Invalid data URI.', self::ERR_INVALID_DATA_URI);
    }

    // Determine mime type
    $this->mimeType = $matches[1];
    if(!preg_match('/^image\/(gif|jpeg|png)$/', $this->mimeType)) {
      throw new \Exception(
        'Unsupported format: ' . $this->mimeType,
        self::ERR_UNSUPPORTED_FORMAT
      );
    }

    // Get image data
    $uri = base64_decode(preg_replace('/^data:(.*?);base64,/', '', $uri));
    $this->image = imagecreatefromstring($uri);
    if(!$this->image) {
      throw new \Exception("Invalid image data.", self::ERR_INVALID_IMAGE);
    }

    return $this;
  }

  //
  // Loads an image from a file.
  //
  //  $file* (string) - The image file to load.
  //
  // Returns a SimpleImage object.
  //
  public function fromFile($file) {
    // Check if the file exists and is readable. We're using fopen() instead of file_exists()
    // because not all URL wrappers support the latter.
    $handle = @fopen($file, 'r');
    if($handle === false) {
      throw new \Exception("File not found: $file", self::ERR_FILE_NOT_FOUND);
    }
    fclose($handle);

    // Get image info
    $info = getimagesize($file);
    if($info === false) {
      throw new \Exception("Invalid image file: $file", self::ERR_INVALID_IMAGE);
    }
    $this->mimeType = $info['mime'];

    // Create image object from file
    switch($this->mimeType) {
    case 'image/gif':
      // Load the gif
      $gif = imagecreatefromgif($file);
      if($gif) {
        // Copy the gif over to a true color image to preserve its transparency. This is a
        // workaround to prevent imagepalettetruecolor() from borking transparency.
        $width = imagesx($gif);
        $height = imagesy($gif);
        $this->image = imagecreatetruecolor($width, $height);
        $transparentColor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        imagecolortransparent($this->image, $transparentColor);
        imagefill($this->image, 0, 0, $transparentColor);
        imagecopy($this->image, $gif, 0, 0, 0, 0, $width, $height);
        imagedestroy($gif);
      }
      break;
    case 'image/jpeg':
      $this->image = imagecreatefromjpeg($file);
      break;
    case 'image/png':
      $this->image = imagecreatefrompng($file);
      break;
    case 'image/webp':
      $this->image = imagecreatefromwebp($file);
      break;
    case 'image/bmp':
    case 'image/x-ms-bmp':
    case 'image/x-windows-bmp':
      $this->image = imagecreatefrombmp($file);
      break;
    }
    if(!$this->image) {
      throw new \Exception("Unsupported format: " . $this->mimeType, self::ERR_UNSUPPORTED_FORMAT);
    }

    // Convert pallete images to true color images
    imagepalettetotruecolor($this->image);

    // Load exif data from JPEG images
    if($this->mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
      $this->exif = @exif_read_data($file);
    }

    return $this;
  }

  //
  // Creates a new image.
  //
  //  $width* (int) - The width of the image.
  //  $height* (int) - The height of the image.
  //  $color (string|array) - Optional fill color for the new image (default 'transparent').
  //
  // Returns a SimpleImage object.
  //
  public function fromNew($width, $height, $color = 'transparent') {
    $this->image = imagecreatetruecolor($width, $height);

    // Use PNG for dynamically created images because it's lossless and supports transparency
    $this->mimeType = 'image/png';

    // Fill the image with color
    $this->fill($color);

    return $this;
  }

  //
  // Creates a new image from a string.
  //
  //  $string* (string) - The raw image data as a string. Example:
  //
  //    $string = file_get_contents('image.jpg');
  //
  // Returns a SimpleImage object.
  //
  public function fromString($string) {
    return $this->fromFile('data://;base64,' . base64_encode($string));
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Savers
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Generates an image.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns an array containing the image data and mime type.
  //
  protected function generate($mimeType = null, $quality = 100) {
    // Format defaults to the original mime type
    $mimeType = $mimeType ?: $this->mimeType;

    // Ensure quality is a valid integer
    if($quality === null) $quality = 100;
    $quality = self::keepWithin((int) $quality, 0, 100);

    // Capture output
    ob_start();

    // Generate the image
    switch($mimeType) {
    case 'image/gif':
      imagesavealpha($this->image, true);
      imagegif($this->image, null);
      break;
    case 'image/jpeg':
      imageinterlace($this->image, true);
      imagejpeg($this->image, null, $quality);
      break;
    case 'image/png':
      imagesavealpha($this->image, true);
      imagepng($this->image, null, round(9 * $quality / 100));
      break;
    case 'image/webp':
      // Not all versions of PHP will have webp support enabled
      if(!function_exists('imagewebp')) {
        throw new \Exception(
          'WEBP support is not enabled in your version of PHP.',
          self::ERR_WEBP_NOT_ENABLED
        );
      }
      imagesavealpha($this->image, true);
      imagewebp($this->image, null, $quality);
      break;
    case 'image/bmp':
    case 'image/x-ms-bmp':
    case 'image/x-windows-bmp':
      imageinterlace($this->image, true);
      imagebmp($this->image, null, $quality);
    break;
    default:
      throw new \Exception('Unsupported format: ' . $mimeType, self::ERR_UNSUPPORTED_FORMAT);
    }

    // Stop capturing
    $data = ob_get_contents();
    ob_end_clean();

    return [
      'data' => $data,
      'mimeType' => $mimeType
    ];
  }

  //
  // Generates a data URI.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a string containing a data URI.
  //
  public function toDataUri($mimeType = null, $quality = 100) {
    $image = $this->generate($mimeType, $quality);

    return 'data:' . $image['mimeType'] . ';base64,' . base64_encode($image['data']);
  }

  //
  // Forces the image to be downloaded to the clients machine. Must be called before any output is
  // sent to the screen.
  //
  //  $filename* (string) - The filename (without path) to send to the client (e.g. 'image.jpeg').
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  public function toDownload($filename, $mimeType = null, $quality = 100) {
    $image = $this->generate($mimeType, $quality);

    // Set download headers
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-Length: ' . strlen($image['data']));
    header('Content-Transfer-Encoding: Binary');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo $image['data'];

    return $this;
  }

  //
  // Writes the image to a file.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a SimpleImage object.
  //
  public function toFile($file, $mimeType = null, $quality = 90) {
    $image = $this->generate($mimeType, $quality);

    // Save the image to file
    if(!file_put_contents($file, $image['data'])) {
      throw new \Exception("Failed to write image to file: $file", self::ERR_WRITE);
    }

    return $this;
  }

  //
  // Outputs the image to the screen. Must be called before any output is sent to the screen.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a SimpleImage object.
  //
  public function toScreen($mimeType = null, $quality = 100) {
    $image = $this->generate($mimeType, $quality);

    // Output the image to stdout
    header('Content-Type: ' . $image['mimeType']);
    echo $image['data'];

    return $this;
  }

  //
  // Generates an image string.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a SimpleImage object.
  //
  public function toString($mimeType = null, $quality = 100) {
    return $this->generate($mimeType, $quality)['data'];
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Utilities
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Ensures a numeric value is always within the min and max range.
  //
  //  $value* (int|float) - A numeric value to test.
  //  $min* (int|float) - The minimum allowed value.
  //  $max* (int|float) - The maximum allowed value.
  //
  // Returns an int|float value.
  //
  protected static function keepWithin($value, $min, $max) {
    if($value < $min) return $min;
    if($value > $max) return $max;
    return $value;
  }

  //
  // Gets the image's current aspect ratio.
  //
  // Returns the aspect ratio as a float.
  //
  public function getAspectRatio() {
    return $this->getWidth() / $this->getHeight();
  }

  //
  // Gets the image's exif data.
  //
  // Returns an array of exif data or null if no data is available.
  //
  public function getExif() {
    return isset($this->exif) ? $this->exif : null;
  }

  //
  // Gets the image's current height.
  //
  // Returns the height as an integer.
  //
  public function getHeight() {
    return (int) imagesy($this->image);
  }

  //
  // Gets the mime type of the loaded image.
  //
  // Returns a mime type string.
  //
  public function getMimeType() {
    return $this->mimeType;
  }

  //
  // Gets the image's current orientation.
  //
  // Returns a string: 'landscape', 'portrait', or 'square'
  //
  public function getOrientation() {
    $width = $this->getWidth();
    $height = $this->getHeight();

    if($width > $height) return 'landscape';
    if($width < $height) return 'portrait';
    return 'square';
  }

  //
  // Gets the resolution of the image
  //
  // Returns the resolution as an array of integers: [96, 96]
  //
  public function getResolution() {
    return imageresolution($this->image);
  }

  //
  // Gets the image's current width.
  //
  // Returns the width as an integer.
  //
  public function getWidth() {
    return (int) imagesx($this->image);
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Manipulation
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Same as PHP's imagecopymerge, but works with transparent images. Used internally for overlay.
  //
  protected static function imageCopyMergeAlpha($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct) {
    // Are we merging with transparency?
    if($pct < 100) {
      // Disable alpha blending and "colorize" the image using a transparent color
      imagealphablending($srcIm, false);
      imagefilter($srcIm, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * ((100 - $pct) / 100));
    }

    imagecopy($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH);

    return true;
  }

  //
  // Rotates an image so the orientation will be correct based on its exif data. It is safe to call
  // this method on images that don't have exif data (no changes will be made).
  //
  // Returns a SimpleImage object.
  //
  public function autoOrient() {
    $exif = $this->getExif();

    if(!$exif || !isset($exif['Orientation'])){
      return $this;
    }

    switch($exif['Orientation']) {
    case 1: // Do nothing!
      break;
    case 2: // Flip horizontally
      $this->flip('x');
      break;
    case 3: // Rotate 180 degrees
      $this->rotate(180);
      break;
    case 4: // Flip vertically
      $this->flip('y');
      break;
    case 5: // Rotate 90 degrees clockwise and flip vertically
      $this->flip('y')->rotate(90);
      break;
    case 6: // Rotate 90 clockwise
      $this->rotate(90);
      break;
    case 7: // Rotate 90 clockwise and flip horizontally
      $this->flip('x')->rotate(90);
      break;
    case 8: // Rotate 90 counterclockwise
      $this->rotate(-90);
      break;
    }

    return $this;
  }

  //
  // Proportionally resize the image to fit inside a specific width and height.
  //
  //  $maxWidth* (int) - The maximum width the image can be.
  //  $maxHeight* (int) - The maximum height the image can be.
  //
  // Returns a SimpleImage object.
  //
  public function bestFit($maxWidth, $maxHeight) {
    // If the image already fits, there's nothing to do
    if($this->getWidth() <= $maxWidth && $this->getHeight() <= $maxHeight) {
      return $this;
    }

    // Calculate max width or height based on orientation
    if($this->getOrientation() === 'portrait') {
      $height = $maxHeight;
      $width = $maxHeight * $this->getAspectRatio();
    } else {
      $width = $maxWidth;
      $height = $maxWidth / $this->getAspectRatio();
    }

    // Reduce to max width
    if($width > $maxWidth) {
      $width = $maxWidth;
      $height = $width / $this->getAspectRatio();
    }

    // Reduce to max height
    if($height > $maxHeight) {
      $height = $maxHeight;
      $width = $height * $this->getAspectRatio();
    }

    return $this->resize($width, $height);
  }

  //
  // Crop the image.
  //
  //  $x1 - Top left x coordinate.
  //  $y1 - Top left y coordinate.
  //  $x2 - Bottom right x coordinate.
  //  $y2 - Bottom right x coordinate.
  //
  // Returns a SimpleImage object.
  //
  public function crop($x1, $y1, $x2, $y2) {
    // Keep crop within image dimensions
    $x1 = self::keepWithin($x1, 0, $this->getWidth());
    $x2 = self::keepWithin($x2, 0, $this->getWidth());
    $y1 = self::keepWithin($y1, 0, $this->getHeight());
    $y2 = self::keepWithin($y2, 0, $this->getHeight());

    // Crop it
    $this->image = imagecrop($this->image, [
      'x' => min($x1, $x2),
      'y' => min($y1, $y2),
      'width' => abs($x2 - $x1),
      'height' => abs($y2 - $y1)
    ]);

    return $this;
  }

  //
  // Proportionally resize the image to a specific height.
  //
  // **DEPRECATED:** This method was deprecated in version 3.2.2 and will be removed in version 4.0.
  // Please use `resize(null, $height)` instead.
  //
  //  $height* (int) - The height to resize the image to.
  //
  // Returns a SimpleImage object.
  //
  public function fitToHeight($height) {
    return $this->resize(null, $height);
  }

  //
  // Proportionally resize the image to a specific width.
  //
  // **DEPRECATED:** This method was deprecated in version 3.2.2 and will be removed in version 4.0.
  // Please use `resize($width, null)` instead.
  //
  //  $width* (int) - The width to resize the image to.
  //
  // Returns a SimpleImage object.
  //
  public function fitToWidth($width) {
    return $this->resize($width, null);
  }

  //
  // Resize an image to the specified dimensions. If only one dimension is specified, the image will
  // be resized proportionally.
  //
  //  $width* (int) - The new image width.
  //  $height* (int) - The new image height.
  //
  // Returns a SimpleImage object.
  //
  public function resize($width = null, $height = null) {
    // No dimentions specified
    if(!$width && !$height) {
      return $this;
    }

    // Resize to width
    if($width && !$height) {
      $height = $width / $this->getAspectRatio();
    }

    // Resize to height
    if(!$width && $height) {
      $width = $height * $this->getAspectRatio();
    }

    // If the dimensions are the same, there's no need to resize
    if($this->getWidth() === $width && $this->getHeight() === $height) {
      return $this;
    }

    // We can't use imagescale because it doesn't seem to preserve transparency properly. The
    // workaround is to create a new truecolor image, allocate a transparent color, and copy the
    // image over to it using imagecopyresampled.
    $newImage = imagecreatetruecolor($width, $height);
    $transparentColor = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
    imagecolortransparent($newImage, $transparentColor);
    imagefill($newImage, 0, 0, $transparentColor);
    imagecopyresampled(
      $newImage,
      $this->image,
      0, 0, 0, 0,
      $width,
      $height,
      $this->getWidth(),
      $this->getHeight()
    );

    // Swap out the new image
    $this->image = $newImage;

    return $this;
  }

  //
  // Sets an image's resolution, as per https://www.php.net/manual/en/function.imageresolution.php
  //
  // $res_x* (int) - The horizontal resolution in DPI
  // $res_y  (int) - The vertical resolution in DPI
  //
  // Returns a SimpleImage object.
  //
  public function resolution($res_x, $res_y = null) {
    if(is_null($res_y)) {
      imageresolution($this->image, $res_x);
    } else {
      imageresolution($this->image, $res_x, $res_y);
    }

    return $this;
  }

  //
  // Rotates the image.
  //
  // $angle* (int) - The angle of rotation (-360 - 360).
  // $backgroundColor (string|array) - The background color to use for the uncovered zone area
  //   after rotation (default 'transparent').
  //
  // Returns a SimpleImage object.
  //
  public function rotate($angle, $backgroundColor = 'transparent') {
    // Rotate the image on a canvas with the desired background color
    $backgroundColor = $this->allocateColor($backgroundColor);

    $this->image = imagerotate(
      $this->image,
      -(self::keepWithin($angle, -360, 360)),
      $backgroundColor
    );
    imagecolortransparent($this->image, imagecolorallocatealpha($this->image, 0, 0, 0, 127));

    return $this;
  }
	
	//
  // Creates a thumbnail image. This function attempts to get the image as close to the provided
  // dimensions as possible, then crops the remaining overflow to force the desired size. Useful
  // for generating thumbnail images.
  //
  //  $width* (int) - The thumbnail width.
  //  $height* (int) - The thumbnail height.
  //  $anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right', 'top left',
  //    'top right', 'bottom left', 'bottom right' (default 'center').
  //
  // Returns a SimpleImage object.
  //
  public function thumbnail($width, $height, $anchor = 'center') {
    // Determine aspect ratios
    $currentRatio = $this->getHeight() / $this->getWidth();
    $targetRatio = $height / $width;

    // Fit to height/width
    if($targetRatio > $currentRatio) {
      $this->resize(null, $height);
    } else {
      $this->resize($width, null);
    }

    switch($anchor) {
    case 'top':
      $x1 = floor(($this->getWidth() / 2) - ($width / 2));
      $x2 = $width + $x1;
      $y1 = 0;
      $y2 = $height;
      break;
    case 'bottom':
      $x1 = floor(($this->getWidth() / 2) - ($width / 2));
      $x2 = $width + $x1;
      $y1 = $this->getHeight() - $height;
      $y2 = $this->getHeight();
      break;
    case 'left':
      $x1 = 0;
      $x2 = $width;
      $y1 = floor(($this->getHeight() / 2) - ($height / 2));
      $y2 = $height + $y1;
      break;
    case 'right':
      $x1 = $this->getWidth() - $width;
      $x2 = $this->getWidth();
      $y1 = floor(($this->getHeight() / 2) - ($height / 2));
      $y2 = $height + $y1;
      break;
    case 'top left':
      $x1 = 0;
      $x2 = $width;
      $y1 = 0;
      $y2 = $height;
      break;
    case 'top right':
      $x1 = $this->getWidth() - $width;
      $x2 = $this->getWidth();
      $y1 = 0;
      $y2 = $height;
      break;
    case 'bottom left':
      $x1 = 0;
      $x2 = $width;
      $y1 = $this->getHeight() - $height;
      $y2 = $this->getHeight();
      break;
    case 'bottom right':
      $x1 = $this->getWidth() - $width;
      $x2 = $this->getWidth();
      $y1 = $this->getHeight() - $height;
      $y2 = $this->getHeight();
      break;
    default:
      $x1 = floor(($this->getWidth() / 2) - ($width / 2));
      $x2 = $width + $x1;
      $y1 = floor(($this->getHeight() / 2) - ($height / 2));
      $y2 = $height + $y1;
      break;
    }

    // Return the cropped thumbnail image
    return $this->crop($x1, $y1, $x2, $y2);
  }

}


function formatPhoneNumber($phoneNumber) {
    $phoneNumber = preg_replace('/[^0-9]/','',$phoneNumber);

    if(strlen($phoneNumber) > 10) {
        $countryCode = substr($phoneNumber, 0, strlen($phoneNumber)-10);
        $areaCode = substr($phoneNumber, -10, 3);
        $nextThree = substr($phoneNumber, -7, 3);
        $lastFour = substr($phoneNumber, -4, 4);

        $phoneNumber = '+'.$countryCode.' ('.$areaCode.') '.$nextThree.'-'.$lastFour;
    }
    else if(strlen($phoneNumber) == 10) {
        $areaCode = substr($phoneNumber, 0, 3);
        $nextThree = substr($phoneNumber, 3, 3);
        $lastFour = substr($phoneNumber, 6, 4);

        $phoneNumber = $areaCode.'-'.$nextThree.'-'.$lastFour;
    }
    else if(strlen($phoneNumber) == 7) {
        $nextThree = substr($phoneNumber, 0, 3);
        $lastFour = substr($phoneNumber, 3, 4);

        $phoneNumber = $nextThree.'-'.$lastFour;
    }

    return $phoneNumber;
}


/* Google App Client Id */
define('CLIENT_ID', '150842030586-dbcdngluufu1butil13oq7or9qq720qi.apps.googleusercontent.com');

/* Google App Client Secret */
define('CLIENT_SECRET', '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');

/* Google App Redirect Url */
define('CLIENT_REDIRECT_URL', 'http://localhost/account.php');


class GoogleCalendarApi
{
	public function GetAccessToken($client_id, $redirect_uri, $client_secret, $code) {	
		$url = 'https://accounts.google.com/o/oauth2/token';			
		
		$curlPost = 'client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&client_secret=' . $client_secret . '&code='. $code . '&grant_type=authorization_code';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		if($http_code != 200) 
			throw new Exception('Error : Failed to receieve access token');
			
		return $data;
	}

	public function GetUserCalendarTimezone($access_token) {
		$url_settings = 'https://www.googleapis.com/calendar/v3/users/me/settings/timezone';
		
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url_settings);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	
		$data = json_decode(curl_exec($ch), true); //echo '<pre>';print_r($data);echo '</pre>';
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		if($http_code != 200) 
			throw new Exception('Error : Failed to get timezone');

		return $data['value'];
	}

	public function GetCalendarsList($access_token) {
		$url_parameters = array();

		$url_parameters['fields'] = 'items(id,summary,timeZone)';
		$url_parameters['minAccessRole'] = 'owner';

		$url_calendars = 'https://www.googleapis.com/calendar/v3/users/me/calendarList?'. http_build_query($url_parameters);
		
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url_calendars);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	
		$data = json_decode(curl_exec($ch), true); //echo '<pre>';print_r($data);echo '</pre>';
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		if($http_code != 200) 
			throw new Exception('Error : Failed to get calendars list');

		return $data['items'];
	}

	public function CreateCalendarEvent($calendar_id, $summary, $all_day, $event_time, $event_timezone, $access_token) {
    // Initialize the Google Client
    $client = new Google_Client();
    $client->setApplicationName('Calendar API PHP');
    $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
    $client->setAuthConfig('vendor/credential.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    // Authorize the client
    $client->setAccessToken($access_token);
    // Create a new Google Calendar event
    $service = new Google_Service_Calendar($client);
    $event = new Google_Service_Calendar_Event([
      'summary' => $summary,
      'start' => [
        'dateTime' => $event_time['start_time'],
        'timeZone' => $event_timezone,
      ],
      'end' => [
        'dateTime' => $event_time['end_time'],
        'timeZone' => $event_timezone,
      ],
    ]);

    // Set the calendar ID where you want to create the event

    // Insert the event into the calendar
    $createdEvent = $service->events->insert($calendar_id, $event);

    // Print the event ID if successful
    if ($createdEvent) {
      $message = "Appointment Success!";

      echo '<script type="text/javascript">';
      echo 'alert("' . $message . '");';
      echo '</script>';
    }
    else {
        echo $createdEvent.'GHOST';
    }

		// $url_events = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendar_id . '/events';

		// $curlPost = array('summary' => $summary);
		// if($all_day == 1) {
		// 	$curlPost['start'] = array('date' => $event_time['event_date']);
		// 	$curlPost['end'] = array('date' => $event_time['event_date']);
		// }
		// else {
		// 	$curlPost['start'] = array('dateTime' => $event_time['start_time'], 'timeZone' => $event_timezone);
		// 	$curlPost['end'] = array('dateTime' => $event_time['end_time'], 'timeZone' => $event_timezone);
		// }

    // echo '<pre>';print_r($curlPost);echo '</pre>';
		// $ch = curl_init();		
		// curl_setopt($ch, CURLOPT_URL, $url_events);		
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		// curl_setopt($ch, CURLOPT_POST, 1);		
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token, 'Content-Type: application/json'));	
		// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));	
		// $data = json_decode(curl_exec($ch), true);

    // echo '<pre>';print_r($ch);echo '</pre>';
		// $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		// if($http_code != 200) 
		// 	throw new Exception('Error : Failed to create event');

		// return $data['id'];
	}

  public function GetCalendarEvents( $access_token )
  {
    
    // Set up the client
    $client = new Google_Client();
    $client->setApplicationName('Calendar API PHP');
    $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
    $client->setAuthConfig('vendor/credential.json'); // Path to your client credentials JSON file
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setAccessToken($access_token);

    // Create a new instance of the Calendar service
    $service = new Google_Service_Calendar($client);

    // Specify the calendar ID (can be the primary calendar or any other calendar you have access to)
    $calendarId = 'primary';

    // Set the time range for the events you want to retrieve
    $optParams = array(
        'timeMin' => date('c'), // Starting from current time
        'maxResults' => 250, // Number of events to retrieve
        'orderBy' => 'startTime',
        'singleEvents' => true
    );

    // Call the API to retrieve the events
    $results = $service->events->listEvents($calendarId, $optParams);
    $events = $results->getItems();

    // Process the events
    if (empty($events)) {
    } else {
      $items = array();
      foreach ($events as $event) {
        $start = $event->start->dateTime ?? $event->start->date;
        $end = $event->end->dateTime ?? $event->end->date;

        $start = substr($start, 0, strlen($start)-6);
        $end = substr($end, 0, strlen($end)-6);
        $date = substr($start, 0, 10);
        // echo $start."before";
        $start = substr($start, 11, strlen($start));
        // echo $start."after";
        $end = substr($end, 11, strlen($end));
        $title = $event->getSummary();
        $item = array(
          'title' => $title,
          'date' => $date,
          'start' => $start,
          'end' => $end
        );

        $items[] = $item;
      }
    }
    return $items;
  }
}
?>
