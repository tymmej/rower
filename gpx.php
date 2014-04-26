<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Rower</title>
	<link rel="stylesheet" href="gpx.css" />
</head>
<body>
<div id="container">

<div id="upload">
<form enctype="multipart/form-data" action="process.php" method="post">
Opis: <input name="desc" type="text" />
<input name="gpx"  type="file" />
<input type="submit" value="Dodaj" />
</form>
</div>

<div id="podsumowanie">
<form action="gpx-gmaps-all.php" method="post">
Okres: <input name="files" type="text" />
<input type="submit" value="Pokaż" />
</form>
</div>

<?php

function timeReadable($hours, $minutes, $seconds) {
	return sprintf("%02d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
}

function timeReadableTrip($hours, $minutes, $seconds) {
	return sprintf("%01d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
}

//read json
$file=file_get_contents('gpx.json');
$json=json_decode($file, true);

//create own array
$i=0;

$trips=array();
foreach($json['trips'] as $trip) {
	$trips[$i]['name']=$trip['name'];
	$trips[$i]['date']=str_replace('.gpx', '', $trips[$i]['name']);
	$trips[$i]['desc']=$trip['desc'];
	$trips[$i]['dist']=sprintf("%.2f", $trip['dist']);
	$trips[$i]['map']=str_replace('gpx', 'png', $trips[$i]['name']);
	$time=explode(':', $trip['time']);
	$trips[$i]['seconds']=$time[0]*60+$time[1];
	$trips[$i]['avg']=sprintf("%.2f", round($trips[$i]['dist']/$trips[$i]['seconds']*3600, 2));
	$hours=(int)($trips[$i]['seconds']/3600);
	$minutes=(int)(($trips[$i]['seconds']-$hours*3600)/60);
	$seconds=(int)($trips[$i]['seconds']-$hours*3600-$minutes*60);
	$trips[$i]['time_readable']=timeReadableTrip($hours, $minutes, $seconds);
	$i++;
}

//sort array by dates descending
foreach ($trips as $trip) {
    $dates[]  = $trip['date'];
}
array_multisort($trips, SORT_DESC, $dates);

//read serwis
$file=file_get_contents('serwis.json');
$json=json_decode($file, true);

$i=0;

$serwis=array();
foreach($json['serwis'] as $czesc) {
        $serwis[$i]['name']=$czesc['name'];
        $serwis[$i]['dist']=$czesc['dist'];
        $serwis[$i]['date']=$czesc['date'];
}

//calculate distance since last check
foreach($serwis as &$czesc) {
	$i=0;
	$czesc['driven']=0;
	while(substr($trips[$i]['date'], 0, strpos($trips[$i]['date'],'.'))>=$czesc['date']) {
		$czesc['driven']+=$trips[$i]['dist'];
		$i++;
	}
}

//create one trip stats and put data for monthly
$table=array(0 => "", 1 => "", 2 => "");
$stats=array();

$i=0;

foreach($trips as $trip) {
	$year=substr($trip['name'], 0, 4);
	$month=substr($trip['name'], 4, 2);
	if(!isset($stats[$year])) $stats[$year]=array();
	if(!isset($stats[$year][$month])) $stats[$year][$month]=array();
	if(!isset($stats[$year][$month]['distance'])) $stats[$year][$month]['distance']=0;
	if(!isset($stats[$year][$month]['time'])) $stats[$year][$month]['time']=0;
	if(!isset($stats[$year]['distance'])) $stats[$year]['distance']=0;
	if(!isset($stats[$year]['time'])) $stats[$year]['time']=0;
	$stats[$year][$month]['distance']+=$trip['dist'];
	$stats[$year][$month]['time']+=$trip['seconds'];
	$stats[$year]['distance']+=$trip['dist'];
	$stats[$year]['time']+=$trip['seconds'];
	$table[$i].="\t<tr>\n\t\t<th colspan=\"4\"><a href=\"gpx-gmaps.html?file=" . $trip['date'] . "\">"  . $trip['desc'] . "</a> <a href=\"gpx-osm.html?file=" . $trip['date'] . "\">" . $trip['date'] . "</a></th>\n\t</tr>\n";
	$table[$i].="\t<tr>\n\t\t<td>". $trip['dist'] . "km" ."</td>\n\t\t<td>" . $trip['time_readable'] . "</td>\n\t\t<td>" . $trip['avg'] . "km/h" . "</td>\n\t\t<td><a href=\"maps/". $trip['map'] . "\"><img width=\"250px\" heigth=\"125\" src=\"maps/mini-". $trip['map'] . "\" alt=\"" . $trip['desc'] . " - " .  $trip['date'] . "\" /></a></td>\n\t</tr>\n";
	$i=++$i%3;
}

//create monthly stats
$i=0;
$j=0;
echo "<div id=\"stats\">\n<table>\n\t<tr><td></td>";
foreach($stats as $year => $stat) {
	echo "<th colspan=\"2\">$year</th>";
	$years[$j]=$year;
	$j++;
}
echo "</tr>\n";

$months=array('', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień');

echo "\t<tr><td>Razem</td>";

for($k=0; $k<$j; $k++) {
	$hours=(int)($stats[$years[$k]]['time']/3600);
	$minutes=(int)(($stats[$years[$k]]['time']-$hours*3600)/60);
	$seconds=(int)($stats[$years[$k]]['time']-$hours*3600-$minutes*60);
	echo "<td>" . sprintf("%06.2f", $stats[$years[$k]]['distance']) . " km";
	echo "</td><td>" . timeReadable($hours, $minutes, $seconds) . "</td>";
}
echo "</tr>\n";

//print stats
for($i=12; $i>=1; $i--) {
	$empty=1;
	$stat="";
	$stat.="\t<tr><td>" . $months[$i] . "</td>";
	$i_zero=sprintf("%02d",$i);
	for($k=0; $k<$j; $k++) {
		if(isset($stats[$years[$k]][$i_zero]['time']) && $stats[$years[$k]][$i_zero]['time']!=0) {
			$empty=0;
			$hours=(int)($stats[$years[$k]][$i_zero]['time']/3600);
			$minutes=(int)(($stats[$years[$k]][$i_zero]['time']-$hours*3600)/60);
			$seconds=(int)($stats[$years[$k]][$i_zero]['time']-$hours*3600-$minutes*60);
			$stat.="<td>";
			$stat.=sprintf("%06.2f",$stats[$years[$k]][$i_zero]['distance']) . " km";
			$stat.="</td><td>" . timeReadable($hours, $minutes, $seconds) . "</td>";
		}
		else {
			$stat.="<td></td><td></td>";
		}
	}
	if(!$empty) {
		echo $stat;
	}
}

echo "</table>\n</div>\n\n";

//serwis
echo "<div id=\"serwis\">\n<table>\n";
echo "<tr><th>Część</th><th>Przejechane</th><th>Co ile</th></tr>";
foreach($serwis as $czesc) {
	echo "<tr><td>" . $czesc['name'] . "</td><td>" . sprintf("%.2f", $czesc['driven']) . "</td><td>" . sprintf("%.2f", $czesc['dist']) . "</td></tr>";
}
echo "</table>\n</div>";

//calendar
$daysOfWeek=array("nd", "pn", "wt", "śr", "cz", "pt", "so");
$dayOfWeek=date('N');
$today=date('Ymd');
echo "<div id=\"calendar\">
<table><tr>";
for($i=0; $i>-4; $i--) {
	echo "<th colspan=\"7\">" . $i . "</th>";
}
echo "</tr>\n<tr>";
for($i=28; $i; $i--) {
	echo "<th>" . $daysOfWeek[($i+$dayOfWeek)%7] ."</th>";
	$wasTrip[$i-1]=0;
}
echo "</tr>\n";
$endDate=date('Ymd', strtotime("-28 day"));
$i=0;
while(substr($trips[$i]['date'], 0, strpos($trips[$i]['date'],'.'))>=$endDate) {
	$date=substr($trips[$i]['date'], 0, strpos($trips[$i]['date'],'.'));
	$diff=(strtotime($today)-strtotime($date))/(60*60*24);
	$wasTrip[$diff]=1;
	$i++;
}
echo "<tr>";
for($i=0; $i<28; $i++) {
	echo "<td class=\"trip" . $wasTrip[$i] . "\">".  date('d', strtotime(-$i . "day")) . "</td>";
}
echo "</tr>
</table>
</div>";

//print tables
for($i=0; $i<3; $i++) {
	echo "<div class=\"col\">\n<table>\n";
	echo $table[$i];
	echo "</table>\n</div>";
}

?>

</div>
</body>
</html>
