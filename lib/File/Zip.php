<?php

namespace Lum\File;

/**
 * A wrapper around the ZipArchive class that uses custom Exceptions
 * for error handling instead of returning numeric codes.
 */
class Zip
{
  protected $zipArchive; // If using this as a wrapper, the real ZipArchive.

  /**
   * Build a Zip wrapper.
   *
   * Wraps a ZipArchive object with a few extra features.
   * When building a Zip instance, the 'strict mode' is always used in open().
   *
   * @param {ZipArchive|string} $file  The path to a zip file, or a ZipArchive.
   * @param mixed $mode  The mode to pass to open() if $file was a string.
   *
   */
  public function __construct($file, $mode=null)
  {
    if (is_object($file) && $file instanceof \ZipArchive)
    { // A ZipArchive object was passed.
      $this->zipArchive = $opts;
    }
    elseif (is_string($file))
    { // We're counting it as a path name, let's open it.
      $this->zipArchive = static::open($file, $mode);
    }
    else
    { // That's not valid.
      throw new ZipInternalException("Invalid file parameter passed");
    }
  }

  public static function zip_error ($msg, $code)
  {
    $ns = '\Lum\File';
    $codes =
    [
      \ZipArchive::ER_EXISTS => "$ns\\ZipFileExistsException",
      \ZipArchive::ER_INCONS => "$ns\\ZipInconsistencyException",
      \ZipArchive::ER_INVAL  => "$ns\\ZipInvalidArgumentException",
      \ZipArchive::ER_MEMORY => "$ns\\ZipMemoryException",
      \ZipArchive::ER_NOENT  => "$ns\\ZipNoFileException",
      \ZipArchive::ER_NOZIP  => "$ns\\ZipNotZipException",
      \ZipArchive::ER_OPEN   => "$ns\\ZipOpenException",
      \ZipArchive::ER_READ   => "$ns\\ZipReadException",
      \ZipArchive::ER_SEEK   => "$ns\\ZipSeekException",
    ];
    if (isset($codes[$code]))
    {
      $exception = $codes[$code];
    }
    else
    {
      $exception = "$ns\\ZipException";
    }
    throw new $exception($msg, $code);
  }

  /**
   * Create a new Zip file.
   *
   * This is simply a wrapper for open() now, just with the $mode set to
   * only one of the boolean values (true for overwrite, false for exclusive.)
   * The 'strict' mode is always on when using this method.
   *
   * @param string $filename  The path to the file you want to create.
   * @param bool $overwrite=false  Should we overwrite the file?
   *
   * @return ZipArchive  The ZipArchive object, assuming it was opened.
   *
   * This method will always throw an appropriate ZipException on a failure.
   */
  public static function create (string $filename, bool $overwrite=false)
  {
    return static::open($filename, $overwrite);
  }

  /**
   * Open a Zip file.
   *
   * @param string $filename  The path to the file you want to open.
   *
   * @param mixed $mode=null  If this is an integer, it's ZipArchive flags.
   *
   *                          If it's boolean true: (CREATE | OVERWRITE);
   *                          If it's boolean false: (CREATE | EXCL);
   *
   *                          If it's null (or any unrecognized value), the
   *                          default of \ZipArchive::RDONLY will be used if
   *                          supported, otherwise 0 will be used.
   *
   * @param bool $strict=true If true (the default), throw a ZipException on
   *                          a failure to open the archive. 
   *                          If false, we will return the result from 
   *                          ZipArchive->open() directly.
   *
   * @return ZipArchive|int   The ZipArchive file, assuming it was opened.
   *
   *                          The only time this will return an int is if
   *                          the $strict parameter was set to false and an
   *                          error occurred when trying to open the file.
   */
  public static function open ($filename, $mode=null, $strict=true)
  {
    if (is_bool($mode))
    { // Boolean modes are various versions of CREATE.
      $mode = \ZipArchive::CREATE;
      $mode = $overwrite 
        ? (\ZipArchive::CREATE | \ZipArchive::OVERWRITE)
        : (\ZipArchive::CREATE | \ZipArchive::EXCL);
      $what = 'create';
    }
    elseif (is_int($mode))
    { // It's a set of mode flags as per the ZipArchive specs.
      $what = ($mode & \ZipArchive::CREATE) ? 'create' : 'open';
    }
    else
    { // Anything else we'll assume read-only open.
      $what = 'open';
      if (defined('ZipArchive::RDONLY'))
      { // The RDONLY flag is defined.
        $mode = \ZipArchive::RDONLY;
      }
      else
      { // No RDONLY flag, use the old default of 0 (no flags).
        $mode = 0;
      }
    }

    $zip = new \ZipArchive();
    $res = $zip->open($filename, $mode);
    if ($res !== true)
    {
      if ($strict)
        static::zip_error("Could not $what zip file", $res);
      else
        return $res;
    }
    return $zip;
  }

