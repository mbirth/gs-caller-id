<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CSV.class.php');

$result = array(
    '+4910' => 'Call-by-Call',
    '+4912' => 'Innovative Dienste',
    '+49137' => 'Televoting',
    '+49138' => 'Televoting',
    '+4915' => 'Mobilfunk',
    '+4916' => 'Mobilfunk',
    '+4917' => 'Mobilfunk',
    '+4918' => 'Shared-Cost-Dienste',
    '+4919' => 'Premium Rate Dienste',
);

$prefix = '0';
$cc = '+49';
$country = 'Deutschland';
$countrycode = 'DE';

foreach ($result as $pre=>$data) {
    $result[$pre] = array(
        'Calling Code' => $pre,
        'District' => $data,
    );
}

$db = new CSV();
$db->setDelimiter(';');
if (!$db->load(dirname(__FILE__) . DIRECTORY_SEPARATOR . '+49/LosgXXXX.TXT', true)) return $result;
$db->setUseHeaders(true);

foreach ($db->getTable() as $loc) {
    $pre = $loc['Vorwahl'];
    $ort = $loc['Ortsnetz'];
    if (substr($pre, 0, strlen($prefix)) == $prefix) $pre = $cc . substr($pre, strlen($prefix));
    $result[$pre] = array(
        'Calling Code' => $pre,
        'District' => $ort,
        'CC' => $countrycode,
        'Country' => $country,
    );
}

$db->close();

return $result;

?>
