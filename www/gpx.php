<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Rower</title>
	<link rel="stylesheet" href="gpx.css" />
<body>
<div id="container">

<?php

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
	$trips[$i]['dist']=$trip['dist'];
	$trips[$i]['map']=str_replace('gpx', 'png', $trips[$i]['name']);
	$trips[$i]['seconds']=explode(':', $trip['time'])[0]*60+explode(':', $trip['time'])[1];
	$trips[$i]['avg']=$avg=round($trips[$i]['dist']/$trips[$i]['seconds']*3600, 2);
	$hours=(int)($trips[$i]['seconds']/3600);
	$minutes=(int)(($trips[$i]['seconds']-$hours*3600)/60);
	$seconds=(int)($trips[$i]['seconds']-$hours*3600-$minutes*60);
	$trips[$i]['time_readable']=$hours. "h ". $minutes . "m " . $seconds . "s";
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
	$pos=strpos($trips[$i]['date'],'.');
	$date=substr($trips[$i]['date'], 0, $pos);
	while(TRUE) {
		$date=substr($trips[$i]['date'], 0, strpos($trips[$i]['date'],'.'));
		if($date>=$czesc['date']) {
			$czesc['driven']+=$trips[$i]['dist'];
			$i++;
		}
		else {
			break;
		}
	}
}

//create one trip stats and put data for monthly
$table=array();
$stats=array();

$i=0;

foreach($trips as $trip) {
	$year=substr($trip['name'], 0, 4);
	$month=substr($trip['name'], 4, 2);
	$stats[$year][$month]['distance']+=$trip['dist'];
	$stats[$year][$month]['time']+=$trip['seconds'];
	$stats[$year]['distance']+=$trip['dist'];
	$stats[$year]['time']+=$trip['seconds'];
	$table[$i].="\t<tr>\n\t\t<th colspan=\"4\"><a href=\"gpx-gmaps.html?file=" . $trip['date'] . "\">"  . $trip['desc'] . "</a> <a href=\"gpx-osm.html?file=" . $trip['date'] . "\">" . $trip['date'] . "</a></th>\n\t</tr>\n";
	$table[$i].="\t<tr>\n\t\t<td>". $trip['dist'] . "km" ."</td>\n\t\t<td>" . $trip['time_readable'] . "</td>\n\t\t<td>" . $trip['avg'] . "km/h" . "</td>\n\t\t<td><a href=\"maps/". $trip['map'] . "\"><img width=200 src=\"maps/mini-". $trip['map'] . "\" /></a></td>\n\t</tr>\n";
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

echo "\t<tr><td>Total</td>";

for($k=0; $k<$j; $k++) {
	$hours=(int)($stats[$years[$k]]['time']/3600);
	$minutes=(int)(($stats[$years[$k]]['time']-$hours*3600)/60);
	$seconds2=(int)($stats[$years[$k]]['time']-$hours*3600-$minutes*60);
	$time_readable=$hours. "h ". $minutes . "m " . $seconds2 . "s";
	if($time_readable=="0h 0m 0s") $time_readable="";
	echo "<td>" . $stats[$years[$k]]['distance'];
	if($stats[$years[$k]]['distance']!="") echo " km";
	echo "</td><td>" . $time_readable . "</td>";
}
echo "</tr>\n";

//print stats
for($i=12; $i>=1; $i--) {
	$empty=1;
	$stat="";
	$stat.="\t<tr><td>" . $months[$i] . "</td>";
	$i_zero=sprintf("%02d",$i);
	for($k=0; $k<$j; $k++) {
		if($stats[$years[$k]][$i_zero]['distance']!="") $empty=0;
		$hours=(int)($stats[$years[$k]][$i_zero]['time']/3600);
		$minutes=(int)(($stats[$years[$k]][$i_zero]['time']-$hours*3600)/60);
		$seconds=(int)($stats[$years[$k]][$i_zero]['time']-$hours*3600-$minutes*60);
		$time_readable=$hours. "h ". $minutes . "m " . $seconds . "s";
		if($time_readable=="0h 0m 0s") $time_readable="";
		$stat.="<td>" . $stats[$years[$k]][$i_zero]['distance'];
		if($stats[$years[$k]][$i_zero]['distance']!="") $stat.=" km";
		$stat.="</td><td>" . $time_readable . "</td>";
	}
	$stat.="</tr>\n";
	if($empty==1) $stat="";
	echo $stat;
}

echo "</table>\n</div>\n\n";

//serwis
$ilekm=123.12;
$celkm=100;
echo "<div id=\"serwis\">\n<table>\n";
echo "<tr><th>Część</th><th>Przejechane</th><th>Co ile</th></tr>";
foreach($serwis as $czesc) {
	echo "<tr><td>".$czesc['name']."</td><td>".$czesc['driven']."</td><td>".$czesc['dist']."</td></tr>";
}
echo "</table>\n</div>";

//print tables
for($i=0; $i<3; $i++) {
	echo "<div id=\"col\">\n<table>\n";
	echo $table[$i];
	echo "</table>\n</div>";
}

?>

</div>
</body>
</html>