  // Now for class instance methods.


  /**
   * Get a list of files contained in the zip archive.
   *
   * @param bool $justNames=false  If set to true, return an array of names.
   *                               Otherwise it returns an array of stat info
   *                               associative arrays. See the statIndex()
   *                               method in ZipArchive for details on that.
   *
   * @return array  The list of files. Format depends on $justNames option.
   *
   */
  public function listFiles($justNames=false)
  {
    $list = [];
    for ($i=0; $i < $this->zipArchive->numFiles; $i++)
    {
      $info = $this->zipArchive->statIndex($i);
      if ($justNames)
        $list[] = $info['name'];
      else
        $list[] = $info;
    }
    return $list;
  }

  /**
   * Add an entire directory to the zip file.
   *
   * The current zip file must have been opened in a mode capable of writing
   * to use this. The default readonly mode is well, read-only and this will
   * fail if you try to use it.
   *
   * @param string $folder         The path to the directory to add to the zip.
   *
   * @param bool $addToRoot=false  If true, add the contents of the folder
   *                               to the root of the zip file. Otherwise
   *                               add the folder itself as a subfolder in
   *                               the zip file. The latter is the default.
   */
  public function addDir ($folder, $addToRoot=false)
  {
    if ($addToRoot)
    { // Add the files in this folder directly to the root of the zip.
      $folder = rtrim($folder, '/'); // Strip any trailing slash.
      $len = strlen("$folder/");     // Get the length to strip from paths.
    }
    else
    { // Add the folder itself to the zip.
      $pi = pathinfo($folder);
      $len = strlen($pi['dirname'].'/');
      $this->zipArchive->addEmptyDir($pi['basename']);
    }
    $this->add_directory_contents($folder, $len);
  }

  // The recursive method that actually adds the folder contents.
  // This and the addDir() method were adapted from umbalaconmeogia's note:
  // https://www.php.net/manual/en/class.ziparchive.php#110719
  //
  // Modified a bit to support the addToRoot mode, and made into an instance
  // method instead of a static function.
  protected function add_directory_contents ($folder, $len)
  {
    $handle = opendir($folder);
    while (false !== $f = readdir($handle))
    {
      if ($f != '.' && $f != '..')
      {
        $fpath = "$folder/$f";
        $lpath = substr($fpath, $len); // Remove the prefix.
        if (is_file($fpath))
        { // It's a file, add it to the zip.
          $this->zipArchive->addFile($fpath, $lpath);
        }
        elseif (is_dir($fpath))
        { // It's a sub-folder, add it recursively.
          $this->zipArchive->addEmptyDir($lpath);
          $this->add_directory_contents($fpath, $len);
        }
      }
    }
    closedir($handle);
  } 

  /**
   * Proxy unknown instance methods to the underlying zipArchive.
   */
  public function __call ($method, $args)
  {
    if (isset($this->zipArchive))
    {
      $callable = [$this->zipArchive, $method];
      if (is_callable($callable))
      { // Let's do this!
        return call_user_func_array($callable, $args);
      }
      else
      { // No such method? We have an Exception for that.
        throw new ZipInvalidMethodException($method); 
      }
    }
    else
    { // No zipArchive set? That shouldn't be possible, but...
      throw new ZipInternalException("No zipArchive property set");
    }
  }

} // class Zip

class ZipException extends \Exception
{
  protected $why_msg = 'unknown error occurred';

  protected function get_zip_err (string $message)
  {
    return $message . ': ' . $this->why_msg;
  }

  public function __construct ($message="", $code=0, \Throwable $previous=null)
  {
    parent::__construct($this->get_zip_err($message), $code, $previous);
  }
}

class ZipFileExistsException extends ZipException
{
  protected $why_msg = 'file exists';
}

class ZipInconsistencyException extends ZipException
{
  protected $why_msg = 'archive inconsistency';
}

class ZipInvalidArgumentException extends ZipException
{
  protected $why_msg = 'invalid argument';
}

class ZipMemoryException extends ZipException
{
  protected $why_msg = 'malloc failure';
}

class ZipNoFileException extends ZipException
{
  protected $why_msg = 'no such file';
}

class ZipNotZipException extends ZipException
{
  protected $why_msg = 'not a zip archive';
}

class ZipOpenException extends ZipException
{
  protected $why_msg = 'could not open file';
}

class ZipReadException extends ZipException
{
  protected $why_msg = 'error reading file';
}

class ZipSeekException extends ZipException
{
  protected $why_msg = 'seek error';
}

class ZipInvalidMethodException extends ZipException
{
  protected $why_msg = 'method does not exist or is not callable';
}

class ZipInternalException extends ZipException
{
  protected $why_msg = __CLASS__.' internal error';

  // We're reversing the order from the ZipException.
  protected function get_zip_err (string $message)
  {
    return $this->why_msg . ': ' . $message;
  }
}
