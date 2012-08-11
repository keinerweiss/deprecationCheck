deprecationCheck
================

Locate @decprecate annotations in a php project
-----------------------------------------------

These scripts were primarly created to locate extension 
incompatibility to new TYPO3 versions

Checks a directory for PHP files containing functions that have been marked
to be deprecated via doccomment. It outputs a csv stream that can be 
piped to a file.

Utilizes Linux grep on command shell.

Note that the result will be incomplete since this is kind of a signature 
checking of file contents where certain signatures are assumed.
Nevertheless, the result is rich, even though something might have been missed. 

Resulting CSV file format is:
  File;Line;Class;Function;Variable;Static 0/1;Class removed 0/1;Function removed 0/1;Variable removed 0/1;Deprecation Message

Usage:
  php locateDeprecates.php /path/to/dir > allDeprecates.csv  


Check files for calls to the located functions
----------------------------------------------

Checks a directory for PHP files containing functions that have been found
to be deprecated. Utilizes a csv file generated with locateDeprecates.php 

Utilizes Linux grep on command shell.

The result is a report of propably incompatible files.
Note that only the static function call can be trusted, other tests
are considered to be "hints to check if really ..."

Expected file format is at least:
  File;Line;Class;Function;Variable 1/0;Static 1/0

Resulting CSV file format is:
  File;Line;Class;Function;Variable;Static 0/1;Class removed 0/1;Function removed 0/1;Variable removed 0/1;Deprecation Message

Usage:
  php checkForUsedDeprecates.php /path/to/dir allDeprecates.csv

