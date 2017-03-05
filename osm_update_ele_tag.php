<?php
/* *********************************************************
	@author Grant McKenzie
	@date March 5, 2017
	@project ISED 
	@desc A script used to update elevation values for OSM
		  nodes based on ISED project.
	@paper http://grantmckenzie.com/academics/ISED_2017.pdf

************************************************************/

require("simplehtml.php");
require("credentials.inc")

// Loop through CSV file containing NODEID, ELEVATION
// Store values in 2D array
$data = array();
$handle = @fopen("osm16249534.csv", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $d = explode(",",$buffer);
        $data[] = $d;
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}

// Loop through the array of OSM nodes
// Make request to OSM API for the full node details
// Important to get version, lat, lng and any other information associated with node (not currently implemented)
foreach($data as $node) {
	$url = "http://www.openstreetmap.org/api/0.6/node/".trim($node[0]); 
	echo $url . "\n";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
	curl_setopt($ch, CURLOPT_URL, $url);    

	$output = curl_exec($ch);  
	$d = str_get_html($output);
	$f = $d->find("node")[0];
	$id = $f->id;
	$version = $f->version;
	$lat = $f->lat;
	$lon = $f->lon;
	$elev = trim($node[1]);
	curl_close($ch); 
	updateNode($id, $version, $lat, $lon, $elev);
	sleep(1);
}

// Update OSM node with elevation values
// IMPORTANT:  This overwrites the node so make sure to bring over all existing tags as well.
function updateNode($id, $version, $lat, $lon, $elev) {
	$url = "http://www.openstreetmap.org/api/0.6/node/".$id;  
	$putString = '<osm><node id="'.$id.'" changeset="46600439" version="'.$version.'" lat="'.$lat.'" lon="'.$lon.'"><tag k="ele" v="'.$elev.'"/></node></osm>';
	echo "\t".$putString . "\n";

	$putData = fopen('php://temp/maxmemory:256000', 'w');  
	if (!$putData) {  
	    die('could not open temp memory data');  
	}  
	fwrite($putData, $putString);  
	fseek($putData, 0);  

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
	curl_setopt($ch, CURLOPT_USERPWD, $credentials);	// Credential string in the credentials.inc file
	curl_setopt($ch, CURLOPT_URL, $url);  
	curl_setopt($ch, CURLOPT_PUT, true);  
	curl_setopt($ch, CURLOPT_INFILE, $putData);  
	curl_setopt($ch, CURLOPT_INFILESIZE, strlen($putString));  

	$output = curl_exec($ch);  
	echo $output . "\n";  
	 
	fclose($putData); 
	curl_close($ch);  

}

?>