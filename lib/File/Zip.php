<?php

namespace Lum\File;

/**
 * A wrapper around the ZipArchive class that uses custom Exceptions
 * for error handling instead of returning numeric codes.
 */
class Zip
{
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
   * @param string $filename  The path to the file you want to create.
   * @param bool $overwrite=false  Should we overwrite the file?
   */
  public static function create ($filename, $overwrite=false)
  {
    $mode = $overwrite 
      ? (\ZipArchive::CREATE | \ZipArchive::OVERWRITE)
      : (\ZipArchive::CREATE | \ZipArchive::EXCL);
    $zip = new \ZipArchive();
    $res = $zip->open($filename, $mode);
    if ($res !== true)
    {
      static::zip_error("Could not create zip", $res);
    }
    return $zip;
  }

  /**
   * Open a Zip file.
   *
   * @param string $filename  The path to the file you want to open.
   * @param mixed $mode=null  If this is an integer, it's ZipArchive flags.
   *                          If it is boolean true, add ZipArchive::CREATE.
   *                          Anything else will default to mode 0 (no flags.)
   */
  public static function open ($filename, $mode=null)
  {
    if (is_bool($mode) && $mode)
    {
      $mode = \ZipArchive::CREATE;
    }
    elseif (!is_int($mode))
    {
      $mode = 0;
    }
    $zip = new \ZipArchive();
    $res = $zip->open($filename, $mode);
    if ($res != true)
    {
      static::zip_error("Could not open zip", $res);
    }
    return $zip;
  }
}

class ZipException extends \Exception
{
  protected $why_msg = 'unknown error occurred';

  public function __construct ($message="", $code=0, \Throwable $previous=null)
  {
    $message = $message . ': ' . $this->why_msg;
    parent::__construct($message, $code, $previous);
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
