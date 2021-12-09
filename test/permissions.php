<?php

require_once 'vendor/autoload.php';

use Lum\File\Permissions;

$t = new \Lum\Test();

$ten_tests =
[
  'drwxr-xr-x' => 0755,
  '-rw-r--r--' => 0644,
  '-rwsr-sr--' => 06754,
  '-rwsr-Sr--' => 06744,
  '-rwSr--r--' => 04644,
  '-rw-r--r-T' => 01644,
  '-rwxr-xr-t' => 01755,
];

$mod_tests =
[
  ['u=rwx,g=rx,o=r', 0754, 0, true, 'drwxr-xr--'],
  ['og+w', 0666, 0644, false, '-rw-rw-rw-'],
  ['u=rw,g=u', 0664, 0714, false, '-rw-rw-r--'],
  ['go-x', 0766, 0777, false, '-rwxrw-rw-'],
  ['u+s', 04755, 0755, true, 'drwsr-xr-x'],
  ['u+s,a-x', 04644, 0755, false, '-rwSr--r--'],
  ['o+X', 0701, 0700, false, '-rwx-----x'],
  ['g+X', 04710, 04700, false, '-rws--x---'],
  ['u+X', 0644, 0644, false, '-rw-r--r--'],
  ['+r', 0444, 0, false, '-r--r--r--'],
  ['-x', 0644, 0755, false, '-rw-r--r--'],
];

$has_haves =
[
  'drwxr-x---',
  '-r--------',
  '-rw-rw-r--',
  '-r-xr-xr-x',
  '---S--S--T',
  '---s--s--t',
  '---x--S--t',
];

$has_wants =
[
  '+r'              => [false, false, true,  true,  false, false, false],
  'u+r'             => [true,  true,  true,  true,  false, false, false],
  'g+r'             => [true,  false, true,  true,  false, false, false],
  'o+r'             => [false, false, true,  true,  false, false, false],
  'u+w'             => [true,  false, true,  false, false, false, false],
  'g+w'             => [false, false, true,  false, false, false, false],
  'o+w'             => [false, false, false, false, false, false, false],
  'u+x'             => [true,  false, false, true,  false, true,  true],
  'g+x'             => [true,  false, false, true,  false, true,  false],
  'o+x'             => [false, false, false, true,  false, true,  true],
  '---S------'      => [false, false, false, false, true,  true,  false],
  '------S---'      => [false, false, false, false, true,  true,  true],
  '---------T'      => [false, false, false, false, true,  true,  true],
  '---s------'      => [false, false, false, false, false, true,  false],
  '---s--s--t'      => [false, false, false, false, false, true,  false],
];

$testCount = 
  (count($ten_tests)*2)
  + (count($mod_tests)*2)
  + (count($has_haves)*count($has_wants));

$t->plan($testCount);

// First do all the 10 character tests.
foreach ($ten_tests as $string => $wantmode)
{
  $isDir = ($string[0] == 'd');
  $t->is(Permissions::parse($string), $wantmode, "parsed '$string'");
  $t->is(Permissions::encode($wantmode, $isDir), $string, "encoded '".decoct($wantmode)."'");
}

// Next do the mod_tests.
foreach ($mod_tests as $mod_test)
{
  list($string, $wantmode, $startmode, $isdir, $wantstr) = $mod_test;
  $t->is(Permissions::convert_mod($string, $startmode, $isdir), $wantstr, "converted '$string'");
  $t->is(Permissions::parse($string, $startmode), $wantmode, "parsed '$string'");
}

foreach ($has_haves as $h => $have)
{
  foreach ($has_wants as $want => $expected)
  {
    $t->is(Permissions::has($have, $want), $expected[$h], "$have has $want");
  }
}

echo $t->tap();

return $t;

