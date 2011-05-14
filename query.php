<?php

header("Content-Type: application/json");

require("settings.php");
require("sparql-xml-parse.php");

$query = $_GET['query'];
if (!$query) {
	$query = $_POST['query'];
}
if (!$query) {
	exit("Expecting query= argument");
}
if (get_magic_quotes_gpc()) {
	$query = stripslashes($query);
}

if (preg_match('/^\\s*(PREFIX\\s+\\S*:\\s+<[^>]*>\\s*)*CONSTRUCT\\s+JSON\\s+\\{\\s*(.*?)\\s*\\}\\s+(FROM\\s+(?:NAMED\\s+)?<[^>]*>\\s*)*\\s*WHERE\\s+(.*)/is', $query, $parts) == 1) {
	$prefixes = $parts[1];
	$json = $parts[2];
	$preamble = $parts[3];
	$where = $parts[4];
} else {
	exit("Expected CONSTRUCT JSON ... WHERE ... got ".$query);
}

preg_match_all('/\\?([a-zA-Z][a-zA-Z0-9_]*)/', $json, $parts);

$outquery = $prefixes.'SELECT';

$vars = array_unique($parts[1]);
foreach($vars as $var) {
	$outquery .= ' ?'.$var;
}
if (!$vars) {
	/* we could use an ASK, but that's more work later */
	$outquery .= ' (1 AS ?var)';
}

$outquery .= ' '.$preamble.'WHERE '.$where;

$res = sparql_query($endpoint, $outquery);

$jsonout = "[\n";
$first = true;

foreach ($res as $sol) {
	$jsonel = $json;
	foreach($vars as $var) {
		if ($sol[$var]) {
			$value = '"'.addslashes($sol[$var]).'"';
		} else {
			$value = "NULL";
		}
		$jsonel = preg_replace("/\\?$var/", $value, $jsonel);
	}
	if ($first) {
		$first = false;
	} else {
		$jsonout .= ",\n";
	}
	$jsonout .= $jsonel;
}
$jsonout .= "\n]";

print($jsonout);

exit();

function sparql_query($ep, $query)
{
	$req = "$ep?query=".urlencode($query);
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $req);
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Accept: application/sparql-results+xml"));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "CURL");

	$res = curl_exec($ch);
	$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	if (stristr($mime, "application/sparql-results+xml")) {
		$parser = new SparqlResultParser;
		$ret = $parser->parse($res);
	} else {
		exit("Didn't get SPARQL XML results, got ".$mime."\n\n".$query.$res);
	}

	return $ret;
}

?>
