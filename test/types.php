<?php 

namespace Test;

use \Lum\File\Types as FT;
use Lum\File\MIME as MT;

require_once 'vendor/autoload.php';

const NUM_EXTS = 61;
const NUM_MIME = 76;

$t = new \Lum\Test;

$t->plan(7);

$c = count(FT::types());
$t->is($c, NUM_EXTS, 'correct number of Types::types() returned');

$c = count(MT::types());
$t->is($c, NUM_MIME, 'correct number of MIME::types() returned');

$t->is(FT::get('html'), 'text/html', 'get a type via static call');

$ft = new FT;

$t->is($ft->get('xml'), 'application/xml', 'get a type via instance');

$xlxs = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
$t->is($ft->get('xlsx'), $xlxs, 'get a MS Office type');

FT::use_text_xml();

$t->is($ft->get('xml'), 'text/xml', 'use_text_html() works');

$ft->use_xhtml();

$t->is(FT::get('html'), MT::APP_XHTML, 'use_xhtml() works');

echo $t->tap();
return $t;

