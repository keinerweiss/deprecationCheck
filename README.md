deprecationCheck
================

These scripts were primarly created to locate extension 
incompatibility to new TYPO3 versions

Note that the algorithm is kind of a signature check of file contents.
The result will probably be incomplete since certain signatures are assumed.

Nevertheless, the result may help, even though something might have been missed. 


Locate @decprecate annotations in a php project
-----------------------------------------------

Checks a directory for PHP files containing functions that have been marked
to be deprecated via doccomment. It outputs a csv stream that can be 
piped to a file.

It utilizes Linux `grep` on command shell.

The resulting CSV file format is:
	File;Line;Class;Function;Variable;Static 0/1;Class removed 0/1;Function removed 0/1;Variable removed 0/1;Deprecation Message

Usage:
	php locateDeprecates.php /path/to/dir > allDeprecates.csv  


Check files for calls to the located functions
----------------------------------------------

Checks a directory for PHP files containing functions that have been found
to be deprecated. It uses a csv file generated with locateDeprecates.php 

It utilizes Linux `grep` on command shell.

The result is a report of propably incompatible files.
Note that only the static function calls can really be trusted, other tests
are considered to be "hints to check if really ..." since function names 
may be used across many classes in same notation.

Expected file format is at least:
	File;Line;Class;Function;Variable;Static 1/0

Resulting CSV file format is:
	File;Line;Class;Function;Variable;Static 0/1;Class removed 0/1;Function removed 0/1;Variable removed 0/1;Deprecation Message

Usage:
	php checkForUsedDeprecates.php /path/to/dir allDeprecates.csv

