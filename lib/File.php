<?php

namespace Lum;
use finfo;
use Lum\File\CSV;
use Lum\File\Stream;
use Lum\File\Zip;
use Lum\File\Permissions;

/**
 * A class representing a file.
 *
 * Has a whole bunch of wrappers for dealing with file uploads,
 * CSV files, and Zip files. 
 */
class File
{
  const MODE_RO = 'r';
  const MODE_RW = 'r+';
  const MODE_READ = self::MODE_RO;
  const MODE_READ_WRITE = self::MODE_RW;
  const MODE_WRITE = 'w';
  const MODE_WRITE_READ = 'w+';
  const MODE_APPEND = 'a';
  const MODE_APPEND_READ = 'a+';
  const MODE_NEW = 'x';
  const MODE_NEW_READ = 'x+';
  const MODE_CREATE = 'c';
  const MODE_CREATE_READ = 'c+';

  const MODE_BINARY = 'b';       // $flags & Stream::BINARY
  const MODE_TRANSLATE = 't';    // $flags & Stream::TRANSLATE
  const MODE_NONBLOCKING = 'n';  // $flags & Stream::NONBLOCKING
  const MODE_CLOSE_EXEC = 'e';   // $flags & Stream::CLOSE_EXEC

  const ZIP_NONE   = 0;  // Use non-strict mode to open zip files.
  const ZIP_STRICT = 1;  // Use strict mode to open zip files.
  const ZIP_WRAP   = 2;  // Return a File\Zip wrapper instead of ZipArchive.

  const UNKNOWN_MIME = 'application/octet-stream';

  const FMT_DECIMAL = 0; // Raw decimal integers
  const FMT_HEX     = 1; // Hexidecimal encoded
  const FMT_BASE64  = 2; // Base64 encoded, with trailing = characters trimmed.

  /**
   * A static property to determine the default format
   * for the random names generated by the tempdir() method.
   */
  public static $RANDOM_FORMAT = self::FMT_BASE64;

  /**
   * A static property to determine the default format for getZip()
   */
  public static $ZIP_MODE = self::ZIP_WRAP;

  /**
   * The name of the file.
   */
  public $name;

  /**
   * Mime type, if any.
   */
  public $type;

  /**
   * The size of the file.
   */
  public $size;

  /**
   * The filename on the system.
   */
  public $file;

  /**
   * Set this to a valid text encoding ('UTF-8', 'UTF-16', etc.) if you
   * know the encoding of the file before hand. Otherwise auto-detection
   * of the encoding will be used.
   */
  public $encoding;

  /**
   * The mode to set created directories to.
   */
  public $dirMode = 0755;

  /**
   * The default flags for all Stream related methods.
   *
   * Default value: 0 (no flags)
   */
  public $streamFlags = 0;

  /**
   * The default stream mode when using openStream()
   *
   * Default value: 'r'
   */
  public $openStreamMode = self::MODE_RO;

  /**
   * The default stream mode when using getStream()
   *
   * Default value: null
   */
  public $getStreamMode = null;
  
  /**
   * Build a new File object.
   */
  public function __construct ($file=Null)
  {
    if (isset($file))
    {
      $this->file = $file;
      $this->name = basename($file);
      if (file_exists($file))
      {
        $this->size = filesize($file);
        $this->type = static::detectMimeType($file);
      }
    }
  }

  public static function detectMimeType ($file)
  {
    $finfo = new finfo(FILEINFO_MIME);
    return $finfo->file($file);
  }

  /**
   * See if a standard HTTP upload of a set name exists.
   */
  public static function hasUpload ($name, $context=null)
  {
    if (isset($context))
    {
      if (isset($context->files[$name]) 
        && $context->files[$name]['error'] === UPLOAD_ERR_OK)
      {
        return True;
      }
      return False;
    }
    if (isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK)
    {
      return True;
    }
    return False;
  }

  /**
   * Utility function used by getUpload/getUploads.
   */
  protected static function makeUploadFile ($file)
  {
    if ($file['error'] === UPLOAD_ERR_OK)
    {
      $class = __CLASS__;
      $mime = ($file['type'] != self::UNKNOWN_MIME) 
        ? $file['type']
        : static::detectMimeType($file['tmp_name']);
      $upload = new $class();
      $upload->name = $file['name'];
      $upload->type = $mime;
      $upload->size = $file['size'];
      $upload->file = $file['tmp_name'];
      return $upload;
    }
    return $file['error'];
  }

