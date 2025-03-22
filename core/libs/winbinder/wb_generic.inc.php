<?php

/*******************************************************************************

 WINBINDER - The native Windows binding for PHP for PHP

 Copyright Hypervisual - see LICENSE.TXT for details
 Author: Rubem Pechansky (https://github.com/crispy-computing-machine/Winbinder)

 General-purpose supporting functions

*******************************************************************************/

//-------------------------------------------------------------------- FUNCTIONS

/**
 * Retrieves all files in a directory and optionally its subdirectories.
 * 
 * This function scans a specified directory and returns an array of file paths.
 * It can include files from subdirectories recursively and apply a regular expression
 * filter to include only matching files.
 *
 * @param string $path The directory path to scan.
 * @param bool $subdirs Whether to include files from subdirectories (default: false).
 * @param bool $fullname Whether to return full paths or just filenames (default: true).
 * @param string $mask A PCRE regular expression to filter filenames (default: "").
 * @param bool $forcelowercase Whether to convert filenames to lowercase (default: true).
 * 
 * @return array An array of file paths matching the criteria.
 * 
 * @example
 * // Get all files in current directory
 * $files = get_folder_files('.'); 
 * 
 * // Get all files in subdirectories
 * $files = get_folder_files('.', true); 
 * 
 * // Get files with .php extension
 * $files = get_folder_files('.', false, true, '/\.php$/');
 */
function get_folder_files($path, $subdirs=false, $fullname=true, $mask="", $forcelowercase=TRUE)
{
	// Correct path name, if needed

	$path = str_replace('/', '\\', $path);
	if(substr($path, -1) != '\\')
		$path .= "\\";
	if(!$path || !@is_dir($path))
		return array();

	// Browse the subdiretory list recursively

	$dir = array();
	if($handle = opendir($path)) {
		while(($file = readdir($handle)) !== false) {
			if(!is_dir($path.$file)) {	// No directories / subdirectories
				if($forcelowercase)
					$file = strtolower($file);
				if(!$mask) {
					$dir[] = $fullname ? $path.$file : $file;
				} else if($mask && preg_match($mask, $file)) {
					$dir[] = $fullname ? $path.$file : $file;
				}
			} else if($subdirs && $file[0] != ".") {	// Exclude "." and ".."
				$dir = array_merge($dir, get_folder_files($path.$file, $subdirs, $fullname, $mask));
			}
		}
	}
	closedir($handle);
	return $dir;
}

//-------------------------------------------------------------------- INI FILES

/**
 * Converts an array into a formatted INI file string.
 * 
 * This function takes an associative array and converts it into a string
 * formatted as an INI file. It handles escaping of special characters
 * and can include a comment header.
 *
 * @param array $data The associative array to convert.
 * @param string $comments Optional header comments for the INI file (default: "").
 * 
 * @return string|null The INI-formatted string, or null if input is not an array.
 * 
 * @example
 * // Example usage
 * $data = array(
 *     'section1' => array(
 *         'key1' => 'value1',
 *         'key2' => 123,
 *     ),
 *     'section2' => array(
 *         'key3' => 'value with spaces',
 *     ),
 * );
 * 
 * $iniContent = generate_ini($data, '# Custom INI file');
 */
function generate_ini($data, $comments="")
{
	if(!is_array($data)) {
		trigger_error(__FUNCTION__ . ": Cannot save INI file.");
		return null;
	}
	$text = $comments;
	foreach($data as $name=>$section) {
		$text .= "\r\n[$name]\r\n";

		foreach($section as $key=>$value) {
			$value = trim($value);
			if((string)((int)$value) == (string)$value)			// Integer: does nothing
				;
			elseif((string)((float)$value) == (string)$value)	// Floating point: does nothing
				;
			elseif($value === "")								// Empty string
				$value = '""';
			elseif(strstr($value, '"'))							// Escape double-quotes
				$value = '"' . str_replace('"', '\"', $value) . '"';
			else
				$value = '"' . $value . '"';

			$text .= "$key = " . $value . "\r\n";
		}
	}
	return $text;
}

/**
 * Parses an INI formatted string into an associative array.
 * 
 * This function reads an INI-formatted string and converts it into an
 * associative array. It handles escaped double quotes and can optionally
 * convert certain keywords to their boolean or null equivalents.
 * Replaces function parse_ini_file() so INI files may be processed more similarly to Windows.
 * Replaces escaped double-quotes (\") with double-quotes (").
 *
 * @param string $initext The INI-formatted string to parse.
 * @param bool $changecase Whether to convert section names to title case and keys to lowercase (default: true).
 * @param bool $convertwords Whether to convert special keywords to their respective values (default: true).
 * 
 * @return array The parsed data as an associative array.
 * 
 * @example
 * // Example usage
 * $iniContent = <<<INI
 * [Section1]
 * key1 = value1
 * key2 = 123
 * key3 = true
 * key4 = null
 * 
 * [Section2]
 * key5 = "quoted value"
 * key6 = "value with \" escaped quote"
 * INI;
 * 
 * $parsedData = parse_ini($iniContent);
 */
function parse_ini($initext, $changecase=TRUE, $convertwords=TRUE)
{
	$ini = preg_split("/\r\n|\n/", $initext);
	$secpattern = "/^\[(.[^\]]*)\]/i";
//	$entrypattern = "/^([a-z_0-9]*)\s*=\s*\"?([^\"]*)?\"?" . '$' . "/i";
//	$strpattern = "/^\"?(.[^\"]*)\"?" . '$' . "/i";
	$entrypattern = "/^([a-z_0-9]*)\s*=\s*\"?([^\"]*)?\"?\$/i";
	$strpattern = "/^\"?(.[^\"]*)\"?\$/i";

	$section = array();
	$sec = "";

	// Predefined words

	static $words  = array("yes", "on", "true", "no", "off", "false", "null");
	static $values = array(   1,    1,      1,    0,     0,       0,   null);

	// Lines loop

	for($i = 0; $i < count($ini); $i++) {

		$line = trim($ini[$i]);

		// Replaces escaped double-quotes (\") with special signal /%quote%/

		if(strstr($line, '\"'))
			$line = str_replace('\"', '/%quote%/', $line);

		// Skips blank lines and comments

		if($line == "" || preg_match("/^;/i", $line))
			continue;

		if(preg_match($secpattern, $line, $matches)) {

			// It's a section

			$sec = $matches[1];

			if($changecase)
				$sec = ucfirst(strtolower($sec));

			$section[$sec] = array();

		} elseif(preg_match($entrypattern, $line, $matches)) {

			// It's an entry

			$entry = $matches[1];

			if($changecase)
				$entry = strtolower($entry);

			$value = preg_replace($entrypattern, "\\2", $line);

			// Restores double-quotes (")

			$value = str_replace('/%quote%/', '"', $value);

			// Convert some special words to their respective values

			if($convertwords) {
				$index = array_search(strtolower($value), $words);
				if($index !== false)
					$value = $values[$index];
			}

			$section[$sec][$entry] = $value;

		} else {

			// It's a normal string

			$section[$sec][] = preg_replace($strpattern, "\\1", $line);

		}
	}
	return $section;
}

//------------------------------------------------------------------ END OF FILE
