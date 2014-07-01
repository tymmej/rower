<?php

include 'key.php';

require_once("user.php");
$USER = new User("registration_callback");

if($USER->authenticated) {

//main part of script

$data_path="users";
$user=$_SESSION["username"];

if(isset($_POST['tryb'])){
	if($_POST['tryb']=="gpx"){
		$tryb="gpx";
	}
	else if($_POST['tryb']=="szlaki"){
		$tryb="szlaki";
	}
	else if($_POST['tryb']=="inne"){
		$tryb="inne";
	}
}
else{
	$tryb="gpx";
}

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

function findPerpendicularDistance($p, $p1,$p2) {
    // if start and end point are on the same x the distance is the difference in X.
    $result;
    $slope;
    $intercept;
    if ($p1['lat']==$p2['lat']){
        $result=abs($p['lat']-$p1['lat']);
    }else{
		$coefficient=1/abs(cos(($p2['lon'] + $p1['lon'])/2));
        $slope = ($p2['lon']*$coefficient - $p1['lon']*$coefficient) / ($p2['lat'] - $p1['lat']);
        $intercept = $p1['lon']*$coefficient - ($slope * $p1['lat']);
        $result = abs($slope * $p['lat'] - $p['lon']*$coefficient + $intercept) / sqrt(pow($slope, 2) + 1);
    }
    return $result;
}

function properRDP($points,$epsilon){
	$numOfPoints=count($points);
    $firstPoint=$points[0];
    $lastPoint=$points[$numOfPoints-1];
    if ($numOfPoints<3){
        return $points;
    }
    $index=-1;
    $dist=0;
    for ($i=1;$i<$numOfPoints-1;$i++){
        $cDist=findPerpendicularDistance($points[$i],$firstPoint,$lastPoint);
        if ($cDist>$dist){
            $dist=$cDist;
            $index=$i;
        }
    }
    if ($dist>$epsilon){
        // iterate
        $l1=array_slice($points, 0, $index+1);
        $l2=array_slice($points, $index);
        $r1=properRDP($l1,$epsilon);
        $r2=properRDP($l2,$epsilon);
        // concat r2 to r1 minus the end/startpoint that will be the same
        $rs=array_merge(array_slice($r1, 0, count($r1)-1), $r2);
        return $rs;
    }else{
        return compact($firstPoint,$lastPoint);
    }
}

function rdppoints($points, $numOfPoints) {
	$epsilon=0.00001;
	$count=count($points);
	if($count>$numOfPoints){
		do {
			$count>$numOfPoints ? $epsilon*=1.1 : $epsilon/=1.1;
			$reduced=properRDP($points, $epsilon);
			$count=count($reduced);
			echo $count."\n";
		} while($count>$numOfPoints||$count<$numOfPoints-10);
	}
	else {
		$reduced=$points;
	}
	return $reduced;
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

//check for best average speed
function checkbest($stats, &$best, $name, $whichdistance){
	$maxavg=-1;
	$i_max=sizeof($stats);
	$i=0;
	$j=0;
	$ile=$whichdistance;
	$max_i=0;
	$max_j=1;
	while(true){
		$avg=-1;
		while($stats[$i]['distance']<1000){ //ignore first 1000m, floating of gps
			$i++;
		}
		$przejechane=0;
		$j=$i+1;
		while($przejechane<$ile && $j<$i_max){
			$przejechane+=$stats[$j]['distance']-$stats[$j-1]['distance'];
			$j++;
		}
		if(($stats[$j-1]['time']-$stats[$i]['time'])!=0){
			$avg=$przejechane/($stats[$j-1]['time']-$stats[$i]['time'])*3.6;
		}
		if($przejechane<0.95*$ile) break;
		if($avg>$maxavg) {
			$maxavg=round($avg, 2);
			$max_j=$j-1;
			$max_i=$i;
			$max_start=round($stats[$max_i]['distance']/1000, 2);
			$max_end=round($stats[$max_j-1]['distance']/1000, 2);
		}
		$i++;
	}
	if($maxavg>$best['max'][$whichdistance]['avg']){
		$best['max'][$whichdistance]['avg']=$maxavg;
		$best['max'][$whichdistance]['file']=$name;
		$best['max'][$whichdistance]['max_start']=$max_start;
		$best['max'][$whichdistance]['max_end']=$max_end;
	}
	if($maxavg>-1){
		if(!isset($best['trips'][$name])) $best['trips'][$name]=array();
		$best['trips'][$name][$whichdistance]=array();
		$best['trips'][$name][$whichdistance]['avg']=$maxavg;
		$best['trips'][$name][$whichdistance]['max_start']=$max_start;
		$best['trips'][$name][$whichdistance]['max_end']=$max_end;
	}
	else{
		if(!isset($best['trips'][$name])) $best['trips'][$name]=array();
		$best['trips'][$name][$whichdistance]=array();
		$best['trips'][$name][$whichdistance]['avg']=-1;
		$best['trips'][$name][$whichdistance]['max_start']=0;
		$best['trips'][$name][$whichdistance]['max_end']=0;
	}
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

$file=$data_path . '/' . $user . '/' . $tryb . '/' . $filename;

//move file to tmp folder
if($mode==1){
	$uploadfile='tmp/' . $filename;
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

	$stats=array();
	$stats[0]['time']=0;
	$stats[0]['distance']=0;
	$i=1;
	
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

			$stats[$i]['time']=$time;
			$stats[$i]['distance']=$distance;
			$i++;
			$prev=$cur;
		}
	}
	$distance=round($distance/1000, 2);

	//read gpx.json
	$text=file_get_contents($data_path . '/' . $user . "/" . $tryb .".json");
	$json=json_decode($text, true);

	//create new trip
	$new_trip=array();
	$new_trip['desc']=$desc;
	$new_trip['dist']=$distance;
	if($tryb!="szlaki") {
		$new_trip['time']=floor($time/60) . ":" . $time%60;
	}
	else {
		$new_trip['time']=0;
	}
	$new_trip['tags']="";

	//push to json
	$json[str_replace('.gpx', '', $filename)]=$new_trip;

	//write data
	file_put_contents($data_path . '/' . $user . '/' . $tryb . '.json', json_encode($json, JSON_PRETTY_PRINT));

	
	//check for best averages
	if($tryb=="gpx"){
		$distances=array(500, 1000, 2000, 5000, 10000, 15000, 20000, 50000);
		$text=file_get_contents($data_path . '/' . $user . '/best.json');
		$best=json_decode($text, true);

		foreach($distances as $distance1){
			checkbest($stats, $best, str_replace(".gpx", "", $filename), $distance1);
		}
		
		file_put_contents($data_path . '/' . $user . '/best.json', json_encode($best, JSON_PRETTY_PRINT));
	}

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
	$url="http://maps.googleapis.com/maps/api/staticmap?key=".$key."&sensor=false&size=640x320&path=weight:3|color:rend|enc:";
	$url.=$enc;
	$urlmini="http://maps.googleapis.com/maps/api/staticmap?key=".$key."&sensor=false&size=250x125&path=weight:3|color:rend|enc:";
	$urlmini.=$enc;
	$imagename=str_replace('.gpx', '', $filename);
	$img = $data_path . '/' . $user . '/maps/' . $tryb . '/' . $imagename . '.png';
	$imgmini = $data_path . '/' . $user . '/maps/' . $tryb . '/mini-' . $imagename. '.png';
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
		echo "<meta http-equiv=\"Refresh\" content=\"3; url=gpx.php?tryb=" . $tryb . "\"";
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
}
?>

