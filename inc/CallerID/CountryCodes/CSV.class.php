<?php

/**
 * CSV class for easy use of CSV files as tables
 *
 * @author Markus Birth, mbirth@webwriters.de
**/

if (class_exists('CSV')) return;
ini_set('auto_detect_line_endings', '1');

class CSV {

    /** CSV formatting variables */
    protected $csv_delim = ',';
    protected $csv_stren = '"';
    protected $csv_decim = '.';
    protected $csv_escap = '\\';

    /** table-filename, read-only- and headers-flag */
    protected $file, $readonly;
    protected $useheaders = false;
    protected $autoconvert = false;

    /** table contents */
    protected $data, $headers;

    /** holds allowed characters for numerical values */
    protected $num_allowed = '0123456789.';

    private static $lfw_staleAge = 5;  // staleAge in seconds for locked filewrite
    private static $lfw_timeLimit = 300000; // time limit in �s to try to gain lock

    function __construct() {
    }

    function setDecimal($nd) {
        if (strlen($nd) != 1) return false;
        $this->csv_decim = $nd;
        $this->num_allowed = '0123456789' . $nd;
        return true;
    }

    function setStringEnclosure($enc) {
        if (strlen($enc) != 1) return false;
        $this->csv_stren = $enc;
        return true;
    }

    function setDelimiter($deli) {
        if (strlen($deli) != 1) return false;
        $this->csv_delim = $deli;
        return true;
    }

    /**
     * creates a new table; empty or from given array via <code>$init</code>
     *
     * @public
     * @param $table name of new table
     * @param $init array with initial table (if omitted or not an array, an empty table is created)
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function create($file, $init = false) {
        if (isset($this->file) || isset($this->data)) return false;
        $this->file = $file;
        $this->readonly = false;
        $this->data = array();
        if ($init !== false) {
            if (!is_array($init)) $init = array($init);
            foreach ($init as $tmp) {
                if (!is_array($tmp)) $tmp = array($tmp);
                array_push($this->data, $tmp);
            }
        } else {
            $this->data = array();
        }
        return true;
    }

    /**
     * loads a table from CSV file
     *
     * @public
     * @param $table name of table to load (without '.csv'!)
     * @param $readonly if true, loads table for read-only (defaults to false)
     * @param $delim CSV delimiter (defaults to ',')
     * @param $stren CSV string encloser (defaults to '"')
     * @param $decim Decimal separator (defaults to '.')
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function load($file, $readonly = false) {
        if (isset($this->file) || isset($this->data) || !file_exists($file)) return false;
        $this->data = array();
        $this->file = $file;
        $this->readonly = $readonly;
        $f = fopen($this->file, 'rt');
        if ($f === false) return false;
        $safety = -1;
        while (($temp = fgetcsv($f, filesize($this->file), $this->csv_delim, $this->csv_stren)) !== false) {
            $temp = preg_replace('/\\\([^\\\])/', '\\1', $temp);
            $temp = str_replace('\\\\', '\\', $temp);
            $this->data[] = $temp;
            if (ftell($f) == $safety) break;  // loop
            $safety = ftell($f);
        }
        fclose($f);
        return true;
    }

    /**
     * sets whether to treat all fields as string or as specific types
     *
     * @public
     * @param $acflag new boolean value
     */
    function setAutoConvert($acflag) {
        if (!is_bool($acflag)) return false;
        $this->autoconvert = $acflag;
        return true;
    }

    /**
     * sets whether to use headers in first data row for description of fields
     * or use 0..n as field descriptors. Doesn't affect the $this->data object.
     *
     * @public
     * @param $hflag new boolean value
     */
    function setUseHeaders($hflag) {
        if (!is_bool($hflag)) return false;
        if ($this->useheaders === $hflag) return true;
        if ($hflag) {
            // Apply headings from first row to all entries
            // and remove first row
            $heads = array_shift($this->data);
            foreach ($this->data as $i=>$r) {
                $r2 = array();
                foreach ($r as $j=>$d) {
                    $r2[$heads[$j]] = $d;
                }
                $this->data[$i] = $r2;
            }
        } else {
            // Extract headings from longest data record
            // and prepend header line to data array
            $maxrec = 0;
            $maxct = 0;
            foreach ($this->data as $i=>$d) {
                if (count($d)>$maxct) {
                    $maxct = count($d);
                    $maxrec = $i;
                }
            }
            $heads = array();
            foreach ($this->data[$maxrec] as $h=>$f) {
                $heads[] = $h;
            }
            foreach ($this->data as $i=>$r) {
                $r2 = array();
                foreach ($heads as $h) {
                    $r2[] = $r[$h];
                }
                $this->data[$i] = $r2;
            }
            array_unshift($this->data, $heads);
        }
        $this->useheaders = $hflag;
        return true;
    }

