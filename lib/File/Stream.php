<?php

namespace Lum\File;

/**
 * A wrapper around PHP file streams that works with the File object.
 */
class Stream
{
  const BINARY      = 1; // Add 'b' which is unnecessary on modern systems.
  const TRANSLATE   = 2; // Add 't' which is unrecommended.
  const NONBLOCKING = 4; // Add 'n' which is undocumented, YMMV.
  const CLOSE_EXEC  = 8; // Add 'e' which is not supported on all platforms.

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
   * @param string $mode  If non-null, call open($mode); Default: null
   * @param int $flags Passed to open() if $mode was set. Default: null
   */
  public function __construct (\Lum\File $file, $mode=null, $flags=null)
  {
    $this->file = $file;
    if (isset($mode))
    {
      $this->open($mode, $flags);
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
   *                       Use null for File defaults. Default: null
   *
   * @param int|bool  $flags  Flags passed to File::openStream()
   *                          Default: null (which uses File defaults.)
   *
   *  Stream::BINARY      = Use binary mode.
   *  Stream::TRANSLATE   = Use translate mode. 
   *  Stream::NONBLOCKING = Use non-blocking mode.
   *  Stream::CLOSE_EXEC  = Use close-exec mode.
   *
   *  Binary mode and translate mode are mutually exclusive.
   *  Non-blocking mode is undocumented and does not work on all platforms.
   *  Close-exec mode is not available on all platforms.
   *
   */
  public function open ($mode=null, $flags=null)
  {
    if ($this->open)
    { // Close the open stream.
      if (!$this->close())
      {
        return false;
      }
    }
    $this->stream = $this->file->openStream($mode, $flags);
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
  public function write ($data, $length=null)
  {
    if (!$this->open) { return null; }
    $bytes = fwrite($this->stream, $data, $length);
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

