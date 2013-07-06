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

$i=0;
$j=0;

//create own array
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
	$seconds2=(int)($trips[$i]['seconds']-$hours*3600-$minutes*60);
	$trips[$i]['time_readable']=$hours. "h ". $minutes . "m " . $seconds2 . "s";
	$i++;
}

//sort array by dates descending
foreach ($trips as $trip) {
    $dates[]  = $trip['date'];
}
array_multisort($trips, SORT_DESC, $dates);


$table=array();

$stats=array();

foreach($trips as $trip) {
	$year=substr($trip['name'], 0, 4);
	$month=substr($trip['name'], 4, 2);
	$stats[$year][$month]['distance']+=$trip['dist'];
	$stats[$year][$month]['time']+=$trip['seconds'];
	$stats[$year]['distance']+=$trip['dist'];
	$stats[$year]['time']+=$trip['seconds'];
	$table[$j].="<tr>\n\t<th colspan=\"4\"><a href=\"gpx.html?file=" . $trip['date'] . "\">"  . $trip['desc'] . "</a> <a href=\"gpx-osm.html?file=" . $trip['date'] . "\">" . $trip['date'] . "</a></th>\n</tr>\n";
	$table[$j].="<tr>\n\t<td>". $trip['dist'] . "km" ."</td>\n\t<td>" . $trip['time_readable'] . "</td>\n\t<td>" . $trip['avg'] . "km/h" . "</td>\n\t<td><a href=\"maps/". $trip['map'] . "\"><img width=200 src=\"maps/mini-". $trip['map'] . "\" /></a></td>\n</tr>\n";
	$j=++$j%3;
}

$j=0;
echo "<div id=\"stats\">\n<table>\n<tr>\n<td></td>\n";
foreach($stats as $year => $stat) {
	echo "<th colspan=\"2\">$year</th>";
	$years[$j]=$year;
	$j++;
}
echo "</tr>\n";

$months=array('', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień');

echo "</tr>\n<tr><td>Total</td>";

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

//create stats
for($i=12; $i>=1; $i--) {
	$empty=1;
	$temp="";
	$temp.="<tr><td>" . $months[$i] . "</td>";
	$i_zero=sprintf("%02d",$i);
	for($k=0; $k<$j; $k++) {
		if($stats[$years[$k]][$i_zero]['distance']!="") $empty=0;
		$hours=(int)($stats[$years[$k]][$i_zero]['time']/3600);
		$minutes=(int)(($stats[$years[$k]][$i_zero]['time']-$hours*3600)/60);
		$seconds2=(int)($stats[$years[$k]][$i_zero]['time']-$hours*3600-$minutes*60);
		$time_readable=$hours. "h ". $minutes . "m " . $seconds2 . "s";
		if($time_readable=="0h 0m 0s") $time_readable="";
		$temp.="<td>" . $stats[$years[$k]][$i_zero]['distance'];
		if($stats[$years[$k]][$i_zero]['distance']!="") $temp.=" km";
		$temp.="</td><td>" . $time_readable . "</td>";
	}
	$temp.="</tr>\n";
	if($empty==1) $temp="";
	echo $temp;
}

echo "</table></div>";

//print tables
echo "<div id=\"col1\">\n<table>\n";
echo $table[0];
echo "</table></div>";
echo "<div id=\"col2\">\n<table>\n";
echo $table[1];
echo "</table></div>";
echo "<div id=\"col3\">\n<table>\n";
echo $table[2];
echo "</table></div>";

?>

</div>
</body>
</html>
