<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CSV.class.php');

class CountryCodes {

    protected static $loaded = array();
    protected static $data   = array();
    protected static $flagdir = 'flags/';

    static function loadData($cc) {
        if (in_array($cc, self::$loaded)) return false;
        $fnpre = dirname(__FILE__) . DIRECTORY_SEPARATOR . $cc;
        if (file_exists($fnpre . '.php')) {
            self::$data = array_merge(self::$data, include($fnpre . '.php'));
            array_push(self::$loaded, $cc);
            return true;
        }
        if (file_exists($fnpre . '.csv')) {
            $tbl = new CSV();
            $tbl->setDelimiter(';');
            $tbl->load($fnpre . '.csv');
            $tbl->setUseHeaders(true);
            foreach ($tbl->getTable() as $tbldata) {
                $pref = &$tbldata['Calling Code'];
                if ($cc{0} == '+') $pref = $cc . $pref;
                // Strip empty fields
                foreach ($tbldata as $i=>$d) {
                    if (empty($d)) unset($tbldata[$i]);
                }
                self::$data[$pref] = $tbldata;
            }
            $tbl->close();
            array_push(self::$loaded, $cc);
            return true;
        }
        return false;
    }

    static function lookupNum($num) {
        self::loadData('countries');
        $result = self::findBestMatch($num);
        if ($result === false) return false;
        if (self::loadData($result['Calling Code'])) {
            // new data loaded, find match again
            $result = self::findBestMatch($num);
        }
        $result['flag'] = self::findFlag($result['CC']);
        return $result;
    }

    static function findFlag($cc) {
        $cc = strtolower($cc);
        $filepre = self::$flagdir . $cc;
        if (file_exists($filepre . '.png')) return $cc . '.png';
        if (file_exists($filepre . '.gif')) return $cc . '.gif';
        return false;
    }

    static function findBestMatch($num) {
        $result = array();
        for ($i=strlen($num);$i>1;$i--) {
            $test = substr($num, 0, $i);
            if (isset(self::$data[$test])) {
                $result = array_merge(self::$data[$test], $result);
            }
        }
        if (count($result) == 0) return false;
        return $result;
    }

    static function getFlagDir() {
        return self::$flagdir;
    }

    static function setFlagDir($flagdir) {
        if (!file_exists($flagdir)) return false;
        self::$flagdir = $flagdir;
        if (substr(self::$flagdir, -1) != DIRECTORY_SEPARATOR) self::$flagdir .= DIRECTORY_SEPARATOR;
        return true;
    }
}

?>