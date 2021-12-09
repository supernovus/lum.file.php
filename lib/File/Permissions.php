<?php

namespace Lum\File;

/**
 * Convert between Unix permissions strings and integer values.
 */
class Permissions
{
  // Handle error messages.
  protected static function err ($msg, $opts=null)
  {
    if (is_array($opts))
    { // Options were passed.
      if (isset($opts['no_errors']) && $opts['no_errors'])
      { // The 'no_errors' option means we won't report errors at all.
        return;
      }
      if (isset($opts['fatal']) && $opts['fatal'])
      { // The 'fatal' option means errors throw exceptions.
        throw new \Exception($msg);
      }
    }

    // If we reached here, log the error.
    error_log($msg);
  }

  // Handle new and old option types.
  protected static function opts($opts, $isDir=null)
  {
    if (is_numeric($opts))
    { // It's the initial mode for conversions.
      $opts = ['inMode'=>$opts];
    }
    elseif (is_bool($opts))
    { // It's the initial type for encoding.
      $opts = ['inType'=>$opts];
    }
    elseif (!is_array($opts))
    {
      $opts = [];
    }

    if (isset($isDir) && !isset($opts['inType']))
    { // Old isDir option was passed, it's been replaced with 'inType'.
      $opts['inType'] = $isDir;
    }

    return $opts;
  }

  // Get character options.
  protected static function char_opt($opts, $opt, $def)
  {
    if (!is_string($def) || strlen($def) < 1) $def = '!'; // Invalid default.
    $char = 
      (isset($opts[$opt]) 
      && is_string($opts[$opt]) 
      && strlen($opts[$opt]) > 0) 
      ? $opts[$opt] 
      : $def;
    return $char[0]; // Make sure we're returning only the first character.
  }

  protected static function type_char($opts, $inmode=null)
  {
    $fc = self::char_opt($opts, 'file_char', '-');
    $wc = self::char_opt($opts, 'what_char', '?');
    $uf = isset($opts['none_file']) ? $opts['none_file'] : false;
    $uc = $uf ? $fc : $wc;

    $intype = isset($opts['inType']) ? $opts['inType'] : null;

    if (is_string($intype))
    { // It's a string. We're going to return it, but first make sure its okay.
      $len = strlen($intype);
      if ($len == 1)
      { // It's exactly the right length.
        return $intype;
      }
      elseif ($len < 1)
      { // It's less than one character, that's not valid.
        self::err('inType string must be 1 character', $opts);
        return $uc;
      }
      else
      { // It's more than 1 character, return just the first character.
        return $intype[0];
      }
    }
    elseif (is_bool($intype))
    { // Old school boolean type parameter.
      if ($intype) return 'd'; // true is directory.
      return $fc;              // false is regular file.
    }

    // If we reached here, the intype was not a recognized value or was null.
    // Therefore we're going to use auto-detection.

    if (!isset($inmode))
    { // No direct inMode passed, look for the same-named option instead.
      $inmode = isset($opts['inMode']) ? $opts['inMode'] : 0; 
    }

    if (!is_numeric($inmode))
    {
      if (is_string($inmode))
      { // It's a string and it's not numeric, let's try parsing it.
        $inmode = self::parse($inmode, $opts);
      }
      else
      { // It's not a mode we can parse.
        self::err('inMode was not a supported value: '
          . json_encode($inmode), $opts);

        return $uc;
      }
    }

    // Past this point we assume a numeric value.

    if (!is_int($inmode))
    { // Enforce integer value.
      $inmode = intval($inmode);
    }

    if ($inmode == 0) return $uc;  // Permission was 0, cannot continue.
    $filebit = $inmode & 0xF000;   // 0170000 - the filetype bit.
    if ($filebit == 0) return $uc; // Filetype bit was 0, cannot continue.

    switch ($filebit)
    {
      case 0xC000:   // 0140000 - socket
        return 's';
      case 0xA000:   // 0120000 - symbolic link
        return 'l';
      case 0x8000:   // 0100000 - regular file
        return $fc;
      case 0x6000:   // 0060000 - block device
        return 'b';
      case 0x4000:   // 0040000 - directory
        return 'd';
      case 0x2000:   // 0020000 - character device
        return 'c';
      case 0x1000:   // 0010000 - FIFO pipe
        return 'p';
      default:
        return $wc;  // We have no idea what this is, LOL.
    } 
  }

