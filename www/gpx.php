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
$file = file_get_contents('gpx.json');
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

foreach($trips as $trip) {
$table[$j].="<tr>\n\t<th colspan=\"4\">" . $trip['desc'] . " " . $trip['date'] . "</th>\n</tr>\n";
$table[$j].="<tr>\n\t<td>". $trip['dist'] . "km" ."</td>\n\t<td>" . $trip['time_readable'] . "</td>\n\t<td>" . $trip['avg'] . "km/h" . "</td>\n\t<td><a href=\"maps/". $trip['map'] . "\"><img width=200 src=\"maps/mini-". $trip['map'] . "\" /></a></td>\n</tr>\n";
$j=++$j%3;
}

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
