<?php

class OnlineLookup {
    public static $xml = 'jfritz-definitions.xml';
    public static $countrycode = '49';
    public static $areacode    = '30';

    protected static function canonizeNumber($number) {
        if ($number{0} == '+') return $number;  // already canonized
        if (substr($number, 0, 2) == '00') {
            // int'l number
            $number = '+' . substr($number, 2);
        } elseif ($number{0} == '0') {
            // local number
            $number = '+' . self::$countrycode . substr($number, 1);
        } else {
            // plain number
            $number = '+' . self::$countrycode . self::$areacode . $number;
        }
        return $number;
    }

    protected static function getDefinitionsForCountry($number) {
        $configfile = dirname(__FILE__) . '/' . self::$xml;
        $xml = new DOMDocument();
        if (!file_exists($configfile)) return false;
        $xml->load($configfile);
        
        $countries =& $xml->getElementsByTagName('country');   // first country entry

        for ($i=0; $i<$countries->length; $i++) {
            $country =& $countries->item($i);
            $cc = $country->attributes->getNamedItem('code')->nodeValue;
            if ( $cc == substr($number, 0, strlen($cc)) ) return $country;
        }
        return false;
    }

    public static function getNumberInfo($number, $maxhosts = 99) {
        $number  = self::canonizeNumber($number);
        $country = self::getDefinitionsForCountry($number);
        if ($country === false) return false;

        $cc = $country->attributes->getNamedItem('code')->nodeValue;

        $websites =& $country->getElementsByTagName('website');

        for ($i=0; $i<$websites->length; $i++) {
            $website =& $websites->item($i);

            $wname = $website->attributes->getNamedItem('name')->nodeValue;
            $wurl  = $website->attributes->getNamedItem('url')->nodeValue;
            $wpref = $website->attributes->getNamedItem('prefix')->nodeValue;
            $wentry = $website->getElementsByTagName('entry')->item(0);

            $checknum = $wpref . substr($number, strlen($cc));   // replace int'l prefix by local prefix

            $url = str_replace('$NUMBER', $checknum, $wurl);

            // Had to use wget b/c PHP's file_get_contents() and other tricks didn't succeed (returned wrong data!)
            exec('wget -q -O - "' . $url . '"', $data);
            $data = implode("\n", $data);

            $lastPos = 0;
            $details = array();
            foreach ($wentry->childNodes as $child) {
                if ($child->nodeType == XML_TEXT_NODE) continue;
                $ntitle = $child->nodeName;
                $pattern = $child->nodeValue;
                $pattern = str_replace('/', '\\/', $pattern);

                $matchct = preg_match('/'.$pattern.'/', $data, &$matches, PREG_OFFSET_CAPTURE, $lastPos);
                if ($matchct == 0) {
                    break;
                }

                $details[$ntitle] = html_entity_decode( rawurldecode( trim($matches[1][0]) ) );
                $lastPos = $matches[0][1];
            }

            if (count($details) > 0 || --$maxhosts <= 0) break;
        }

        // print_r($details);
        if (isset($details['name']) && !empty($details['name'])) {
            return  $details;
        }
        return false;
    }
}

?>