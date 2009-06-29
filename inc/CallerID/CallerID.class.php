<?php

class CallerID {
    // could easily check folders automatically to find dataProviders
    protected static $dataProvider = 'CSVLookup,CountryCodes';
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

    public static function getCallerId($number) {
        $number = self::canonizeNumber($number);
        $provider = explode(',', self::$dataProvider);

        foreach ($provider as $p) {
            include_once(dirname(__FILE__) . '/' . $p . '/ResolverBridge.php');
            $classname = __CLASS__ . '_' . $p;
            if (!class_exists($classname)) continue;

            $info = call_user_func(array($classname, 'lookupNum'), $number);
            if ($info !== false) return $info;
        }

        return false;
    }
}

?>