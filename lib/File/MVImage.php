<?php

namespace Lum\File;

/**
 * A class for handling Google/Android's MVImage format.
 */
class MVImage
{
  const TEST_FTYP = 1;
  const TEST_CTRL = 2;
  const TEST_OLD  = 4;

  const CTRL_CHARS = "\x00\x00\x00\x18";
  const FTYP_HEADER = "\x66\x74\x79\x70";
  const OLD_PRE_CHARS = "\xFF\xD9";

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
    if ($file->exists())
    {
      $mvData = $file->getContents(true);
      return new MVImage($mvData);
    }
    else
    {
      throw new \Exception("File not found");
    }
  }

  /**
   * Create a new MVImage instance from a file path.
   */
  public static function fromPath (string $path)
  {
    $file = new \Lum\File($path);
    return static::fromFile($file);
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
  public function getOffset (int $tests=self::TEST_FTYP)
  {
    if (isset($this->mvData) && is_string($this->mvData))
    {
      if ($tests <= 0) $tests = self::TEST_FTYP;

      if ($tests & self::TEST_CTRL)
      {
        // Try the control characters and 'ftyp' declaration. 
        $eoi_pos = strpos($this->mvData, self::CTRL_CHARS.self::FTYP_HEADER);
        if ($eoi_pos !== false)
        { 
          return $eoi_pos;
        }
      }

      if ($tests & self::TEST_OLD)
      {
        // Try a slightly different set of control characters.
        $eoi_pos = strpos($this->mvData, self::OLD_PRE_CHARS.self::CTRL_CHARS);
        if ($eoi_pos !== false)
        { // The POS + 2 prefix characters.
          return $eoi_pos + 2;
        }
      }

      if ($tests & self::TEST_FTYP)
      {
        $eoi_pos = strpos($this->mvData, self::FTYP_HEADER);
        if ($eoi_pos !== false)
        { // The POS - 4 header characters.
          return $eoi_pos - 4;
        }
      }
    }
    return null;
  }

  /**
   * Get the JPEG portion of an MVImage.
   *
   * Returns null if the data has no MV offset.
   */
  public function getJPEG (int $tests=self::TEST_FTYP)
  {
    $offset = $this->getOffset($tests);
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
  public function getMPEG (int $tests=self::TEST_FTYP)
  {
    $offset = $this->getOffset($tests);
    if (isset($offset))
    {
      return substr($this->mvData, $offset);
    }
  }

}