  /**
   * Convert a permission string into an integer value.
   *
   * @param str $string      The permission string we are parsing.
   * @param array $opts      Extra options that are used for different things.
   *
   *  'addType'   (bool)  If true, add the filetype bits to the returned value.
   *                      Default is true if 'inType' is set to a string,
   *                      and false otherwise.
   *
   *  'initMode'  (int)   The initial mode value before we add the properties
   *                      from the parsed string. Default: 0
   *
   *  See the encode() and convert_mod() methods as well, as any options
   *  supported for them may be passed here as well. 
   *
   * @return int   The integer mode value (optionally with the type bits set.)
   *
   * NOTE: If the string is not 10 characters long exactly, or contains
   *       any of the letters u, g, a, or o, it will be converted using the
   *       convert_mod() function. This is the only time the 'inMode' and
   *       'inType' options are used.
   *
   * NOTE 2: If the $opts parameter is passed an integer, it will be set
   *         as the 'mode' option for backwards compatibility with older
   *         versions of the library.
   */
  static function parse ($string, $opts=[])
  {
    $mode = isset($opts['initMode']) ? intval($opts['initMode']) : 0;

    if (is_int($string))
    { // It's already an integer, no parsing needed.
      return $string;
    }
    elseif (is_numeric($string))
    { // It's a numeric string, or maybe a float. Make it into an int.
      return intval($string);
    }
    elseif (!is_string($string))
    { // It's not a valid value.
      self::err('invalid string passed to parse: '
        . json_encode($string), $opts);
      // Assume no permissions at all.
      return $mode;
    }

    $opts = self::opts($opts);

    if (strlen($string) != 10 || preg_match('/[ugao]+/', $string))
    { // Pass it off onto parse_mod()
      $string = self::convert_mod($string, $opts);
    }

    if ($string[1] == 'r') $mode += 0400;
    if ($string[2] == 'w') $mode += 0200;
    if ($string[3] == 'x') $mode += 0100;
    elseif ($string[3] == 's') $mode += 04100;
    elseif ($string[3] == 'S') $mode += 04000;

    if ($string[4] == 'r') $mode += 040;
    if ($string[5] == 'w') $mode += 020;
    if ($string[6] == 'x') $mode += 010;
    elseif ($string[6] == 's') $mode += 02010;
    elseif ($string[6] == 'S') $mode += 02000;

    if ($string[7] == 'r') $mode += 04;
    if ($string[8] == 'w') $mode += 02;
    if ($string[9] == 'x') $mode += 01;
    elseif ($string[9] == 't') $mode += 01001;
    elseif ($string[9] == 'T') $mode += 01000;

    if (isset($opts['addType']))
    {
      $addType = $opts['addType'];
    }
    else
    {
      $addType = isset($opts['inType']);
    }

    $fc = self::char_opt($opts, 'file_char', '-');

    if ($addType)
    { // Add a filetype bit for any known filetypes.
          if ($string[0] == 's') $mode += 0140000;
      elseif ($string[0] == 'l') $mode += 0120000;
      elseif ($string[0] == $fc) $mode += 0100000;
      elseif ($string[0] == 'b') $mode += 0060000;
      elseif ($string[0] == 'd') $mode += 0040000;
      elseif ($string[0] == 'c') $mode += 0020000;
      elseif ($string[0] == 'p') $mode += 0010000;
    }

    return $mode;
  }

  /**
   * Encode an integer value into a 10 character string.
   *
   * @param int $int     The integer value we are encoding.
   * @param array $opts  Extra options that are used for different things.
   *
   *  'file_char' (string)  The character to use for regular files ('-');
   *  'what_char' (string)  The character to use for unknown files ('?');
   *
   *  'none_file' (bool)    If true use 'file_char' if the integer doesn't
   *                        have any file type properties at all. Otherwise
   *                        we'll use the 'what_char' as if it was an unknown
   *                        value instead.
   *
   *  'inType'    (mixed)   How we handle filetype processing.
   *
   *    If it's a string like 'd', '-', 'l', it will be set as the first 
   *    character of the string. This allows for the maximum flexibility
   *    when we already know the file type.
   *
   *    If it's boolean false, we use '-'. If it's boolean true, we use 'd'.
   *
   *    If it's null (the default), we will check for known type flags in
   *    the $int itself (assuming an extended file mode value.)
   *
   * @return str  The permissions string.
   */
  static function encode ($int, $opts=[])
  {
    if (is_string($int))
    { // A string was passed instead of an integer.
      if (is_numeric($int))
      { // It's a numeric string, make it into an int.
        $int = intval($int);
      }
      elseif (strlen($int) == 10 && !preg_match('/[ugao]+/', $int))
      { // It's already a 10 character string, and has no UGAO characters.
        return $int;
      }
      else
      { // Assume it's a UGAO string and use convert_mod() on it.
        return self::convert_mod($int, $opts);
      }
    }
    elseif (is_float($int))
    { // Floating point, make it into an integer.
      $int = intval($int);
    }
    elseif (!is_int($int))
    { // Not an integer, that's not valid.
      self::err('invalid value passed to encode: '
        . json_encode($int), $opts);
      // Assume no permissions at all.
      $int = 0;
    }

    $opts = self::opts($opts);

    $string = self::type_char($opts, $int);

    if ($int & 0400)
      $string .= 'r';
    else
      $string .= '-';
    if ($int & 0200)
      $string .= 'w';
    else
      $string .= '-';
    if ($int & 04000 && $int & 0100)
      $string .= 's';
    elseif ($int & 0100)
      $string .= 'x';
    elseif ($int & 04000)
      $string .= 'S';
    else
      $string .= '-';

    if ($int & 040)
      $string .= 'r';
    else
      $string .= '-';
    if ($int & 020)
      $string .= 'w';
    else
      $string .= '-';
    if ($int & 02000 && $int & 010)
      $string .= 's';
    elseif ($int & 010)
      $string .= 'x';
    elseif ($int & 02000)
      $string .= 'S';
    else
      $string .= '-';

    if ($int & 04)
      $string .= 'r';
    else
      $string .= '-';
    if ($int & 02)
      $string .= 'w';
    else
      $string .= '-';
    if ($int & 01000 && $int & 01)
      $string .= 't';
    elseif ($int & 01)
      $string .= 'x';
    elseif ($int & 01000)
      $string .= 'T';
    else
      $string .= '-';

    return $string;
  }

