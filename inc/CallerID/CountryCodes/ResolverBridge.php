<?php

require_once(dirname(__FILE__) . '/../CallerIDResolver.interface.php');
require_once(dirname(__FILE__) . '/CountryCodes.class.php');

class CallerID_CountryCodes implements CallerIDResolver {

    public static function lookupNum($number) {
        $info = CountryCodes::lookupNum($number);

        /*
         * $info = array(
         *   'Calling Code' => '+493322',
         *   'Country'      => 'Deutschland',
         *   'CC'           => 'DE',
         *   'District'     => 'Falkensee',
         *   'flag'         => '',
         * );
         */

         $result = $info['CC'];
         if (!empty($info['District'])) $result .= ', ' . $info['District'];

        return $result;
    }
    
}

?>