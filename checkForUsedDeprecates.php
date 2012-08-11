<?php
/**
 * Checks a directory for PHP files containing functions that have been found
 * to be deprecated. Utilizes a csv file generated with locateDeprecates.php 
 * 
 * Utilizes Linux grep on command shell.
 * 
 * The result is a report of propably incompatible files.
 * Note that only the static function call can be trusted, other tests
 * are considered to be "hints to check if really ..."
 * 
 * Expected file format is at least:
 * File;Line;Class;Function;Variable 1/0;Static 1/0
 * 
 * Resulting CSV file format is:
 * File;Line;Class;Function;Variable;Static 0/1;Class removed 0/1;
 *   Function removed 0/1;Variable removed 0/1;Deprecation Message
 * 
 * Usage:
 * php checkForUsedDeprecates.php /path/to/dir allDeprecates.csv
 */

$dir = $argv[1];
$deprecationList = $argv[2];

$fp = fopen($deprecationList,'r');

$problems = array();

/**
 * Create direct CSV output instead of poluting memory
 */
echo "Call;Filename;Line;Code;Reason\r\n";

/**
 * Take each deprecated signature of the list and run a directory wide 
 * grep for it.
 */
while($row = fgetcsv($fp,1024,';')) {
	$calls = array();
	$static = $row[4];
	$class = $row[2];
	$method = $row[3];
	$reason = $row[5];
	
	/**
	 * grep for signatures of
	 * - static method calls
	 * - method calls (fuzziness implied, same function elsewhere is possible)
	 * - class instatiation via factory (in quotes)
	 */
	if($static && $class && $method) {
		$grep = 'grep -rne \''.escapeshellarg($class.'::'.$method).'(\' '.escapeshellarg($dir);
	} elseif($class && $method) {
		$grep = 'grep -rne \'->'.escapeshellarg($method).'(\' '.escapeshellarg($dir);
	} elseif($class && !$method) {
		// might be the whole class or variable
		$grep = 'grep -rne "[(]\''.escapeshellarg($class).'\'" '.escapeshellarg($dir);
	} else {
		continue;
	}
	exec($grep, $calls);
	
	if(!count($calls)) continue;
	
	/**
	 * Analyze each call for happening in a php file, not being commented out.
	 * Comment check is fuzzy because multiline comments are not covered. 
	 */
	foreach($calls as $grepMatch) {
		/**
		 * Eliminate typical false positives due to to general naming 
		 * Fuzziness: these functions are not being checked for deprecation
		 */
		if($method == 'stdWrap') continue;
		if($method == '__construct') continue;
		if($method == 'render') continue;

		/**
		 * Extracting grep match information 
		 */
		$parts = explode(':',$grepMatch,3);
		$filename = realpath($parts[0]);
		$line = $parts[0];
		if(substr($filename,-3) != 'php') continue;
		
		/** 
		 * Fixing a Mac-linebreak-issue polluting the result
		 * If linebreak \r is used the match line contains the whole file.
		 * Fuzziness: in this case only one appearance could have been 
		 * found in the file
		 */
		$code = explode("\r",$parts[2]);
		$code = $parts[2][0];
		$code = trim($parts[2]);
		
		/**
		 * Eliminating code most probably being commented out 
		 */
		if(strpos($code,'//') === 0) continue;
		if(strpos($code,'#') === 0) continue;
		if(strpos($code,'* ') === 0) continue;
		if(strpos($code,'/*') === 0) continue;
		
		/*
		if(preg_match('/\/ext\/([^\/]*)/', realpath($parts[0]), $ext)) {
			$ext = $ext[1];
		} else {
			$ext = 'extNoMatch';
		}
		*/
		echo "$class.'::'.$method;\"$filename\";$line;\"$code\";\"$reason\"\r\n";
	}
}
fclose($fp);
