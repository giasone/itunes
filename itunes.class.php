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
		// Init
		$clean_args = array();
		$tv = '';

		// We need to handle TV stuff differently
		if (strpos($name, 'tv') === 0)
		{
			$name = substr($name, 2);
			$tv = 'TV';
		}

		// Change the names of the methods to match what the API expects
		$name = $tv . ucwords($name);

		// Convert any array values to comma-delimited strings
		foreach ($args[0] as $k => $v)
		{
			if (is_array($v))
			{
				$clean_args[$k] = urldecode(implode(',', $v));
			}
			else
			{
				$clean_args[$k] = $v;
			}
		}

		// Construct the rest of the query parameters with what was passed to the method
		$fields = http_build_query((count($clean_args) > 0) ? $clean_args : array(), '', '&');

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
		return $this->request_json($api_call);
	}


	/*%******************************************************************************************%*/
	// REQUEST/RESPONSE

	/**
	 * Method: request_json()
	 * 	Requests the JSON data, parses it, and returns it. Requires RequestCore and JSON.
	 *
	 * Parameters:
	 * 	url - _string_ (Required) The web service URL to request.
	 *
	 * Returns:
	 * 	ResponseCore object
	 */
	public function request_json($url)
	{
		if (class_exists('RequestCore'))
		{
			$http = new RequestCore($url);
			$http->send_request();

			$response = new stdClass();
			$response->header = $http->get_response_header();
			$response->body = json_decode($http->get_response_body());
			$response->status = $http->get_response_code();

			return $response;
		}

		throw new Exception('This class requires RequestCore. http://requestcore.googlecode.com');
	}

	/**
	 * Method: request_storefront()
	 * 	Requests the actual iTunes Store page, parses it to XML, and returns it. Requires RequestCore, SimpleXML and Tidy.
	 *
	 * Parameters:
	 * 	url - _string_ (Required) The web service URL to request.
	 *
	 * Returns:
	 * 	ResponseCore object
	 */
	public function request_storefront($url)
	{
		if (class_exists('RequestCore'))
		{
			$http = new RequestCore($url);
			$http->add_header('X-Apple-Store-Front', '143441-1,5');
			$http->send_request();

			$response = new stdClass();
			$response->header = $http->get_response_header();
			$response->body = $http->get_response_body();
			$response->status = $http->get_response_code();

			return $response;
		}

		throw new Exception('This class requires RequestCore. http://requestcore.googlecode.com');
	}


	/*%******************************************************************************************%*/
	// UTILITY

	/**
	 * Method: clean_with_tidy_and_parse_as_xml()
	 * 	Cleans the give content with HTML Tidy, then parses it as XML with SimpleXML.
	 *
	 * Parameters:
	 * 	content - _string_ (Required) The content to parse, as a string.
	 *
	 * Returns:
	 * 	SimpleXMLElement The content parsed as XML.
	 */
	public function clean_with_tidy_and_parse_as_xml($content)
	{
		$tidy = tidy_parse_string($content, array('output-xml' => true, 'numeric-entities' => true), 'UTF8');
		$tidy->cleanRepair();
		return new SimpleXMLElement($tidy, LIBXML_NOCDATA);
	}

	/**
	 * Method: location_exists()
	 * 	Checks to see if a given web location exists.
	 *
	 * Parameters:
	 * 	url - _string_ (Required) The URL to request.
	 *
	 * Returns:
	 * 	boolean Whether the location exists or not (HTTP 200).
	 */
	public function location_exists($url)
	{
		if (class_exists('RequestCore'))
		{
			$http = new RequestCore($url);
			$http->set_method(HTTP_HEAD);
			$http->send_request();

			$response = $http->get_response_header('_info');
			return (boolean) $response['http_code'] == '200';
		}

		throw new Exception('This class requires RequestCore. http://requestcore.googlecode.com');
	}
}
