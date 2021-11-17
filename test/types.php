<?php 

namespace Test;

use \Lum\File\Types as FT;

require_once 'vendor/autoload.php';

$t = new \Lum\Test;

$t->plan(6);

$c = count(FT::types());
$t->is($c, 19, 'correct number of types returned');

$t->is(FT::get('html'), 'text/html', 'get a type via static call');

$ft = new FT;

$t->is($ft->get('xml'), 'application/xml', 'get a type via instance');

$xlxs = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
$t->is($ft->get('xlsx'), $xlxs, 'get a MS Office type');

FT::use_text_xml();

$t->is($ft->get('xml'), 'text/xml', 'use_text_html() works');

$ft->use_xhtml();

$t->is(FT::get('html'), FT::app_xhtml, 'use_xhtml() works');

echo $t->tap();
return $t;

