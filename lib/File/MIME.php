<?php

namespace Lum\File;

/**
 * A static collection of constants representing common MIME Types.
 */
class MIME
{
  use \Lum\Meta\GetConstants;

  // Common web-app related formats.
  const TEXT      = 'text/plain';
  const TEXT_CSS  = 'text/css';
  const TEXT_JS   = 'text/javascript';
  const TEXT_HTML = 'text/html';
  const APP_XHTML = 'application/xhtml+xml';
  const APP_XML   = 'application/xml';
  const APP_YAML  = 'application/yaml';
  const APP_JSON  = 'application/json';

  // Deprecated alternatives to a few of the above.
  const TEXT_XML  = 'text/xml';
  const TEXT_YAML = 'text/yaml';
  const APP_JS    = 'application/javascript';

  // Common image formats.
  const IMG_JPEG  = 'image/jpeg';
  const IMG_PNG   = 'image/png';
  const IMG_SVG   = 'image/svg+xml';
  const IMG_WEBP  = 'image/webp';
  const IMG_GIF   = 'image/gif';
  const IMG_AVIF  = 'image/avif';
  const IMG_BMP   = 'image/bmp';
  const IMG_ICO   = 'image/vnd.microsoft.icon';
  const IMG_TIFF  = 'image/tiff';
  const IMG_HEIF  = 'image/heif';
  const IMG_HEIC  = 'image/heic';

  // Common video formats.
  const VID_AVI   = 'video/x-msvideo';
  const VID_MP4   = 'video/mp4';
  const VID_MPEG  = 'video/mpeg';
  const VID_OGV   = 'video/ogg';
  const VID_TS    = 'video/mp2t';
  const VID_WEBM  = 'video/webm';
  const VID_WMV   = 'video/x-ms-wmv';
  const VID_QT    = 'video/quicktime';
  const VID_3GP   = 'video/3gpp';
  const VID_3G2   = 'video/3gpp2';

  // Common audio formats.
  const AUD_AAC   = 'audio/aac';
  const AUD_MIDI  = 'audio/midi';
  const AUD_MP3   = 'audio/mpeg';
  const AUD_OGA   = 'audio/ogg';
  const AUD_FLAC  = 'audio/flac';
  const AUD_WAV   = 'audio/wav';
  const AUD_WEBA  = 'audio/webm';
  const AUD_3GP   = 'audio/3gpp';
  const AUD_3G2   = 'audio/3gpp2';

  // Common archive formats.
  const AR_ARC = 'application/x-freearc';
  const AR_BZ  = 'application/x-bzip';
  const AR_BZ2 = 'application/x-bzip2';
  const AR_GZ  = 'application/gzip';
  const AR_JAR = 'application/java-archive';
  const AR_RAR = 'application/vnd.rar';
  const AR_TAR = 'application/x-tar';
  const AR_ZIP = 'application/zip';
  const AR_7Z  = 'application/x-7z-compressed';

  // Various document formats.
  const DOC_CSV  = 'text/csv';
  const DOC_ABW  = 'application/x-abiword';
  const DOC_EPUB = 'application/epub+zip';
  const DOC_ICS  = 'text/calendar';
  const DOC_RTF  = 'application/rtf';
  const DOC_PDF  = 'application/pdf';

  // Font formats.
  const FONT_OTF   = 'font/otf';
  const FONT_TTF   = 'font/ttf';
  const FONT_WOFF  = 'font/woff';
  const FONT_WOFF2 = 'font/woff2';

  // Microsoft Office XML formats.
  const _MSO  = 'application/vnd.openxmlformats-officedocument.';
  const MS_DOCX = self::_MSO . 'wordprocessingml.document';
  const MS_DOTX = self::_MSO . 'wordprocessingml.template';
  const MS_POTX = self::_MSO . 'presentationml.template';
  const MS_PPSX = self::_MSO . 'presentationml.slideshow';
  const MS_PPTX = self::_MSO . 'presentationml.presentation';
  const MS_SLDX = self::_MSO . 'presentationml.slide';
  const MS_XLSX = self::_MSO . 'spreadsheetml.sheet';
  const MS_XLTX = self::_MSO . 'spreadsheetml.template';

  // Microsoft Office old formats.
  const MS_DOC  = 'application/msword';
  const MS_PPT  = 'application/vnd.ms-powerpoint';
  const MS_VSD  = 'application/vnd.visio';
  const MS_XLS  = 'application/vnd.ms-excel';

  // OpenDocument XML formats.
  const _ODF  = 'application/vnd.oasis.opendocument.';
  const ODF_ODP  = self::_ODF . 'presentation';
  const ODF_ODS  = self::_ODF . 'spreadsheet';
  const ODF_ODT  = self::_ODF . 'text';

  // Finally, for anything not recognized.
  const MISC = 'application/octet-stream';

  public static function types ()
  {
    return array_filter(self::getConstants(), 
      fn($k) => !str_starts_with($k, '_'),
      ARRAY_FILTER_USE_KEY);
  }

} // MIME class

