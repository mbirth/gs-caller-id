<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CSV.class.php');

$states = array(
    'AL' => 'Alabama',
    'AK' => 'Alaska',
    'AZ' => 'Arizona',
    'AR' => 'Arkansas',
    'CA' => 'Kalifornien',
    'CO' => 'Colorado',
    'CT' => 'Connecticut',
    'DC' => 'Washington D.C.',
    'DE' => 'Delaware',
    'FL' => 'Florida',
    'GA' => 'Georgia',
    'HI' => 'Hawaii',
    'ID' => 'Idaho',
    'IL' => 'Illinois',
    'IN' => 'Indiana',
    'IA' => 'Iowa',
    'KS' => 'Kansas',
    'KY' => 'Kentucky',
    'LA' => 'Louisiana',
    'MA' => 'Massachusetts',
    'MD' => 'Maryland',
    'ME' => 'Maine',
    'MI' => 'Michigan',
    'MN' => 'Minnesota',
    'MS' => 'Mississippi',
    'MO' => 'Missouri',
    'MT' => 'Montana',
    'NE' => 'Nebraska',
    'NV' => 'Nevada',
    'NH' => 'New Hampshire',
    'NJ' => 'New Jersey',
    'NM' => 'New Mexiko',
    'NY' => 'New York',
    'NC' => 'North Carolina',
    'ND' => 'North Dakota',
    'OH' => 'Ohio',
    'OK' => 'Oklahoma',
    'OR' => 'Oregon',
    'PA' => 'Pennsylvania',
    'RI' => 'Rhode Island',
    'SC' => 'South Carolina',
    'SD' => 'South Dakota',
    'TN' => 'Tennessee',
    'TX' => 'Texas',
    'UT' => 'Utah',
    'VT' => 'Vermont',
    'VA' => 'Virginia',
    'WA' => 'Washington',
    'WV' => 'West Virginia',
    'WI' => 'Wisconsin',
    'WY' => 'Wyoming',
);

$nonus = array(
    array('Amerika', 'UK', array('264', '441', '345', '664', '649')),
    array('Kanada', 'CA', array('403', '587', '780', '250', '604', '778', '204', '506', '709', '902', '867', '226', '289', '416', '519', '613', '647', '705', '807', '905', '418', '438', '450', '514', '581', '819', '306', '600')),
);

foreach ($nonus as $i=>$nu) {
    foreach ($nu[2] as $pre) {
        $nonus[$pre] = array($nu[0], $nu[1]);
    }
    unset($nonus[$i]);
}

$result = array(
);

$prefix = '';
$cc = '+1';
$country = 'USA';
$countrycode = 'US';

foreach ($result as $pre=>$data) {
    $result[$pre] = array(
        'Calling Code' => $pre,
        'District' => $data,
    );
}

$db = new CSV();
$db->setDelimiter(';');
if (!$db->load(dirname(__FILE__) . DIRECTORY_SEPARATOR . '+1/NpasInSvcByNumRpt.csv', true)) return array();
$db->setUseHeaders(true);

foreach ($db->getTable() as $loc) {
    $pre = $loc['Npa'];
    $ort = $loc['Location'];
    $coc = $countrycode;
    $cou = $country;
    if (isset($nonus[$pre])) {
        $coc = $nonus[$pre][1];
        $cou = $nonus[$pre][0];
    }
    if (substr($pre, 0, strlen($prefix)) == $prefix) $pre = $cc . substr($pre, strlen($prefix));
    if (isset($states[$ort])) $ort = $states[$ort];
    $result[$pre] = array(
        'Calling Code' => $pre,
        'District' => $ort,
        'CC' => $coc,
        'Country' => $cou,
    );
}

$db->close();

return $result;

?>
