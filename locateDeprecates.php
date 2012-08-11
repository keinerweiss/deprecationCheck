<?php
/**
 * Checks a directory for PHP files containing functions that have been marked
 * to be deprecated via doccomment. It outputs a csv stream that can be 
 * piped to a file 
 * 
 * Utilizes Linux grep on command shell.
 * 
 * Note that the result will be incomplete since this is kind of a signature 
 * checking of file contents where certain signatures are assumed.
 * Nevertheless, the result is rich, even though something might have been missed. 
 * 
 * Resulting CSV file format is:
 * File;Line;Class;Function;Variable;Static 0/1;Class removed 0/1;
 *   Function removed 0/1;Variable removed 0/1;Deprecation Message
 * 
 * Usage:
 * php locateDeprecates.php /path/to/dir > allDeprecates.csv
 */
$dir = $argv[1];
$prevLine = array();
$prevFilename = '';
$content = '';
$annotations = array();

/**
 * Execute a wide grep to find all deprecations
 * pretty fast and straight forward (Linux limitation)
 */ 
exec('grep -rne \'* @deprecated\' '.$dir.'', $annotations);

/**
 * Create direct CSV output instead of poluting memory
 */
echo "File;Line;Class;Function;Variable;Static;Class removed;Function removed;Variable removed;Message\r\n";
echo "\r\n";

foreach($annotations as $grepMatch) {
	/**
	 * Take the textual grep result and split up information segments
	 * Only take PHP file matches into account
	 */
	$parts = explode(':', $grepMatch,3);
	$filename = $parts[0];
	if(substr($filename,-3) != 'php') continue;
	$line = $parts[1];
	$message = str_replace("\t"," ",trim($parts[2]," *\t"));
	$filenameClean = substr($filename, strlen($dir));

	/**
	 * Keeping track of the last analyzed line saves reverse-lookup time 
	 * the farer down we get in one file
	 */
	if(!isset($prevLine[$filename])) {
		$prevLine[$filename] = 0;
		$class = '';
	}

	/**
	 * Getting the file content line by line
	 */
	if($filename != $prevFilename) {
		$content = file($filename);
	}
	$prevFilename = $filename;

	/**
	 * Initialize all information, we will aggregate, to zero
	 */
	$function = '';
	$static = false;
	$variable = '';
	$functionRemoved = false;
	$variableRemoved = false;
	$classRemoved = false;

	/**
	 * Check if in the prior 2 lines a var-annotation exists,
	 * assuming this deprecation describes a variable that will be removed.
	 * This assumes, the variable declaration is 2 lines below the 
	 * the deprecate annotation
	 */
	if(preg_match('/\* @var /',$content[$line-1].$content[$line-2],$match)) {
		$variableRemoved = true;
		$variable = $content[$line+2];
	}

	/**
	 * Check up to 10 lines forward for keywords that identify what
	 * this deprecate-annotation is connected to. An interface, 
	 * class or function
	 */
	for($j=0;$j<10;$j++) {
		if(!isset($content[$line+$j])) break;
		if(preg_match('/([^\$]|^)(class|interface) (.*?) .*?\{/',$content[$line+$j],$match)) {
			$classRemoved = true;
			$class = trim($match[3]);
			$prevLine[$filename] = $line+$j;
			break;
		}
		if(preg_match('/function\s+?(.*?)\(.*?{/',$content[$line+$j],$match)) {
			$static = strpos($content[$line+$j], ' static ') !== FALSE;
			$function = trim($match[1]);
			$functionRemoved = true;
			break;
		}
	}

	/**
	 * Determining the class or interface where a found function 
	 * is located in.
	 */
	for($j=$line; ($function && $j>$prevLine[$filename]); $j--) {
		if(preg_match('/([^\$]|^)(class|interface) (.*?) .*?\{/', $content[$j],$match)) {
			$class = trim($match[3]);
			$prevLine[$filename] = $j;
			break;
		}
	}

	/*
	// collect data to memory - not good for huge runs
	if($class && ($function || $variable)) {
		$deprecations[$class][] = array('class'=>$class, 'method'=>$function,'static'=>$static);
	}
	*/

	/**
	 * Writing plain CSV line with the aggregated information
	 */
	echo "$filenameClean;$line;$class;$function;$variable;$static;$classRemoved;$functionRemoved;$variableRemoved;\"$message\"\r\n";
}
