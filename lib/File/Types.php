<?php

namespace Lum\File;

/**
 * A quick mapping of common MIME types based on file extension alone.
 *
 * This is very basic, for enhanced functionality, use finfo.
 */

class Types
{
  protected static $types =
  [ // A list of types.
    'bin'   => MIME::MISC,
    'txt'   => MIME::TEXT,
    'xml'   => MIME::APP_XML,
    'json'  => MIME::APP_JSON,
    'html'  => MIME::TEXT_HTML,
    'htm'   => MIME::TEXT_HTML,
    'xhtml' => MIME::APP_XHTML,
    'js'    => MIME::TEXT_JS,
    'css'   => MIME::TEXT_CSS,
    'jpg'   => MIME::IMG_JPEG,
    'jpeg'  => MIME::IMG_JPEG,
    'png'   => MIME::IMG_PNG,
    'svg'   => MIME::IMG_SVG,
    'webp'  => MIME::IMG_WEBP,
    'gif'   => MIME::IMG_GIF,
    'bmp'   => MIME::IMG_BMP,
    'tif'   => MIME::IMG_TIFF,
    'tiff'  => MIME::IMG_TIFF,
    'heif'  => MIME::IMG_HEIF,
    'heic'  => MIME::IMG_HEIC,
    'avi'   => MIME::VID_AVI,
    'mp4'   => MIME::VID_MP4,
    'mpg'   => MIME::VID_MPEG,
    'mpeg'  => MIME::VID_MPEG,
    'webm'  => MIME::VID_WEBM,
    'wmv'   => MIME::VID_WMV,
    'qt'    => MIME::VID_QT,
    'mov'   => MIME::VID_QT,
    '3gp'   => MIME::VID_3GP,
    '3g2'   => MIME::VID_3G2,
    'zip'   => MIME::AR_ZIP,
    'bz2'   => MIME::AR_BZ2,
    'gz'    => MIME::AR_GZ,
    'tar'   => MIME::AR_TAR,
    'rar'   => MIME::AR_RAR,
    '7z'    => MIME::AR_7Z,
    'jar'   => MIME::AR_JAR,
    'csv'   => MIME::DOC_CSV,
    'rtf'   => MIME::DOC_RTF,
    'epub'  => MIME::DOC_EPUB,
    'ics'   => MIME::DOC_ICS,
    'pdf'   => MIME::DOC_PDF,
    'doc'   => MIME::MS_DOC,
    'ppt'   => MIME::MS_PPT,
    'vsd'   => MIME::MS_VSD,
    'xls'   => MIME::MS_XLS,
    'xlsx'  => MIME::MS_XLSX,
    'xltx'  => MIME::MS_XLTX,
    'potx'  => MIME::MS_POTX,
    'ppsx'  => MIME::MS_PPSX,
    'pptx'  => MIME::MS_PPTX,
    'sldx'  => MIME::MS_SLDX,
    'docx'  => MIME::MS_DOCX,
    'dotx'  => MIME::MS_DOTX,
    'odt'   => MIME::ODF_ODT,
    'ods'   => MIME::ODF_ODS,
    'odp'   => MIME::ODF_ODP,
    'otf'   => MIME::FONT_OTF,
    'tff'   => MIME::FONT_TTF,
    'woff'  => MIME::FONT_WOFF,
    'woff2' => MIME::FONT_WOFF2,
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
    static::set('xml', MIME::TEXT_XML, true);
  }

  public static function use_app_xml()
  {
    static::set('xml', MIME::XML, true);
  }

  public static function use_text_js()
  {
    static::set('js', MIME::JS, true);
  }

  public static function use_app_js()
  {
    static::set('js', MIME::APP_JS, true);
  }

  protected static function html_ext($x)
  {
    if (is_string($x) && strlen($x) >= 3) 
    { // An extension was passed.  
      return $x;
    }
    else
    { // true for xhtml, false for html.
      return ($x ? 'xhtml' : 'html');
    }
  }

  public static function use_text_html($x=false)
  {
    static::set(static::html_ext($x), MIME::TEXT_HTML, true);
  }

  public static function use_xhtml($x=false)
  {
    static::set(static::html_ext($x),  MIME::APP_XHTML, true);
  }

  public static function use_xml_html($x=false)
  {
    static::set(static::html_ext($x), MIME::APP_XML, true);
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
