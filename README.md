# iTunes Store

This API will construct the appropriate iTunes Store API URL to query, and use RequestCore and JSON to retrieve and parse the data.

It takes advantage of PHP's magic methods to make a more intuitive interface for developers: rather than learning a new set of methods, you just use the service's native methods.

You can also request the actual store pages that you typically see when using the iTunes client. These are cleaned up with Tidy and parsed with SimpleXML so you can grab additional data.

## Requirements

This class is built on top of [ServiceCore](http://github.com/skyzyx/servicecore), and therefore shares it's requirements.

## Setup

	git clone git://github.com/skyzyx/itunes.git
	cd itunes
	git submodule update --init --recursive

The `--recursive` option was added in a 1.6.x version of Git, so make sure you're running the latest version.

## Example usage

The iTunes web service APIs are documented here:

* <http://www.apple.com/itunesaffiliates/API/AffiliatesSearch2.1.pdf>
* <http://images.apple.com/itunesaffiliates/US/2009/Document/LinktoiTune.pdf>

If you want to search for a TV show episode, you'd do the following. This makes a request using [RequestCore](http://github.com/skyzyx/requestcore), defaults to JSON response, and parses it with `json_decode()`.

	$itunes = new iTunesStore();
	$response = $itunes->search(array(
		'term' => 'smallville absolute justice',
		'media' => 'tvShow',
		'entity' => 'tvEpisode'
	));
	print_r($response);

Or search for a movie:

	$itunes = new iTunesStore();
	$response = $itunes->search(array(
		'term' => 'knocked up',
		'media' => 'movie'
	));
	print_r($response);

Or lookup an artist:

	$itunes = new iTunesStore();
	$response = $itunes->lookup(array(
		'id' => '889780',
		'media' => 'music'
	));
	print_r($response);

You can look through the response to see how to traverse through the data.

### More advanced example

Here, we'll search for an episode of Smallville then pull the large-sized image from the upper-left of the iTunes Store page.

	// Lookup the data
	$itunes = new iTunesStore();
	$query = $itunes->search(array(
		'term' => 'smallville absolute justice',
		'media' => 'tvShow',
		'entity' => 'tvEpisode'
	));

	// Get the iTunes URL
	$itunes_url = $query->body->results[0]->trackViewUrl;

	// Pretend to be iTunes to get the URL for the store version of the page.
	$plist_data = $itunes->request_storefront($itunes_url);
	$plist_data = new SimpleXMLElement($plist_data->body, LIBXML_NOCDATA);
	$itunes_store_url = (string) $plist_data->dict->dict->string[1];

	// Fetch and parse the iTunes Store version of the page.
	$itunes_store_page = $itunes->request_storefront($itunes_store_url);
	$page_xml = $itunes->clean_with_tidy_and_parse_as_xml($itunes_store_page->body); # Requires the Tidy extension

	// Search for all HTML <img> tags.
	$image_search_results = $page_xml->xpath('//img');
	$big_image_url = (string) $image_search_results[0]->attributes()->src;

	// Display the image
	echo '<img src="' . $big_image_url . '" />';

You would also put this API in Test Mode if you wanted to use your own HTTP and parsing classes. To use a different HTTP request/response class, you would override the <code>request()</code> method. To change how the data was parsed, you would override the <code>parse_response()</code> method.

## License & Copyright

This code is Copyright (c) 2009-2010, Ryan Parman. However, I'm licensing this code for others to use under the [MIT license](http://www.opensource.org/licenses/mit-license.php).
