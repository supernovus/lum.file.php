<?php

namespace Lum\File;

/**
 * A wrapper around PHP file streams that works with the File object.
 */
class Stream
{
  /**
   * The File object associated with this File\Stream object.
   */
  protected $file;

  /**
   * The PHP stream resource currently open.
   */
  protected $stream;

  /**
   * Have we opened a stream?
   */
  protected $open = false;

  /**
   * Have we changed the stream (only applicable in writable streams.)
   */
  protected $changed = false;

  /**
   * Create a new File\Stream object with the given File object.
   *
   * @param File  $file  The File object for this stream.
   * @param string $mode  If non-null, we call open() with this mode.
   * @param bool $addBin  Passed to open() if $mode was set (default: false)
   */
  public function __construct (\Lum\File $file, $mode=null, $addBin=false)
  {
    $this->file = $file;
    if (isset($mode))
    {
      $this->open($mode, $addBin);
    }
  }

  /**
   * Return the File object.
   */
  public function getFile ()
  {
    return $this->file;
  }

  /**
   * Return the current stream.
   */
  public function getStream ()
  {
    return $this->stream;
  }

  /**
   * Open a stream.
   *
   * Will automatically close an already open stream if there is one.
   * Uses the File::openStream() method to actually open the stream.
   *
   * @param string  $mode  The mode to open the stream (e.g. 'r' or 'w')
   * @param bool  $addBin  Add 'b' to the mode? (default: false)
   */
  public function open ($mode, $addBin=false)
  {
    if ($this->open)
    { // Close the open stream.
      if (!$this->close())
      {
        return false;
      }
    }
    $this->stream = $file->openStream($mode, $addBin);
    $this->open = true;
    return true;
  }

  /**
   * Read data from the stream.
   *
   * @param int $bytes  How many bytes to read (default: whole file)
   */
  public function read ($bytes=null)
  {
    if (!$this->open) { return null; }
    if (!$bytes)
    {
      $bytes = $this->file->size;
    }
    return fread($this->stream, $bytes);
  }

  /**
   * Write data to the stream.
   *
   * @param mixed $data  Data to write to stream.
   */
  public function write ($data)
  {
    if (!$this->open) { return null; }
    $bytes = fwrite($this->stream, $data);
    if ($bytes !== false)
    {
      $this->changed = true;
    }
    return $bytes;
  }

  /**
   * Close the stream.
   *
   * Will automatically update the size of the File if it has changed.
   */
  public function close ()
  {
    if ($this->open)
    {
      if (fclose($this->stream))
      {
        if ($this->changed)
        {
          $this->file->update_size();
        }
        $this->open = false;
        return true;
      }
    }
    return false;
  }

}