  /**
   * Create a File object from a standard HTTP upload.
   *
   * @param string $name  The name of the upload field.
   * @param RouteContext $context  Optional: the current RouteContext object.
   *
   * @return mixed
   *
   * Returned value will be a File object if the upload was valid,
   * the PHP upload error code if the upload failed,
   * or null if the upload does not exist in the context, or was not
   * a single upload (use getUploads() for multiple files.)
   */
  public static function getUpload ($name, $context=null)
  {
    $files = isset($context) ? $context->files : $_FILES;
    if (isset($files, $files[$name], $files[$name]['error'])
      && is_scalar($files[$name]['error']))
    {
      return static::makeUploadFile($files[$name]);
    }
    return Null;
  }

  /**
   * Convert PHP's array style upload syntax into something more sane.
   *
   * PHP's multiple upload array syntax is weird:
   *
   * [
   *   "name" => 
   *   [
   *     "foo.txt",
   *     "bar.txt"
   *   ],
   *   "tmp_name" => 
   *   [
   *     "/tmp/file1", 
   *     "/tmp/file2"
   *   ],
   *   "error" => 
   *   [
   *     UPLOAD_ERR_OK, 
   *     UPLOAD_ERR_OK
   *   ],
   *   "type" => 
   *   [
   *     "text/plain", 
   *     "text/plain"
   *   ],
   *   "size" => 
   *   [
   *     123,
   *     456
   *   ],
   * ]
   *
   *  This converts that structure to this:
   *
   *  [
   *    [
   *      "name" => "foo.txt",
   *      "tmp_name" => "/tmp/file1",
   *      "error" => UPLOAD_ERR_OK,
   *      "type" => "text/plain",
   *      "size" => 123,
   *    ],
   *    [
   *      "name" => "bar.txt",
   *      "tmp_name" => "/tmp/file2",
   *      "error => UPLOAD_ERR_OK,
   *      "type" => "text/plain",
   *      "size" => 456,
   *    ],
   *  ]
   *
   * @param array $uploadArray  A PHP upload array to convert.
   * @return array  A sane upload array.
   */
  public static function reorderUploadArray ($uploadArrary)
  {
    $outputArray = [];
    foreach ($uploadArray as $key1 => $val1)
    {
      foreach ($val1 as $key2 => $val2)
      {
        $outputArray[$key2][$key1] = $val2;
      }
    }
    return $outputArray;
  }

  /**
   * Create an array of File objects from an array style multiple upload.
   *
   * Each element in the array will be a File object if the upload was
   * valid, or the PHP upload error code if the upload failed.
   *
   * If the upload was not found in the context, an empty array will be
   * returned.
   */
  public static function getUploads ($name, $context=null)
  {
    $uploads = [];
    $files = isset($context) ? $context->files : $_FILES;
    if (isset($files, $files[$name]))
    {
      if (is_array($files[$name]['error']))
      { // It's an array, let's do the thing.
        $uploadArray = static::reorderUploadArray($files[$name]);
        foreach ($uploadArray as $key => $file)
        {
          $uploads[$key] = static::makeUploadFile($file);
        }
      }
      elseif (is_scalar($files[$name]['error']))
      { // No array was used. Add the single file.
        $uploads[] = static::makeUploadFile($files[$name]);
      }
    }
    return $uploads;
  }

  /**
   * See if the current file exists.
   */
  public function exists ()
  {
    return (isset($this->file) && file_exists($this->file));
  }

  /**
   * Delete the file.
   */
  public function delete ($recursive=false)
  {
    if ($recursive)
    { // This can remove entire directory trees.
      return self::rmtree($this->file);
    }
    else
    { // This cannot, and doesn't work on directories.
      return unlink($this->file);
    }
  }

  /**
   * Save the file to the specified folder (with it's original filename.)
   *
   * @param string $folder  The folder we are saving the file to.
   * @param bool $move  Move the file (default false).
   * @param bool $update  Update the file object (default true).
   *
   * @return {string|bool}  The new full path to the file if the save worked,
   *                        or false otherwise.
   */
  public function saveTo ($folder, $move=false, $update=true)
  { 
    $target = $folder . '/' . $this->name;
    return $this->saveAs($target, $move, $update);
  }

