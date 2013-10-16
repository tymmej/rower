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

	return $curTime-$prevTime;
}

//filename of new gpx
if(isset($_FILES['gpx']['name'])){
	$filename=basename($_FILES['gpx']['name']);
	$desc=preg_replace("/[^A-Za-z0-9ążśźęćńółĄŻŚŹĘĆŃÓŁ-\s]/u", "", $_POST['desc']);
	//1-upload; 2-autoprocess
	$mode=1;
}
else {
	$filename=$argv[1];
	$desc=$argv[2];
	$mode=2;
}

$file=$base_path . "gpx/" . $filename;

//move file to tmp folder
if($mode==1){
	$uploadfile=$base_path . "tmp/" . $filename;
	if (move_uploaded_file($_FILES['gpx']['tmp_name'], $uploadfile)) {
		//check if file valid by using xml checks
		$xml=XMLReader::open($uploadfile);
		$xml->setParserProperty(XMLReader::VALIDATE, true);
		if($xml->isValid()) {
			$status=1;
			rename($uploadfile, $file);
		}
		else{
			unlink($uploadfile);
			$status=0;
		}
	}
	else {
			$status=0;
	}
}
else {
	$status=1;
}

//if no error, continue
if($status){
	//load gpx file	
	$text=file_get_contents($file);

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

	//create screenshot
	$cmd=$base_path . "process.sh " . $filename;
	exec($cmd);
}


//ouput html
if($mode==1) {
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
<title>Rower</title>
<link rel=\"stylesheet\" href=\"gpx.css\" />";

	if($status){
		echo "<meta http-equiv=\"Refresh\" content=\"3; url=gpx.php\"";
	}

	echo "</head>
<body>";

	if($status){
		echo "Dodano";
	}
	else {
		echo "Błąd";
	}

	echo "</body>
</html>";
}
?>
