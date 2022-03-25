<?php

namespace Lum\File;

/**
 * A class for handling Google/Android's MVImage format.
 */
class MVImage
{
  const CTRL_CHARS = "\x00\x00\x00\x18";
  const FTYP_HEADER = "\x66\x74\x79\x70";
  const OLD_PRE_CHARS = "\xFF\xD9";

#  const MP_H = "\x6d\x70";
#  const ISO_H = "\x69\x73\x6f";
#  

  // If we want to be strict, we could look for only official ftyp headers:
  // https://www.ftyps.com/
  // At this time, I'm not worried about it. I may add it as an optional mode
  // down the road.

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
      // Try the control characters and 'ftyp' declaration. 
      $eoi_pos = strpos($this->mvData, self::CTRL_CHARS.self::FTYP_HEADER);
      if ($eoi_pos !== false)
      { 
        return $eoi_pos;
      }

      // Try a slightly different set of control characters.
      $eoi_pos = strpos($this->mvData, self::OLD_PRE_CHARS.self::CTRL_CHARS);
      if ($eoi_pos !== false)
      {
        return $eoi_pos + 2;
      }
    }
    return null;
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