  /**
   * Save the file to the specified filename.
   *
   * The full path will be created if it does not exist.
   *
   * @param string $target  Where we want to save the file.
   * @param bool $move  Move the file (default true).
   * @param bool $update  Update the file object (default true).
   *
   * @return {string|bool}  The new full path to the file if the save worked,
   *                        or false otherwise.
   */
  public function saveAs ($target, $move=true, $update=true)
  {
#    error_log("saveAs($target,".json_encode($move).",".json_encode($update).")");
#    error_log("file: ".$this->file);
    if (!file_exists($this->file))
    {
      return false;
    }
    $target_dir = dirname($target);
    if (!is_dir($target_dir))
    {
#      error_log("directory '$target_dir' does not exist?");
      mkdir($target_dir, $this->dirMode, true);
      chmod($target_dir, $this->dirMode);
    }
    if (copy($this->file, $target))
    {
#      error_log("copied");
      if ($move)
      {
        unlink($this->file);    // Delete the old one.
      }
      if ($update)
      {
        $this->file = $target;           // Change our file pointer.
        $this->name = basename($target); // Change our file name.
      }
      return $target;         // And return the new name.
    }
    return False;
  }

  /**
   * Make a copy of the file, without changing the original.
   *
   * @param string $target  The target path of the copy we are making.
   *
   * @return bool  Was the copy successful?
   */
  public function copyTo ($target)
  {
    $copy = $this->saveAs($target, false, false);
    return $copy == $target;
  }

  /**
   * Rename the file.
   *
   * @param string $newname  The new name of the file.
   *
   * @return bool  Was the file renamed successfully?
   */
  public function rename ($newname)
  {
    if (rename($this->file, $newname))
    {
      $this->file = $newname;
      return True;
    }
    return False;
  }

  /**
   * Update the file size.
   *
   * Use this if you have modified the file.
   */
  public function update_size ()
  {
    $this->size = filesize($this->file);
  }

