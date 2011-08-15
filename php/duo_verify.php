<?php

// Define these based on your integration settings
define("HOST", "");
define("SKEY", "");
define("IKEY", "");
// Enter your phone number
define("PHONE", "");



/**
 * Example Functions
 *
 * The following functions will demonstrate how to sign and
 * make API calls.
 *
 * There is a demonstration below of the functionality,
 * just run this script from the command line to see.
 *
 */

/**
 * Request
 *
 * Creates an HTTPS request to the defined host.
 *
 * @param	String	$uri	String containing the API URI
 * @param	String	$method	String containing either "POST" or "GET"
 * @param	Array	$params	Key value pairs of strings
 * @return	String	JSON response from the server, error message if something is wrong.
 *
 */
function request($uri, $method, $params) {
	$url = "https://" . HOST . $uri;

	if ($method == "GET") {
		if ($params != NULL) {
			$url .= "?";
			foreach($params as $key => $value) {
				$url .= rawurlencode($key) . "=" . rawurlencode($value) . "&";
			}
			// Remove extra amperstand
			$url = substr($url, 0, -1);
		}
	}

	$sig = sign_request($method, HOST, $uri, $params);
	$ch  = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $sig));

	if ($method == "POST") {
		curl_setopt($ch, CURLOPT_POST, count($params));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	/*
	 * You should actually verify the certificate.
	 *
	 * @link
	 *	http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
	 */
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	// Execute the request and return the response.
	$req = curl_exec($ch);
	curl_close($ch);

	return $req;
}


/**
 * Canon
 *
 * Creates the string to be signed for the request.
 *
 * @return	String	String to be signed for the request.
 *
 */
function canon($method, $host, $path, $params) {
	$canon     = array(strtoupper($method), strtolower($host), $path);
	$param_str = "";

	// Create the parameter string
	if ($params != NULL) {

		// Make sure the keys are sorted!
		ksort($params);

		foreach($params as $key => $value) {
			$param_str .= rawurlencode($key) . "=" . rawurlencode($value) . "&";
		}

		// Remove the extra amperstand
		$param_str = substr($param_str, 0, -1);
	}

	// Join them all with new lines, append the param string
	$canon_str  = join("\n", $canon);
	$canon_str .= "\n" . $param_str;

	return $canon_str;
}

/**
 * Sign request
 *
 * Creates the authorization header signature for the request.
 *
 * @return	String	String containing the authorization signature.
 */
function sign_request($method, $host, $path, $params) {
	$canon = canon($method, $host, $path, $params);
	$sig   = hash_hmac("sha1", $canon, SKEY);
	return "Basic " . base64_encode(IKEY . ":" . $sig);
}







/**
 * Duo Verify Demo
 *
 * The following code uses the above functions to
 * make a few sample requests to the API
 *
 * Run this script from the command line to see.
 *
 */

$params = array(
	"phone"   => "+" . PHONE,
	"message" => "This is the PHP Duo verify demo. Your pin is: <pin>"
);

// Request a call be made.
echo "Attempting to call: " . $params['phone'] . "\n";
$result = request("/verify/v1/call.json", "POST", $params);

// Parse the response as assoc. array
$res_obj = json_decode($result, TRUE);

if ($res_obj["stat"] != "OK") {
	echo "There was an error with the request. " . $res_obj["message"];
	exit();
}

$pin = $res_obj["response"]["pin"];
unset($params);

// Check the call status
$params = array(
	"txid" => $res_obj["response"]["txid"]
);

$status = "";
$tries = 5;

// Poll the call status from Duo
while ($status != "ended" && $tries > 0) {
	$stat = request("/verify/v1/status.json", "GET", $params);
	$stat = json_decode($stat, TRUE);
	if ($stat['stat'] == "OK") {
		echo $stat["response"]["info"] . "\n";

		$status = $stat["response"]["state"];
		$tries--;
	}
	else {
		echo "Something went wrong.  Try again.";
		exit();
	}
}

echo "Please enter the pin:\n";
$input = trim(fgets(STDIN));

if ($input == $pin) {
	echo "Pin is correct\n";
}
else {
	echo "Pin is incorrect\n";
}


