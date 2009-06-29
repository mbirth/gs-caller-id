<?php

require_once(dirname(__FILE__) . '/../CallerIDResolver.interface.php');
require_once(dirname(__FILE__) . '/OnlineLookup.class.php');

class CallerID_OnlineLookup implements CallerIDResolver {

    public static function lookupNum($number) {
        OnlineLookup::$countrycode = CallerID::$countrycode;
        OnlineLookup::$areacode    = CallerID::$areacode;

        $info = OnlineLookup::getNumberInfo($number);
        if ($info === false) return false;
        return $info['name'];
    }
}

?>