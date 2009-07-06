<?php

require_once(dirname(__FILE__) . '/../CallerIDResolver.interface.php');
require_once(dirname(__FILE__) . '/CSV.class.php');

class CallerID_CSVLookup implements CallerIDResolver {
    const CSV_FILE = 'telefonbuch.csv';

    protected static function localizeNumber($number) {
        // convert +XX to 00XX or omit if local
        if ($number{0} == '+') {
            if (substr($number, 1, strlen(CallerID::$countrycode)) == CallerID::$countrycode) {
                $number = '0' . substr($number, 1+strlen(CallerID::$countrycode));
            } else {
                $number = '00' . substr($number, 1);
            }
        }
        return $number;
    }

    public static function lookupNum($number) {
        $csv = new CSV();
        $csv->setDelimiter(';');
        $csv->load(dirname(__FILE__) . '/' . self::CSV_FILE, true);

        $data = $csv->getTable();

        $csv->close();

        $number = self::localizeNumber($number);
        $wildmatch = false;

        foreach ($data as $entry) {
            // CSV format: Name;Number
            if ( $entry[1] == $number ) {
                // perfect match - return directly
                return $entry[0];
            } elseif ( fnmatch($entry[1], $number) ) {
                // wildmatch - memorize and continue search for perfect match
                $wildmatch = $entry[0];
            }
        }

        return $wildmatch;
    }

}

?>
