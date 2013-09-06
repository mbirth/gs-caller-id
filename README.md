Gemeinschaft 2.x - CallerID addon
=============================

©2009-2013 Markus Birth \<markus(at)birth-online.de>


1. INSTALLATION
---------------

* copy the directories `dialplan-scripts/` and `inc/` to `/opt/gemeinschaft/`
* open the file `/etc/asterisk/e-internal.ael`
* find the `context to-internal-users-self` (around line 478)
* in there, find the `to_user:` section
* before the `Dial(…)` command (around line 731), add the following line:

```
AGI(/opt/gemeinschaft/dialplan-scripts/in-get-callerid.agi,${CALLERID(num)});
```

so that it looks like this:

```
AGI(/opt/gemeinschaft/dialplan-scripts/in-get-callerid.agi,${CALLERID(num)});
Dial(SIP/${EXTEN}${pgrpdialstr},${dialtimeout});
```

* somewhat further down find the `to_queue:` section
* before the `Queue(…)` command (around line 912), add the line from above so that it looks like this:

```
AGI(/opt/gemeinschaft/dialplan-scripts/in-get-callerid.agi,${CALLERID(num)});
Set(queue_entertime=${EPOCH});
Queue(${EXTEN},${ring_instead_of_moh},,,${queuetimeout});
Set(queue_waittime=$[${EPOCH}-${queue_entertime}]);
```


2. CONFIGURATION
----------------

* open the file `inc/CallerID/CallerID.class.php`
* find the line that reads:

```php
protected static $dataProvider = 'CSVLookup,CountryCodes';
```

* set your preferred order of lookup (from left to right, separated by commas)
* the first match will be used
* ignore the `$countrycode` and `$areacode` as they will be overwritten by your
  Gemeinschaft canonization settings (make sure they are correct!)


### Available dataProviders:

#### CSVLookup
  - the csv-file for CSVLookup is `inc/CallerID/CSVLookup/telefonbuch.csv`
  - the numbers need to be formatted as your phone shows them (i.e. not in international format!)
  - the asterisk ("*") wildcard is allowed

#### CountryCodes
  - uses several official prefix lists to return country and sometimes the region of the caller
  - always returns a match, so dataProviders after this one will not be triggered

#### OnlineLookup
  - looks up the incoming number at different online directories
  - the definitions are in the file `inc/CallerID/OnlineLookup/jfritz-definitions.xml`
  - the XML file should be compatible to the [JFritz](http://jfritz.org/) version
