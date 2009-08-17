#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . '/../CallerIDResolver.interface.php');
require_once(dirname(__FILE__) . '/OnlineLookup.class.php');

$number = $argv[1];

if (empty($number)) die('Keine Rufnummer angegeben!');

OnlineLookup::$countrycode = '49';
OnlineLookup::$areacode = '30';

$info = OnlineLookup::getNumberInfo($number);
if ($info === false) die('Keine Angaben gefunden!');

print_r($info);

exit;

?>
