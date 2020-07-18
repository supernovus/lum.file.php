<?php

namespace Lum\File;

/**
 * A class for handling Google/Android's MVImage format.
 */
class MVImage
{
  protected $mvData; // The raw MVImage data.

  /**
   * Create a new MVImage instance from a Lum\File instance.
   */
  public static function fromFile (\Lum\File $file)
  {
    $mvData = $file->getContents(true);
    return new MVImage($mvData);
  }

  /**
   * Create a new MVImage instance from a path.
   */
  public static function fromPath ($path)
  {
    if (file_exists($path) && is_file($path))
    {
      $size = filesize($path);
      $handle = fopen($path, 'rb');
      $mvData = fread($handle, $size);
      fclose($handle);
      return new MVImage($mvData);
    }
    else
    {
      throw new \Exception("No such file");
    }
  }

  /**
   * Create a new MvImage instance from the raw data.
   */
  public function __construct ($mvData)
  {
    $this->mvData = $mvData;
  }

  /**
   * Find the MV offset in the data.
   *
   * Returns null if the data has no MV offset.
   */
  public function getOffset ()
  {
    if (isset($this->mvData) && is_string($this->mvData))
    {
      $eoi_pos = strpos($this->mvData, "\xFF\xD9\x00\x00\x00\x18");
      if ($eoi_pos !== false)
      {
        return $eoi_pos + 2;
      }
    }
  }

  /**
   * Get the JPEG portion of an MVImage.
   *
   * Returns null if the data has no MV offset.
   */
  public function getJPEG ()
  {
    $offset = $this->getOffset();
    if (isset($offset))
    {
      return substr($this->mvData, 0, $offset);
    }
  }

  /**
   * Get the MPEG portion of an MVImage.
   *
   * Returns null if the data has no MV offset.
   */
  public function getMPEG ()
  {
    $offset = $this->getOffset();
    if (isset($offset))
    {
      return substr($this->mvData, $offset);
    }
  }

}