<?php

//------------LOGIN------------//
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
//------------END-LOGIN------------//

class Rower
{
	private $tryb;
	private $user;
	private $trips=array();
	private $serwis=array();
	private $wasTrip=array();
	private $auth=false;
	private $database="json";
	
	public function setAuth($auth) {
		$this->auth=$auth;
	}
	
	public function setDatabase($database) {
		$this->database=$database;
	}

	public function timeReadable($hours, $minutes, $seconds) {
		return sprintf("%01d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
	}

	//print div with trip
	private function printTrip($trip) {
		echo "\t<div class=\"column\"><table><tr>\n\t\t<th colspan=\"" . ($this->tryb!="szlaki" ? 4 : 3) ."\">
			<a href=\"gpx-gmaps.php?tryb=" . $this->tryb . "&amp;file=". $trip['date'] . "\">" 
			. $trip['desc'] .
			"</a>
			<a href=\"gpx-osm.php?tryb=" . $this->tryb . "&amp;file=" . $trip['date'] . "\">"
			. ($this->tryb!="szlaki" ? $trip['date'] : "OSM") .
			"</a>
			</th>\n\t</tr>
			\n\t<tr>\n\t\t<td>".
			$trip['dist'] . "km" .
			"</td>\n\t\t";
			if($this->tryb!="szlaki"){
				echo "<td>"
					. $trip['time_readable'] .
					"</td>\n\t\t<td>"
					. $trip['avg'] . "km/h" .
					"</td>\n\t\t";
			}
			else {
				echo "<td>"
					. $trip['time_readable'].
					"</td>";
			}
			echo "<td><a href=\"download.php?tryb=" . $this->tryb . "&amp;filename=" . $trip['map'] . "\">
				<img width=\"250\" height=\"125\" src=\"download.php?tryb=" . $this->tryb . "&amp;filename=mini-" . $trip['map'] . "\" alt=\"" . $trip['desc'] . " - " .  $trip['date'] . "\" />
			</a>
			</td>\n\t</tr></table></div>\n";
	}

	//print all trips
	private function printTrips() {
		echo "<div class=\"grid\">";
		foreach($this->trips as $trip) {
			$this->printTrip($trip);
		}
		echo "</div>";
	}

	//create own array with trips based on json data
	private function createDataFromJson($filename, $mode){
		$i=0;
		$file=file_get_contents($filename);
		$json=json_decode($file, true);
		switch($mode){
			case "gpx":
				$this->trips=array();
				foreach($json['trips'] as $trip) {
					$this->trips[$i]['name']=$trip['name'];
					$this->trips[$i]['date']=str_replace('.gpx', '', $this->trips[$i]['name']);
					$this->trips[$i]['desc']=$trip['desc'];
					$this->trips[$i]['dist']=sprintf("%.2f", $trip['dist']);
					$this->trips[$i]['map']=str_replace('gpx', 'png', $this->trips[$i]['name']);
					$time=explode(':', $trip['time']);
					if($this->tryb=="szlaki"){
						$this->trips[$i]['seconds']=floor($trip['dist']/18*3600);
					}
					else{
						$this->trips[$i]['seconds']=$time[0]*60+$time[1];
					}
					$this->trips[$i]['avg']=sprintf("%.2f", round($this->trips[$i]['dist']/$this->trips[$i]['seconds']*3600, 2));
					$hours=(int)($this->trips[$i]['seconds']/3600);
					$minutes=(int)(($this->trips[$i]['seconds']-$hours*3600)/60);
					$seconds=(int)($this->trips[$i]['seconds']-$hours*3600-$minutes*60);
					$this->trips[$i]['time_readable']=$this->timeReadable($hours, $minutes, $seconds);
					$i++;
				}
				break;
			case "serwis":
				foreach($json['serwis'] as $czesc) {
					$this->serwis[$i]['name']=$czesc['name'];
					$this->serwis[$i]['dist']=$czesc['dist'];
					$this->serwis[$i]['date']=$czesc['date'];
					$i++;
				}
				break;
		}
	}

	private function createData($filename, $mode) {
		if($this->database=="json"){
			$this->createDataFromJson($filename, $mode);
		}
		else if($this->database=="sqlite"){
			$this->createDataFromSqlite($filename, $mode);
		}
		if($mode=="gpx"){
			$this->sortTrips();
			if($this->tryb!="szlaki") {
				$this->createStats();
				$this->createCalendar();
			}
		}
		else if($mode=="serwis"){
			$this->calculateSerwis();
		}
	}
	
	//sort array by dates descending or names if szlaki
	private function sortTrips() {
		if($this->tryb!="szlaki") {
			foreach ($this->trips as $trip) {
				$dates[]  = $trip['date'];
			}
			array_multisort($this->trips, SORT_DESC, $dates);
		}
		else {
			foreach ($this->trips as $trip) {
				$desc[]  = $trip['desc'];
			}
			array_multisort($this->trips, SORT_ASC, $desc);
		}
	}

	//check which data we display, gpx, szlaki or inne
	private function checkMode() {
		if(isset($_GET['tryb'])){
			if($_GET['tryb']=="gpx"){
				$this->tryb="gpx";
			}
			else if($_GET['tryb']=="szlaki"){
				$this->tryb="szlaki";
			}
			else if($_GET['tryb']=="inne"){
				$this->tryb="inne";
			}
			else {
				$this->tryb="gpx";
			}
		}
		else{
			$this->tryb="gpx";
		}
	}

	//calculate distance since last check
	private function calculateSerwis() {
		foreach($this->serwis as &$czesc) {
			$i=0;
			$czesc['driven']=0;
			while(substr($this->trips[$i]['date'], 0, strpos($this->trips[$i]['date'],'.'))>=$czesc['date']) {
				$czesc['driven']+=$this->trips[$i]['dist'];
				$i++;
			}
		}
	}

	//calculate monthly stats
	private function createStats() {
		foreach($this->trips as $trip) {
			$year=substr($trip['name'], 0, 4);
			$month=substr($trip['name'], 4, 2);
			if(!isset($this->stats[$year])) $this->stats[$year]=array();
			if(!isset($this->stats[$year][$month])) $this->stats[$year][$month]=array();
			if(!isset($this->stats[$year][$month]['distance'])) $this->stats[$year][$month]['distance']=0;
			if(!isset($this->stats[$year][$month]['time'])) $this->stats[$year][$month]['time']=0;
			if(!isset($this->stats[$year]['distance'])) $this->stats[$year]['distance']=0;
			if(!isset($this->stats[$year]['time'])) $this->stats[$year]['time']=0;
			$this->stats[$year][$month]['distance']+=$trip['dist'];
			$this->stats[$year][$month]['time']+=$trip['seconds'];
			$this->stats[$year]['distance']+=$trip['dist'];
			$this->stats[$year]['time']+=$trip['seconds'];
		}
	}

	//print monthly stats
	private function printStats() {
		echo "<div id=\"stats\" class=\"grid\">";
		//$months=array('', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień');
		$months=array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		foreach($this->stats as $year => $stat) {
			echo "<div class=\"column\"><table>\n\t<tr><td></td><th colspan=\"2\">$year</th>";
			echo "</tr>\n\t<tr><td>Razem</td>";

			$hours=(int)($stat['time']/3600);
			$minutes=(int)(($stat['time']-$hours*3600)/60);
			$seconds=(int)($stat['time']-$hours*3600-$minutes*60);
			echo "<td>" . sprintf("%06.2f", $stat['distance']) . " km";
			echo "</td><td>" . $this->timeReadable($hours, $minutes, $seconds) . "</td>";
			
			echo "</tr>\n";

			//print stats
			for($i=12; $i>=1; $i--) {
				$empty=1;
				$this->stats="";
				$this->stats.="\t<tr><td>" . $months[$i] . "</td>";
				$i_zero=sprintf("%02d",$i);
				if(isset($stat[$i_zero]['time']) && $stat[$i_zero]['time']!=0) {
					$empty=0;
					$hours=(int)($stat[$i_zero]['time']/3600);
					$minutes=(int)(($stat[$i_zero]['time']-$hours*3600)/60);
					$seconds=(int)($stat[$i_zero]['time']-$hours*3600-$minutes*60);
					$this->stats.="<td>";
					$this->stats.=sprintf("%06.2f",$stat[$i_zero]['distance']) . " km";
					$this->stats.="</td><td>" . $this->timeReadable($hours, $minutes, $seconds) . "</td>";
				}
				else {
					$this->stats.="<td></td><td></td>";
				}
				if(!$empty) {
					echo $this->stats."</tr>";
				}
			}
			echo "</table></div>";
		}
		echo "</div>\n\n";
	}

	//print serwis
	private function printSerwis() {
		echo "<div id=\"serwis\" class=\"grid\">\n<table>\n
		<tr><th>Część</th><th>Przejechane</th><th>Co ile</th></tr>";
		foreach($this->serwis as $czesc) {
			echo "<tr><td>" . $czesc['name'] . "</td><td>" . sprintf("%.2f", $czesc['driven']) . "</td><td>" . sprintf("%.2f", $czesc['dist']) . "</td></tr>";
		}
		echo "</table>\n</div>";
	}

	//create calendar
	private function createCalendar(){
		$daysOfWeek=array("nd", "pn", "wt", "śr", "cz", "pt", "so");
		$dayOfWeek=date('N');
		$today=date('Ymd');
		$weeks=6;
		for($i=$weeks*7; $i; $i--) {
			$this->wasTrip[$i-1]=0;
		}
		$endDate=date('Ymd', strtotime("-" . $weeks*7 . "day"));
		$i=0;
		while(substr($this->trips[$i]['date'], 0, strpos($this->trips[$i]['date'],'.'))>=$endDate) {
			$date=substr($this->trips[$i]['date'], 0, strpos($this->trips[$i]['date'],'.'));
			$diff=(strtotime($today)-strtotime($date))/(60*60*24);
			$this->wasTrip[$diff]=1;
			$i++;
		}
	}

	//print calendar
	private function printCalendar() {
		$daysOfWeek=array("nd", "pn", "wt", "śr", "cz", "pt", "so");
		$dayOfWeek=date('N');
		$weeks=6;
		echo "<div id=\"calendar\" class=\"grid\">";
		for($i=0; $i>-$weeks; $i--) {
			echo "<div class=\"column\"><table><tr><th colspan=\"7\">" . $i . "</th></tr><tr>";
			for($j=7; $j; $j--) {
				echo "<th>" . $daysOfWeek[($j+$dayOfWeek)%7] ."</th>";
			}

			echo "</tr><tr>";
			for($j=$i*7; $j>($i-1)*7; $j--) {
				echo "<td class=\"trip" . $this->wasTrip[-$j] . "\">".  date('d', strtotime($j . "day")) . "</td>";
			}
			echo "</tr></table></div>";
		}
		echo "</div>";
	}

	private function printNotAuthenticated() {
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
					<tr><td>Login </td><td><input type=\"text\" name=\"username\" value=\"\" autocapitalize=\"none\" autocorrect=\"off\" /></td></tr>
					<tr><td>Hasło </td><td><input type=\"password\" name=\"password1\" value=\"\" autocapitalize=\"none\" autocorrect=\"off\" /></td></tr>
				</table>
				<input type=\"submit\" value=\"Zaloguj\" />
				</form></div>";
	}

	private function printHeader() {
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
			<input type=\"hidden\" name=\"tryb\" value=\"".$this->tryb."\"/>
			Opis: <input name=\"desc\" type=\"text\" />
			<input name=\"gpx\"  type=\"file\" />
			<input type=\"submit\" value=\"Dodaj\" />
			</form>
			</div>";

		if($this->tryb=="gpx") {
			echo "<div id=\"podsumowanie\">
				<form action=\"gpx-gmaps-all.php\" method=\"post\">
				od: <input name=\"start\" type=\"text\" />
				do: <input name=\"end\" type=\"text\" />
				<input type=\"submit\" value=\"Pokaż\" />
				</form>
				</div>";
		}

		echo "<div id=\"tryb\">
			<a href=\"?tryb=\">GPX</a>
			<a href=\"?tryb=szlaki\">szlaki</a>
			<a href=\"?tryb=inne\">inne</a>
			</div>";

	}

	public function run() {
		echo "<div id=\"container\">";
		if(!$this->auth) {
			$this->printNotAuthenticated();
		}
		if($this->auth) {
			$data_path="users";
			$this->user=$_SESSION["username"];
			$this->checkMode();
			$this->printHeader();

			//read data
			$this->createData($data_path.'/'.$this->user.'/' .$this->tryb.'.json', "gpx");
			$this->createData($data_path.'/'.$this->user.'/serwis.json', "serwis");

			if($this->tryb=="gpx"){
				$this->printStats();
				$this->printSerwis();
				$this->printCalendar();
			}

			$this->printTrips();
		}
	}
}

//------------FUNCTIONS------------//
//print time in nice format 1h 20m 10s

//------------END-FUNCTIONS------------//

echo "<!DOCTYPE html>
<head>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
	<script type=\"text/javascript\" src=\"js/sha1.js\"></script>
	<script type=\"text/javascript\" src=\"js/user.js\"></script>
	<title>Rower</title>
	<link rel=\"stylesheet\" href=\"gpx.css\" />
</head>
<body>";


$obj=new Rower;
$obj->setAuth($USER->authenticated);
$obj->run();

echo "</div>
</body>
</html>";
?>
