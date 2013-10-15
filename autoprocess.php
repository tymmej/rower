<?php

$base_path="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/";

function haversineDistance($curLat, $curLon, $prevLat, $prevLon) {
	$earthMeanRadius=6371000;

	$curLat=deg2rad($curLat);
	$curLon=deg2rad($curLon);
	$prevLat=deg2rad($prevLat);
	$prevLon=deg2rad($prevLon);

	$latDiff=$curLat-$prevLat;
	$lonDiff=$curLon-$prevLon;

//check wiki for equation
	return 2*$earthMeanRadius*asin(sqrt(pow(sin($latDiff/2), 2)+cos($prevLat)*cos($curLat)*pow(sin($lonDiff/2), 2)));
}

function timeDiff($curTime, $prevTime) {
	$curTime=strtotime($curTime);
	$prevTime=strtotime($prevTime);

	$diff=$curTime-$prevTime;

//if difference more than 20s, set 5s (pause, eg. ice creams)
	if($diff > 20) {
		$diff=5;
	}

	return $diff;
}

//filename of new gpx
$filename=$argv[1];
$file=$base_path . "gpx/" . $filename;

//load gpx file
$text=file_get_contents($file);

$desc=$argv[2];

//check if gpx contains name tag, if not: add
if(!preg_match("/<name>/", $text)) {
	$text = preg_replace("/<trk>/", "<trk><name>" . $desc . "</name>", $text);
	file_put_contents($file, $text);
}

//load gpx file to SimpleXml
$gpx=simplexml_load_file($file);

$distance=0;
$time=0;

//calculate distance and time
foreach ($gpx->trk->trkseg as $trkseg) {
	$isFirst=true;
	foreach ($trkseg->trkpt as $pt) {
		if($isFirst) {
			$cur=(array)$pt;
		        $cur['lat']=$cur['@attributes']['lat'];
		        $cur['lon']=$cur['@attributes']['lon'];
			$prev=$cur;
			$isFirst=false;
			continue;
		}
		$cur=(array)$pt;
		$cur['lat']=$cur['@attributes']['lat'];
		$cur['lon']=$cur['@attributes']['lon'];

		$distance+=haversineDistance($cur['lat'], $cur['lon'], $prev['lat'], $prev['lon']);
		$time+=timeDiff($cur['time'], $prev['time']);

		$prev=$cur;
	}
}
$distance=round($distance/1000, 2);

//read gpx.json
$text=file_get_contents($base_path . "gpx.json");
$json=json_decode($text, true);

//create new trip
$new_trip=array();
$new_trip['name']=$filename;
$new_trip['desc']=$desc;
$new_trip['dist']=$distance;
$new_trip['time']=floor($time/60) . ":" . $time%60;
$new_trip['tags']="";

//push to json
array_push($json['trips'], $new_trip);

//write data
file_put_contents($base_path . "gpx.json", json_encode($json));

?>
