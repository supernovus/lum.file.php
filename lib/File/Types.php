<?php

namespace Lum\File;

/**
 * A quick mapping of common MIME types based on file extension alone.
 *
 * This is very basic, for enhanced functionality, use finfo.
 */

class Types
{
  const mso  = 'application/vnd.openxmlformats-officedocument.';

  const any_text  = 'text/plain';

  const text_xml  = 'text/xml';
  const app_xml   = 'application/xml';

  const text_js   = 'text/javascript';
  const app_json  = 'application/json';

  const text_html = 'text/html';
  const app_xhtml = 'application/xhtml+xml';

  const text_css  = 'text/css';

  const img_jpeg  = 'image/jpeg';
  const img_png   = 'image/png';
  const img_svg   = 'image/svg+xml';
  const img_webp  = 'image/webp';
  const img_gif   = 'image/gif';

  protected static $types =
  [ // A list of types.
    'xml'   => self::app_xml,
    'json'  => self::app_json,
    'html'  => self::text_html,
    'xhtml' => self::app_xhtml,
    'js'    => self::text_js,
    'css'   => self::text_css,
    'jpg'   => self::img_jpeg,
    'png'   => self::img_png,
    'svg'   => self::img_svg,
    'webp'  => self::img_webp,
    'gif'   => self::img_gif,
    'xlsx'  => self::mso . 'spreadsheetml.sheet',
    'xltx'  => self::mso . 'spreedsheetml.template',
    'potx'  => self::mso . 'presentationml.template',
    'ppsx'  => self::mso . 'presentationml.slideshow',
    'pptx'  => self::mso . 'presentationml.presentation',
    'sldx'  => self::mso . 'presentationml.slide',
    'docx'  => self::mso . 'wordprocessingml.document',
    'dotx'  => self::mso . 'wordprocessingml.template',
  ];

  public static function types ()
  {
    return static::$types;
  }

  public static function get ($type)
  {
    #error_log(">> Types::get($type)");
    $types = static::types();
    if (isset($types[$type]))
    {
      #error_log(">> Types::get() value: ".json_encode($types[$type]));
      return $types[$type];
    }
  }

  public static function use_text_xml()
  {
    static::set('xml', self::text_xml, true);
  }

  public static function use_app_xml()
  {
    static::set('xml', self::app_xml, true);
  }

  public static function use_text_json()
  {
    static::set('json', self::text_js, true);
  }

  public static function use_app_json()
  {
    static::set('json', self::app_json, true);
  }

  protected static function html_ext($x=false)
  {
    return ($x ? 'xhtml' : 'html');
  }

  public static function use_text_html($x=false)
  {
    static::set(static::html_ext($x), self::text_html, true);
  }

  public static function use_xhtml($x=false)
  {
    static::set(static::html_ext($x),  self::app_xhtml, true);
  }

  public static function use_xml_html($x=false)
  {
    static::set(static::html_ext($x), self::app_xml, true);
  }

  /**
   * Set or add a type map.
   *
   * Due to the static nature of this class, it should be considered
   * a singleton class, so any types added will be added globally.
   *
   * @param string $extension  The file extension to map a MIME type to.
   * @param string $mimetype   The MIME type for the file extension.
   * @param bool   $overwrite  Overwrite exiting items? (Default: false)
   */
  public static function set (string $extension, string $mimetype, 
    bool $overwrite=false)
  {
    #error_log(">> Types::set($extension, $mimetype, ".json_encode($overwrite).')');
    if (!$overwrite && isset(static::$types[$extension]))
      throw new \Exception("Cannot overwrite '$extension' type in ".__CLASS__);

    static::$types[$extension] = $mimetype;

    #error_log(">> Types::set() value: ".json_encode(static::$types[$extension]));
  }

}
