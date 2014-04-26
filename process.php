<?php

$base_path=getcwd();
$data_path="users";

$user="tymmej";

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

//https://gist.github.com/abarth500/1477057
function encodeGPolylineNum($num){
	$fu = false;
	if($num < 0){
		$fu = true;
	}
	//STEP2
	$num = round($num * 100000);
	//STEP3
	//$num = decbin($num);
	//STEP4
	$num = $num << 1;
	//STEP5
	if($fu){
		$num = ~$num;
	}
	//STEP6 - STEP7
	$num = decbin($num);
	$n = str_split($num);
	$num = array();
	$nn = "";
	for($c=count($n)-1;$c >= 0;$c--){
		$nn = $n[$c].$nn;
		if(strlen($nn) == 5){
			array_push($num,$nn);
			$nn = "";
		}
	}
	if(strlen($nn)>0){
		$nn = str_repeat("0",5 - strlen($nn)).$nn;
		array_push($num,$nn);
	}
	//STEP8 - STEP9 - STEP10 - STEP11
 
	for($c = 0;$c < count($num);$c++){
		if($c != count($num)-1){
			$num[$c] = chr(bindec($num[$c]) + 32 + 63);
		}else{
			$num[$c] = chr(bindec($num[$c]) + 63);
		}
	}
	return implode("",$num);
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

$file=$base_path . '/' . $data_path . '/gpx/' . $filename;

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
	file_put_contents($base_path . '/' . $data_path . '/gpx.json', json_encode($json));

	//create map as image
	//https://gist.github.com/abarth500/1477057
	$i=0;
	
	$newgpx=array();
	foreach ($gpx->trk->trkseg as $trkseg) {
		foreach ($trkseg->trkpt as $pt) {
			$cur=(array)$pt;
			$newgpx[$i]['lat']=$cur['@attributes']['lat'];
			$newgpx[$i]['lon']=$cur['@attributes']['lon'];
			$i++;
		}
	}
	
	$enc = "";
	$old = true;
	$skip=(int)$i/50;
	$i=0;
	foreach($newgpx as $latlng){
		if($i%$skip==0){
			if($old === true){
				$enc .= encodeGPolylineNum($latlng['lat']).
						encodeGPolylineNum($latlng['lon']);
			}else{
				$enc .= encodeGPolylineNum($latlng['lat'] - $old['lat']).
						encodeGPolylineNum($latlng['lon'] - $old['lon']);
			}
			$old = $latlng;
		}
		$i++;
	}
	$url="http://maps.googleapis.com/maps/api/staticmap?sensor=false&size=640x320&path=weight:3|color:rend|enc:";
	$url.=$enc;
	$urlmini="http://maps.googleapis.com/maps/api/staticmap?sensor=false&size=250x125&path=weight:3|color:rend|enc:";
	$urlmini.=$enc;
	$imagename=str_replace('.gpx', '', $filename);
	$img = $base_path . '/' . $data_path . '/maps/'. $imagename . '.png';
	$imgmini = $base_path. '/' . $data_path . '/maps/mini-' . $imagename. ' .png';
	file_put_contents($img, file_get_contents($url));
	file_put_contents($imgmini, file_get_contents($urlmini));
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
