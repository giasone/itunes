<?php
/**
 * File: api-itunes
 * 	Handle the iTunes Store API.
 *
 * Version:
 * 	2009.10.26
 *
 * Copyright:
 * 	2009 Ryan Parman
 *
 * License:
 * 	Simplified BSD License - http://opensource.org/licenses/bsd-license.php
 */


/*%******************************************************************************************%*/
// CONSTANTS

/**
 * Constant: ITUNES_NAME
 * 	Name of the software.
 */
define('ITUNES_NAME', 'api-itunes');

/**
 * Constant: ITUNES_VERSION
 * 	Version of the software.
 */
define('ITUNES_VERSION', '1.0');

/**
 * Constant: ITUNES_BUILD
 * 	Build ID of the software.
 */
define('ITUNES_BUILD', gmdate('YmdHis', strtotime(substr('$Date$', 7, 25)) ? strtotime(substr('$Date$', 7, 25)) : filemtime(__FILE__)));

/**
 * Constant: ITUNES_URL
 * 	URL to learn more about the software.
 */
define('ITUNES_URL', 'http://github.com/skyzyx/itunes/');

/**
 * Constant: ITUNES_USERAGENT
 * 	User agent string used to identify the software
 */
define('ITUNES_USERAGENT', ITUNES_NAME . '/' . ITUNES_VERSION . ' (iTunes Store Toolkit; ' . ITUNES_URL . ') Build/' . ITUNES_BUILD);


/*%******************************************************************************************%*/
// CLASS

/**
 * Class: iTunesStore
 */
class iTunesStore
{
	/**
	 * Property: subclass
	 * 	The API subclass (e.g. search, lookup) to point the request to.
	 */
	var $subclass;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Method: __construct()
	 * 	The constructor.
	 *
	 * Access:
	 * 	public
	 *
	 * Parameters:
	 * 	subclass - _string_ (Optional) Don't use this. This is an internal parameter.
	 *
	 * Returns:
	 * 	iTunesStore $this
	 */
	public function __construct($subclass = null)
	{
		// Set default values
		$this->subclass = $subclass;
	}


	/*%******************************************************************************************%*/
	// MAGIC METHODS

	/**
	 * Handle requests to properties
	 */
	function __get($var)
	{
		// Determine the name of this class
		$class_name = get_class($this);

		// Re-instantiate this class, passing in the subclass value
		return new $class_name($var);
	}

	/**
	 * Handle requests to methods
	 */
	function __call($name, $args)
	{
		// Change the names of the methods to match what the API expects
		$name = ucwords($name);
		$clean_args = array();

		// Convert any array values to comma-delimited strings
		foreach ($args[0] as $k => $v)
		{
			if (is_array($v))
			{
				$clean_args[$k] = implode(',', $v);
			}
			else
			{
				$clean_args[$k] = $v;
			}
		}

		// Construct the rest of the query parameters with what was passed to the method
		$fields = urldecode(http_build_query((count($clean_args) > 0) ? $clean_args : array(), '', '&'));

		// Construct the URL to request
		if ($this->subclass)
		{
			// viewArtist?id=909253
			$api_call = sprintf('http://phobos.apple.com/WebObjects/MZStore.woa/wa/' . $this->subclass . $name . '?%s', $fields);
		}
		else
		{
			$api_call = sprintf('http://ax.phobos.apple.com.edgesuite.net/WebObjects/MZStoreServices.woa/wa/ws' . $name . '?%s', $fields);
		}

		// Return the value
		return $api_call;
	}
}
