<?php 

require_once 'vendor/autoload.php';

$t = new \Lum\Test();

$t->plan(2);

$ft = new \Lum\File\Types;

$t->is($ft->get('xml'), 'text/xml', 'get a simple type');

$xlxs = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
$t->is($ft->get('xlsx'), $xlxs, 'get a MS Office type');

echo $t->tap();
return $t;

