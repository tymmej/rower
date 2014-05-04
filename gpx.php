<?php

$data = false;

// this is a demonstrator function, which gets called when new users register
function registration_callback($username, $email, $userdir)
{
	// all it does is bind registration data in a global array,
	// which is echoed on the page after a registration
	global $data;
	$data = array($username, $email, $userdir);
}

require_once("user.php");
$USER = new User("registration_callback");

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">

<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
	<script type=\"text/javascript\" src=\"js/sha1.js\"></script>
	<script type=\"text/javascript\" src=\"js/user.js\"></script>
	<title>Rower</title>
	<link rel=\"stylesheet\" href=\"gpx.css\" />
</head>
<body>";


function timeReadable($hours, $minutes, $seconds) {
	return sprintf("%02d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
}

function timeReadableTrip($hours, $minutes, $seconds) {
	return sprintf("%01d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
}


echo "<div id=\"container\">";

if(!$USER->authenticated) {
	echo "<div id=\"register\"><form id=\"formregistration\" class=\"controlbox\" name=\"new user registration\" action=\"gpx.php\" method=\"post\">
		<input type=\"hidden\" name=\"op\" value=\"register\"/>
		<input type=\"hidden\" name=\"sha1\" value=\"\"/>
		<table>
			<tr><td>Login </td><td><input type=\"text\" name=\"username\" value=\"\" /></td></tr>
			<tr><td>E-mail </td><td><input type=\"text\" name=\"email\" value=\"\" /></td></tr>
			<tr><td>Hasło </td><td><input type=\"password\" name=\"password1\" value=\"\" /></td></tr>
			<tr><td>Hasło (powtórz) </td><td><input type=\"password\" name=\"password2\" value=\"\" /></td></tr>
		</table>
		<input type=\"button\" value=\"Zarejestruj\" onclick=\"User.processRegistration()\"/>
	</form>
	</div>
	<div id=\"login\"><form id=\"formlogin\" class=\"controlbox\" name=\"log in\" action=\"gpx.php\" onsubmit=\"User.processLogin(); return false\" method=\"post\">
		<input type=\"hidden\" name=\"op\" value=\"login\"/>
		<input type=\"hidden\" name=\"sha1\" value=\"\"/>
		<table>
			<tr><td>Login </td><td><input type=\"text\" name=\"username\" value=\"\" autocapitalize=\"off\" autocorrect=\"off\" /></td></tr>
			<tr><td>Hasło </td><td><input type=\"password\" name=\"password1\" value=\"\" autocapitalize=\"off\" autocorrect=\"off\" /></td></tr>
		</table>
		<input type=\"submit\" value=\"Zaloguj\" />
		</form></div>";
}

if($USER->authenticated) {
$data_path="users";
$user=$_SESSION["username"];

if(isset($_GET['tryb'])){
	if($_GET['tryb']=="gpx"){
		$tryb="gpx";
	}
	else if($_GET['tryb']=="szlaki"){
		$tryb="szlaki";
	}
	else if($_GET['tryb']=="inne"){
		$tryb="inne";
	}
	else {
		$tryb="gpx";
	}
}
else{
	$tryb="gpx";
}

echo "<div id=\"logout\">
	<form id=\"formlogout\" name=\"logout\" action=\"gpx.php\" method=\"post\">
	<input type=\"hidden\" name=\"op\" value=\"logout\"/>
	<input type=\"hidden\" name=\"username\" value=\"" . $_SESSION["username"] ."\" />
	Zalogowany jako " . $_SESSION["username"] . "
	<input type=\"submit\" value=\"Wyloguj\"/>
	</form>
	</div>
	<div id=\"upload\">
	<form enctype=\"multipart/form-data\" action=\"process.php\" method=\"post\">
	<input type=\"hidden\" name=\"tryb\" value=\"".$tryb."\"/>
	Opis: <input name=\"desc\" type=\"text\" />
	<input name=\"gpx\"  type=\"file\" />
	<input type=\"submit\" value=\"Dodaj\" />
	</form>
	</div>";

if($tryb=="gpx") {
	echo "<div id=\"podsumowanie\">
		<form action=\"gpx-gmaps-all.php\" method=\"post\">
		Okres: <input name=\"files\" type=\"text\" />
		<input type=\"submit\" value=\"Pokaż\" />
		</form>
		</div>";
}

echo "<div id=\"tryb\">
	<a href=\"?tryb=\">GPX</a>
	<a href=\"?tryb=szlaki\">szlaki</a>
	<a href=\"?tryb=inne\">inne</a>
	</div>";

//read json
$file=file_get_contents($data_path.'/'.$user.'/' .$tryb.'.json');
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
if($tryb!="szlaki") {
	array_multisort($trips, SORT_DESC, $dates);
}
else {
	array_multisort($trips, SORT_ASC, $dates);
}

//read serwis
$file=file_get_contents($data_path.'/'.$user.'/serwis.json');
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
$table="";
$stats=array();

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
}



if($tryb=="gpx"){
	//create monthly stats
	$i=0;
	$j=0;
	//$months=array('', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień');
	$months=array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');

	echo "<div id=\"stats\" class=\"grid\">";
	foreach($stats as $year => $stat) {
		echo "<div class=\"column\"><table>\n\t<tr><td></td><th colspan=\"2\">$year</th>";
		echo "</tr>\n\t<tr><td>Razem</td>";

		$hours=(int)($stat['time']/3600);
		$minutes=(int)(($stat['time']-$hours*3600)/60);
		$seconds=(int)($stat['time']-$hours*3600-$minutes*60);
		echo "<td>" . sprintf("%06.2f", $stat['distance']) . " km";
		echo "</td><td>" . timeReadable($hours, $minutes, $seconds) . "</td>";
		
		echo "</tr>\n";

		//print stats
		for($i=12; $i>=1; $i--) {
			$empty=1;
			$stats="";
			$stats.="\t<tr><td>" . $months[$i] . "</td>";
			$i_zero=sprintf("%02d",$i);
			if(isset($stat[$i_zero]['time']) && $stat[$i_zero]['time']!=0) {
				$empty=0;
				$hours=(int)($stat[$i_zero]['time']/3600);
				$minutes=(int)(($stat[$i_zero]['time']-$hours*3600)/60);
				$seconds=(int)($stat[$i_zero]['time']-$hours*3600-$minutes*60);
				$stats.="<td>";
				$stats.=sprintf("%06.2f",$stat[$i_zero]['distance']) . " km";
				$stats.="</td><td>" . timeReadable($hours, $minutes, $seconds) . "</td>";
			}
			else {
				$stats.="<td></td><td></td>";
			}
			if(!$empty) {
				echo $stats."</tr>";
			}
		}
		echo "</table></div>";
	}
	echo "</div>\n\n";

	//serwis
	echo "<div id=\"serwis\" class=\"grid\">\n<table>\n";
	echo "<tr><th>Część</th><th>Przejechane</th><th>Co ile</th></tr>";
	foreach($serwis as $czesc) {
		echo "<tr><td>" . $czesc['name'] . "</td><td>" . sprintf("%.2f", $czesc['driven']) . "</td><td>" . sprintf("%.2f", $czesc['dist']) . "</td></tr>";
	}
	echo "</table>\n</div>";

	//calendar
	$daysOfWeek=array("nd", "pn", "wt", "śr", "cz", "pt", "so");
	$dayOfWeek=date('N');
	$today=date('Ymd');
	$weeks=6;
	for($i=$weeks*7; $i; $i--) {
		$wasTrip[$i-1]=0;
	}
	$endDate=date('Ymd', strtotime("-" . $weeks*7 . "day"));
	$i=0;
	while(substr($trips[$i]['date'], 0, strpos($trips[$i]['date'],'.'))>=$endDate) {
		$date=substr($trips[$i]['date'], 0, strpos($trips[$i]['date'],'.'));
		$diff=(strtotime($today)-strtotime($date))/(60*60*24);
		$wasTrip[$diff]=1;
		$i++;
	}
	echo "<div id=\"calendar\" class=\"grid\">";
	for($i=0; $i>-$weeks; $i--) {
		echo "<div class=\"column\"><table><tr><th colspan=\"7\">" . $i . "</th></tr><tr>";
		for($j=7; $j; $j--) {
			echo "<th>" . $daysOfWeek[($j+$dayOfWeek)%7] ."</th>";
		}

		echo "</tr><tr>";
		for($j=$i*7; $j>($i-1)*7; $j--) {
			echo "<td class=\"trip" . $wasTrip[-$j] . "\">".  date('d', strtotime($j . "day")) . "</td>";
		}
		echo "</tr></table></div>";
	}
	echo "</div>";
}

//print trips

echo "<div class=\"grid\">";
foreach($trips as $trip) {
	echo "\t<div class=\"column\"><table><tr>\n\t\t<th colspan=\"" . ($tryb!="szlaki" ? 4 : 3) ."\">
		<a href=\"gpx-gmaps.php?tryb=" . $tryb . "&file=". $trip['date'] . "\">" 
		. $trip['desc'] .
		"</a>
		<a href=\"gpx-osm.php?tryb=" . $tryb . "&file=" . $trip['date'] . "\">"
		. ($tryb!="szlaki" ? $trip['date'] : "OSM") .
		"</a>
		</th>\n\t</tr>
		\n\t<tr>\n\t\t<td>".
		$trip['dist'] . "km" .
		"</td>\n\t\t";
		if($tryb!="szlaki"){
			echo "<td>"
				. $trip['time_readable'] .
				"</td>\n\t\t<td>"
				. $trip['avg'] . "km/h" .
				"</td>\n\t\t";
		}
		else {
			echo "<td>"
				. floor($trip['dist']/18) ."h " . (int)(($trip['dist']%18)/18*60) . "m".
				"</td>";
		}
		echo "<td><a href=\"download.php?tryb=" . $tryb . "&filename=" . $trip['map'] . "\">
			<img width=\"250px\" height=\"125\" src=\"download.php?tryb=" . $tryb . "&filename=mini-" . $trip['map'] . "\" alt=\"" . $trip['desc'] . " - " .  $trip['date'] . "\" />
		</a>
		</td>\n\t</tr></table></div>\n";
}
echo "</div>";

//end of main script
}
echo "</div>
</body>
</html>";

?>