    /**
     * saves currently open table to CSV file
     * (locked filewrite code from http://de2.php.net/manual/en/function.flock.php#46085)
     *
     * @public
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function save() {
        if ($this->readonly === true || !isset($this->file) || !isset($this->data)) return false;
        $headerstate = $this->useheaders;
        if ($this->useheaders === true) {
            // prepend headers if used
            $this->setUseHeaders(false);
        }

        // BEGIN: Gain lock
        ignore_user_abort(true);
        $lockdir = $this->file . '.lock';
        if (is_dir($lockdir)) {
            if ((time() - filemtime($lockdir)) > self::$lfw_staleAge) {
                rmdir($lockdir);
            }
        }
        $locked = @mkdir($lockdir);
        if ($locked === false) {
            $timestart = microtime(true);
            do {
                if ((microtime(true) - $timestart) > self::$lfw_timeLimit) {
                    ignore_user_abort(false);
                    $this->setUseHeaders($headerstate);
                    return false;
                }
                $locked = @mkdir($lockdir);
            } while ($locked === false);
        }
        // END: Gain lock

        $f = fopen($this->file, 'wt');
        foreach ($this->data as $line) {
            if (count($line)>0 && strlen(implode('', $line))>0) {
                $temp = array();
                foreach ($line as $tmp) {
                    if (!is_numeric($tmp) && !is_bool($tmp)) {
                        if (strlen($this->csv_stren)>0) $tmp = strtr($tmp, array('\\'=>'\\\\', $this->csv_stren=>'\\'.$this->csv_stren));
                        $temp[] = $this->csv_stren . $tmp . $this->csv_stren;
                    } elseif (is_numeric($tmp)) {
                        if (is_float($tmp)) {
                            $temp[] = strtr((string)$tmp, ".", $this->csv_decim);
                        } else {
                            $temp[] = $tmp;
                        }
                    } elseif (is_bool($tmp)) {
                        if ($tmp==true) {
                            $temp[] = 'true';
                        } else {
                            $temp[] = 'false';
                        }
                    }
                }
                if (!fwrite($f, implode($this->csv_delim, $temp) . "\n")) {
                    $this->setUseHeaders($headerstate);   // restore previous state
                    ignore_user_abort(false);
                    return false;
                }
            }
        }
        fclose($f);
        rmdir($lockdir);
        ignore_user_abort(false);
        $this->setUseHeaders($headerstate);   // restore previous state
        return true;
    }

    /**
     * closes currently open table
     *
     * @public
     * @param $save if true, writes table to CSV file before close (defaults to true)
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function close($save = true) {
        if (!isset($this->file) || !isset($this->data)) return false;
        if ($save===true && !$this->readonly) {
            if (!$this->save()) return false;
        }
        unset($this->file);
        unset($this->readonly);
        unset($this->data);
        return true;
    }

    function sortRows($keycol, $rev = false) {
        if (is_string($keycol)) {
            if ($this->useheaders === false) {
                $keycol = array_search($keycol, $this->data[0]);
                if (!$keycol) return false;
            }

        }
        $sortme = array();
        foreach ($this->data as $i=>$d) {
            $sortme[$i] = $d[$keycol];
        }
        asort($sortme);
        $newdata = array();
        if ($this->useheaders === false) {
            $newdata[0] = $this->data[0];
        }
        foreach ($sortme as $i=>$d) {
            if ($i==0 && $this->useheaders === false) continue;
            $newdata[] = $this->data[$i];
        }
        if ($rev === true) $newdata = array_reverse($newdata);
        $this->data = $newdata;
        return true;
    }

    function getTable() {
        if (!isset($this->data)) return false;
        return $this->data;
    }

    function setTable($tbl) {
        if (!is_array($tbl) || !is_array(reset($tbl))) return false;
        $this->data = $tbl;
        return true;
    }

    /**
     * returns an array with all elements of a complete row
     *
     * @public
     * @param $rid id of the row
     * @return array with elements if successful; <code>false</code> if failed
     */
    function getRow($rid) {
        if (!isset($this->data[$rid])) return false;
        if (!$this->autoconvert) return $this->data[$rid];

        $result = array();
        foreach ($this->data[$rid] as $i=>$d) {
            $result[$i] = $this->guessType($d);
        }
        return $result;
    }

    /**
     * returns a single cell value
     *
     * @public
     * @param $rid id of the row
     * @param $cid id of the column
     * @return cell contents if successful; <code>false</code> if failed
     */
    function getRowCol($rid, $cid) {
        if (!isset($this->data[$rid][$cid])) return false;
        if (!$this->autoconvert) return $this->data[$rid][$cid];

        return $this->guessType($this->data[$rid][$cid]);
    }

    /**
     * Guesses type of a value by its content
     *
     * @public
     * @param $val String to guess type
     * @return String, (int) or (float)
     */
    function guessType($val) {
        $commas = 0;
        for ($i=0;$i<strlen($val);$i++) {
            $c = $val{$i};
            if (strpos($this->num_allowed, $c) === false) return $val;
            if ($c == $this->csv_decim) $commas++;
        }
        $val = strtr($val, $this->csv_decim, '.');
        if ($commas>1) return $val;
        if ($commas==1) return floatval($val);
        return intval($val);
    }

    /**
     * Guesses type for the whole database
     *
     * @public
     */
    function guessTypeAll() {
        foreach ($this->data as $i=>$r) {
            foreach ($r as $j=>$d) {
                $this->data[$i][$j] = $this->guessType($d);
            }
        }
    }

    /**
     * writes a value to a single cell
     *
     * @public
     * @param $rid id of the row
     * @param $cid id of the column
     * @param $val new value to write to cell
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function setRowCol($rid, $cid, $val) {
        $this->data[$rid][$cid] = $val;
    }

    /**
     * writes an array to a row
     *
     * @public
     * @param $rid id of the row
     * @param $line array with column contents to write to line
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function setRow($rid, $line) {
        if (!is_array($line)) return false;
        $this->data[$rid] = $line;
        return true;
    }

    /**
     * deletes a row
     *
     * @public
     * @param $rid id of the row
     * @return <code>true</code> if successful; <code>false</code> if failed
     */
    function delRow($rid) {
        if (!isset($this->data[$rid])) return false;
        unset($this->data[$rid]);
        return true;
    }
}

?>