  /**
   * Compare two modes, and see if the first mode has AT LEAST the
   * same permissions as the second mode.
   *
   * @param str|int $havePerms  The permissions mode we are testing.
   *                            May be a mode integer value, a full
   *                            mode string, or a UGOA string.
   *
   * @param str|int $wantPerms  The minimum permissions required.
   *                            May be any of the same types as $havePerms.
   *
   * @param array $opts         Reserved for future use.
   *
   * @return bool  Does $havePerms have all of the $minPerms?
   */
  static function has ($havePerms, $wantPerms, $opts=[])
  {
    $havePerms = self::encode($havePerms, $opts);
    $wantPerms = self::encode($wantPerms, $opts);

    for ($c=1; $c < 10; $c++)
    { 
      $want = $wantPerms[$c];
      $have = $havePerms[$c];
      if ($want == '-') continue;     // No want permission, next!
      if ($have == $want) continue;   // They match exactly, next!
      if (($c == 3 || $c == 6) 
        && $have == 's' && ($want == 'x' || $want == 'S'))
      { // s is both x and S combined
        continue;
      }
      if ($c == 9 && $have == 't' && ($want == 'x' || $want == 'T'))
      { // t is both x and T combined
        continue;
      }

      // If we reached here, none of the above checks passed.
      return false;
    }

    // If we reached here, all permissions matched.
    return true;
  }