  /**
   * Return the file contents as a string.
   *
   * @param bool $forceUTF8  Force the output to be UTF-8 (default false).
   *
   * @return string  The file contents (converted if $forceUTF8 was true.)
   *
   * @throws Exception  If forceUTF8 was true but we could not detect the
   *                    encoding, an Exception will be thrown.
   */
  public function getString ($forceUTF8=false)
  {
    $string = file_get_contents($this->file);
    if ($forceUTF8)
    {
      if (isset($this->encoding))
      { // Use the manually specified encoding.
        $encoding = $this->encoding;
      }
      else
      { // Try to detect the encoding.
        $bom = substr($string, 0, 2);
        if ($bom === chr(0xff).chr(0xfe) || $bom === chr(0xfe).chr(0xff))
        { // UTF-16 Byte Order Mark found.
          $encoding = 'UTF-16';
        }
        else
        {
          $encoding = mb_detect_encoding($string, 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1', true);
        }
      }
      if ($encoding)
      {
        if ($encoding != 'UTF-8')
        {
          $string = mb_convert_encoding($string, 'UTF-8', $encoding);
        }
      }
      else
      {
        throw new \Exception("Unsupported document encoding found.");
      }
    }
    return $string;
  }

  /**
   * Update the file contents from a string.
   *
   * No conversion is done of the passed contents.
   *
   * @param mixed $data  The string (or blob) we are writing the the file.
   * @param array $opts  (Optional) Named options:
   *
   * 'append' (bool)  If true, use append mode instead of overwrite mode.
   * 'lock'   (bool)  If true, aquire a lock before writing.
   *
   * @return {int|bool}  The number of bytes written or false if error.
   */
  public function putString ($data, $opts=[])
  {
    $flags = 0;
    if (isset($opts['append']) && $opts['append'])
    {
      $flags = FILE_APPEND;
    }
    if (isset($opts['lock']) && $opts['lock'])
    {
      $flags = $flags | LOCK_EX;
    }
    $count = file_put_contents($this->file, $data, $flags);
    if ($count !== False)
    {
      $this->update_size();
    }
    return $count;
  }

  /**
   * Parse a Delimiter Seperated Values file.
   *
   * @param array $opts  See CSV::parse() for a list of valid options.
   *
   * One added option is 'utf8' which defaults to true, and is passed to
   * getString() to determine if we should force UTF-8 strings. You probably
   * shouldn't change this unless you know what you are doing.
   *
   * @param bool $useTabs=false  A short cut to adding ['tabs'=>true] option.
   *
   * @return array  The parsed CSV data.
   */
  public function getDelimited ($opts=[], $useTabs=false)
  {
    $forceutf8 = isset($opts['utf8']) ? $opts['utf8'] : true;
    if ($useTabs && !isset($opts['tabs']))
    {
      $opts['tabs'] = true;
    }
    $string = $this->getString($forceutf8);
    $rows = CSV::parse($string, $opts);
    return $rows;
  }

  /**
   * Read the contents of the file into an array.
   */
  public function getArray ()
  {
    return file($this->file);
  }

  /**
   * Put the contents of an array into the file.
   *
   * This is an alias of putString(), since it handles mixed data.
   */
  public function putArray ($data, $opts=[])
  {
    return $this->putString($data, $opts);
  }

  /**
   * Return a PHP stream resource for the file.
   *
   * @param string $mode  Mode to open the stream (default: $openStreamMode)
   * @param int $flags  Flags to use (default: $streamFlags)
   *
   * @return resource  The PHP stream resource.
   */
  public function openStream ($mode=null, $flags=null)
  {
    if (!is_string($mode))
      $mode = $this->openStreamMode;
    if (!is_int($flags))
      $flags = $this->streamFlags;

    if ($flags & Stream::BINARY)
      $mode .= self::MODE_BINARY;
    if ($flags & Stream::TRANSLATE)
      $mode .= self::MODE_TRANSLATE;
    if ($flags & Stream::NONBLOCKING)
      $mode .= self::MODE_NONBLOCKING;
    if ($flags & Flags::CLOSE_EXEC)
      $mode .= self::MODE_CLOSE_EXEC;

    return fopen($this->file, $mode);
  }

  /**
   * Return a File\Stream object representing this file.
   *
   * @param string $mode  Mode to open the stream.
   *                      If null, call to open() must be done manually.
   *                      Default: $getStreamMode
   * @param int $flags    Flags to add (default $streamFlags)
   *
   * @return Lum\File\Stream
   */
  public function getStream ($mode=null, $addBin=null)
  {
    return new Stream($this, $mode, $addBin);
  }

  /**
   * Return a FileStream object in read mode.
   *
   * @param bool $bin  Use binary mode (default: true)
   *
   * @return FileStream
   */
  public function getReader ($bin=true)
  {
    return $this->getStream('r', $bin);
  }

  /**
   * Return a FileStream object in write mode.
   *
   * @param bool $bin  Use binary mode (default: true)
   *
   * @return FileStream
   */
  public function getWriter ($bin=true)
  {
    return $this->getStream('w', $bin);
  }

  /**
   * Return a FileStream object in append mode.
   *
   * @param bool $bin  Use binary mode (default: true)
   *
   * @return FileStream
   */
  public function getLogger ($bin=true)
  {
    return $this->getStream('a', $bin);
  }

  /**
   * Get the contents using a resource stream.
   *
   * You probably don't need this, getString() works for just about everything.
   */
  public function getContents ($bin=true)
  {
    $handle   = $this->openStream('r', $bin);
    $contents = fread($handle, $this->size);
    fclose($handle);
    return $contents;
  }

  /**
   * Put the contents using a resource stream.
   *
   * You probably don't need this, putString() works for just about everything.
   */
  public function putContents ($data, $bin=true)
  {
    $handle = $this->openStream('w', $bin);
    $count = fwrite($handle, $data);
    fclose($handle);
    if ($count !== False)
    {
      $this->update_size();
    }
    return $count;
  }

  /**
   * Open a zip file.
   *
   * @param int|bool $zipMode=ZIP_WRAP    Mode to use to open the zip file.
   *                                      May be one of the class constants:
   *
   *                                      ZIP_NONE   = Use non-strict mode.
   *                                      ZIP_STRICT = Use strict mode.
   *                                      ZIP_WRAP   = Return a Zip object
   *                                                   wrapping the ZipArchive.
   *                                                   This always uses strict.
   * 
   *                                      It may also be a boolean value:
   *
   *                                      true       = Use strict mode.
   *                                      false      = Use non-strict mode.
   *
   * @param mixed $openMode=null  Mode to pass to open().
   *
   * @return mixed  If wrap mode is on, will return a File\Zip instance.
   *                If strict mode is on, will return a ZipArchive instance.
   *                If strict mode is false, it may also return an error code.
   *
   * @throws Exception  If strict mode or wrap mode is used and a failure 
   *                    occurs, an Exception will be thrown, see Lum\File\Zip 
   *                    for a list of possible Exceptions that might be thrown.
   */
  public function getZip ($zipMode=null, $openMode=null)
  {
    if (!isset($zipMode))
    { // Null means use the default mode.
      $zipMode = static::$ZIP_MODE;
    }
 
    if (is_bool($zipMode))
    { // One of the simple non-wrap modes was selected.
      $strict = $zipMode;
      $useWrapper = false;
    }
    else
    { // An integer was passed, or something invalid.
      $useWrapper = ($zipMode === self::ZIP_WRAP);
      $strict = ($zipMode !== self::ZIP_NONE);
    }

    if ($useWrapper)
    { // Return a File\Zip instance.
      return new Zip($this->file, $openMode);
    }
    else
    { // Return a ZipArchive instance.
      return Zip::open($this->file, $openMode, $strict);
    }
  }

  /**
   * Extract a zip file to a temporary folder and return the path.
   *
   * @param string $prefix='zipfile_'  Temp name prefix.
   *
   * @param bool $strict=true  Should we use strict mode?
   *                           Passed to getZip() as the $zipMode property.
   *
   *                           While technically you could pass an int or
   *                           null here as well, just use a boolean, or
   *                           better yet, don't change it at all and use the
   *                           default version as it just plain works.
   *
   * @return mixed  If a 'string' it's the path to the temporary folder.
   *                If an int, it's the error code from getZip().
   *                If null, the zip could not be extracted.
   */
  public function getZipDir ($prefix='zipfile_', $strict=true)
  {
    $zipfile  = $this->getZip($strict);

    if (!($zipfile instanceof \ZipArchive || $zipfile instanceof Zip))
    { // Not a valid object instance.
      return $zipfile;
    }

    $tempdir = static::tempdir(null, $prefix);

    if (is_dir($tempfile))
    {
      $zipfile->extractTo($tempfile);
      return $tempfile;
    }
  }

  /**
   * Convert a size in bytes to a friendly string.
   *
   * @param  number  $size     The size in bytes you want to convert.
   * @param  int     $prec     Precision for result (default: 2).
   * @param  array   $types    An array of strings to append for each type.
   *                 Default:  [' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB']
   *
   * @return string  The friendly size (e.g. "4.4 MB")
   */
  public static function filesize_str ($size, $prec=2, $types=null)
  {
    if (is_numeric($size))
    {
      $decr = 1024;
      $step = 0;
      if (!is_array($types))
      {
        $types = array(' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB');
      }
      while (($size / $decr) > 0.9)
      {
        $size = $size / $decr;
        $step++;
      }
      return round($size, $prec) . $type[$step];
    }
  }

  /**
   * Returns the friendly string representing our own file size.
   *
   * Uses filesize_str() to generate the string.
   *
   * @param int    $prec   Precision for result (default: 2)
   * @param array  $types  An array of strings to append for each type.
   *                       See filesize_str() for default value.
   *
   * @return string         The friendly size.
   */
  public function fileSize ($prec=2, $types=null)
  {
    return $this->filesize_str($this->size, $prec, $types);
  }

  /**
   * Returns the file stats.
   */
  public function stats ()
  {
    return stat($this->file);
  }

  /**
   * Return a DateTime object or formatted string from a stat field.
   *
   * @param  string  $field   The stat field ('atime','mtime','ctime').
   * @param  string  $format  Optional, a valid DateTime format string.
   *
   * @return mixed  If you passed $format this returns a string.
   *                If you didn't it returns a DateTime object.
   *                Returns null if the field does not exist.
   */
  public function getTime ($field, $format=null)
  {
    $stats = $this->stats();
    if (isset($stat[$field]))
    {
      $modtime  = $stat[$field];
      $datetime = new DateTime("@$modtime");
      if (isset($format) && is_string($format))
      {
        return $datetime->format($format);
      }
      else
      {
        return $datetime;
      }
    }
  }

  /**
   * Return the time when the file was last modified.
   *
   * @param   string  $format   Optional, a valid DateTime format string.
   *
   * See getTime() for valid return values.
   */
  public function modifiedTime ($format=null)
  {
    return $this->getTime('mtime', $format);
  }

  /**
   * Recursively remove an entire directory tree.
   */
  public static function rmtree ($path)
  { 
    if (is_dir($path))
    {
      foreach (scandir($path) as $name)
      {
        if (in_array($name, array('.', '..'))) continue;
        $subpath = $path.DIRECTORY_SEPARATOR.$name;
        if (!self::rmtree($subpath))
        { // Something failed.
          return false;
        }
      }
      return rmdir($path);
    }
    else
    {
      return unlink($path);
    }
  }

  /**
   * Create a temporary directory.
   *
   * Similar to tempnam() but instead of creating a temp file, creates
   * a temporary directory instead. It's still up to you to clean it up,
   * but you can use rmtree() to do that.
   *
   * @param string|null $root=null  The root path for the temporary directory.
   *                                If null it will use sys_get_temp_dir();
   * @param string $prefix=''       A prefix for the temporary directory name.
   *
   * @param int $mode=0700          Permissions to set, prefer octal format.
   *                                Not sure what this does on Windows systems,
   *                                as I run all my PHP scripts on Linux/Unix.
   *
   * @param bool $format=FMT_BASE64 Format of the random portion of the string.
   *                                Use one of the constants in this class:
   *
   *                                FMT_DECIMAL = Decimal integer value.
   *                                FMT_HEX     = Hex value.
   *                                FMT_BASE64  = Base64 value.
   *
   *                                If base64 is used, any '=' characters will
   *                                be stripped from the end of the string.
   *
   *                                You can also set File::$RANDOM_FORMAT to
   *                                one of those constants and it will be used
   *                                as the new default format.
   *
   * @param int $min=1024           Minimum value for mt_rand().
   * @param int $max=null           Max value for mt_rand(). If null will use
   *                                the mt_getrandmax() value.
   *
   * @return string  The path to the temporary directory that was created.
   */
  public static function tempdir($root=null, $prefix='', $mode=0700,
    $format=null, $min=1024, $max=null)
  {

    if (!is_string($root) || trim($root) == '')
    { // The root must be a non-empty directory. 
      $root = sys_get_temp_dir();
    }

    if (substr($root, -1) != '/') $root .= '/'; // Make sure it ends in /

    if (!file_exists($root) || !is_writable($root))
    {
      throw new \Exception("Invalid root directory '$root' in tempdir()");
    }

    if ($min < 0)
    { // Minimum cannot be less than zero.
      $min = 0;
    }
    elseif ($min > mt_getrandmax()-1024)
    { // Minimum cannot be greater than 1024 less than mt_getrandmax()
      $min = mt_getrandmax()-1024;
    }

    if (!is_int($max) || $max < $min+1024)
    { // Max must be an integer, and must be at least 1024 higher than min.
      $max = mt_getrandmax();
    }

    if (!is_int($format) || $format > self::FMT_BASE64)
    { // No valid format, use the default.
      $format = static::$RANDOM_FORMAT;
    }

    do
    {
      $rand = mt_rand($min, $max);

      switch ($format)
      { // Figure out what format we want the random portion to be in.
        case (self::FMT_BASE64):
          $rand = rtrim(base64_encode($rand), '=');
          break;
        case (self::FMT_HEX):
          $ran = dechex($rand);
          break;
        case (self::FMT_DECIMAL):
          // It's all good, nothing to see here.
          break;
        default:
          // Should never reach here, but just in case.
          throw new \Exception("Invalid format '$format' in tempdir()");
      } // switch format

      $path = $root.$prefix.$rand;

    } while (!mkdir($path, $mode));

    // If we reached here, the path was created.
    return $path;
  }

}