  /**
   * Convert a UGOA string into a 10 character permissions string.
   *
   * @param str   $string  The UGOA string we are parsing.
   * @param array $opts    Extra options that are used for different things.
   *
   *   'inMode' (mixed)  Used to determine the initial mode prior to applying
   *                     the UGOA operations to it.
   *                     May be an integer, which we'll pass to encode();
   *                     May be a 10-character string, we'll use directly.
   *                     Anything else we'll use a reasonable default.
   *
   * See encode() as well, as if 'inMode' is an integer we pass it and the
   * options over there.
   *
   * @return int  The modified permissions value.
   *
   * Current Limitations/Differences from Unix chmod:
   *
   *  1.) If you use '=' it will override ALL permissions including s/S.
   *
   */
  static function convert_mod ($instring, $opts=[], $old=null)
  {
    $opts = self::opts($opts, $old);

    $inmode = isset($opts['inMode']) ? $opts['inMode'] : 0;

    if (is_int($inmode))
    {
      $outstring = self::encode($inmode, $opts);
    }
    elseif (is_string($inmode) && strlen($inmode) == 10)
    {
      $outstring = $inmode;
    }
    else
    {
      $fc = self::char_opt($opts, 'file_char', '-');
      $it = isset($opts['inType'])    ? $opts['inType']    : false;

      if (is_bool($it))
        $first = $it ? 'd' : $fc;
      elseif (is_string($it) && strlen($it) > 0)
        $first = $it[0];
      else
        $first = $fc;

      $outstring = $first.'---------';
    }

    if (trim($instring) === '')
    { // Nothing further to do here.
      return $outstring;
    }

    $psets = explode(',', $instring);
    foreach ($psets as $pset)
    {
      $pdef = [];
      if (preg_match('/([ugoa]*)([\+\-\=])([rwxXstugo]+)/', $pset, $pdef))
      {
#        error_log("matched: ".json_encode($pdef));
        $ugoa  = $pdef[1];
        $op    = $pdef[2];
        $perms = $pdef[3];

        if ($perms == 'u')
        { // Copy permissions from user.
          $do_r = ($outstring[1] == 'r');
          $do_w = ($outstring[2] == 'w');
          $do_x = ($outstring[3] == 'x' || $outstring[3] == 's');
          $do_s = ($outstring[3] == 'S' || $outstring[3] == 's');
          $do_t = false;
        }
        elseif ($perms == 'g')
        { // Copy permissions from group.
          $do_r = ($outstring[4] == 'r');
          $do_w = ($outstring[5] == 'w');
          $do_x = ($outstring[6] == 'x' || $outstring[6] == 's');
          $do_s = ($outstring[6] == 'S' || $outstring[6] == 's');
          $do_t = false;
        }
        elseif ($perms == 'o')
        { // Copy permissions from others.
          $do_r = ($outstring[7] == 'r');
          $do_w = ($outstring[8] == 'w');
          $do_x = ($outstring[9] == 'x' || $outstring[9] == 't');
          $do_s = ($outstring[9] == 'T' || $outstring[9] == 't');
          $do_s = false;
        }
        else
        { // Check for rwxXst properties.
          $do_r = is_numeric(strpos($perms, 'r'));
          $do_w = is_numeric(strpos($perms, 'w'));
          $do_s = is_numeric(strpos($perms, 's'));
          $do_t = is_numeric(strpos($perms, 't'));
          if (is_numeric(strpos($perms, 'X')))
          { // If 'x' is true in any existing field, do 'x'.
            $do_x = 
            (
              $outstring[3] == 'x' || $outstring[3] == 's'
              || $outstring[6] == 'x' || $outstring[6] == 's'
              || $outstring[9] == 'x' || $outstring[9] == 't'
            );
          }
          else
          { // Check for 'x'.
            $do_x = is_numeric(strpos($perms, 'x'));
          }
        }

        if ($ugoa == '')
        {
          $ugoa = 'a';
        }

        if (is_numeric(strpos($ugoa, 'a')))
        { // All overrides everything else.
          $do_u = $do_g = $do_o = true;
        }
        else
        { // Look for 'u', 'g', and 'o' separately.
          $do_u = is_numeric(strpos($ugoa, 'u'));
          $do_g = is_numeric(strpos($ugoa, 'g'));
          $do_o = is_numeric(strpos($ugoa, 'o'));
        }

        $parse_op = function ($rc, $wc, $xc, $do_e, $eboth, $eonly)
          use (&$outstring, $op, $do_r, $do_w, $do_x)
        {
#          error_log("parseop($rc, $wc, $xc, $do_e, $eboth, $eonly) use ($outstring, $op, $do_r, $do_w, $do_x)");
          if ($op == '=')
          {
            if ($do_r)
              $outstring[$rc] = 'r';
            else
              $outstring[$rc] = '-';
            if ($do_w)
              $outstring[$wc] = 'w';
            else
              $outstring[$wc] = '-';
            if ($do_x && $do_e)
              $outstring[$xc] = $eboth;
            elseif ($do_x)
              $outstring[$xc] = 'x';
            elseif ($do_e)
              $outstring[$xc] = $eonly;
            else
              $outstring[$xc] = '-';
          }
          elseif ($op == '+')
          {
            if ($do_r)
              $outstring[$rc] = 'r';
            if ($do_w)
              $outstring[$wc] = 'w';
            if ($do_x && $do_e)
              $outstring[$xc] = $eboth;
            elseif ($do_x)
              $outstring[$xc] = ($outstring[$xc] == $eboth || $outstring[$xc] == $eonly) ? $eboth : 'x';
            elseif ($do_e)
              $outstring[$xc] = ($outstring[$xc] == $eboth || $outstring[$xc] == 'x') ? $eboth : $eonly;
          }
          elseif ($op == '-')
          {
            if ($do_r)
              $outstring[$rc] = '-';
            if ($do_w)
              $outstring[$wc] = '-';
            if ($do_x && $do_e)
              $outstring[$xc] = '-';
            elseif ($do_x)
              $outstring[$xc] = $outstring[$xc] == $eboth ? $eonly : '-';
            elseif ($do_e)
              $outstring[$xc] = $outstring[$xc] == $eboth ? 'x' : '-';
          }
        };

        if ($do_u)
        {
          $parse_op(1, 2, 3, $do_s, 's', 'S');
        }
        if ($do_g)
        {
          $parse_op(4, 5, 6, $do_s, 's', 'S');
        }
        if ($do_o)
        {
          $parse_op(7, 8, 9, $do_t, 't', 'T');
        }
      }
    }

    return $outstring;
  }

}
