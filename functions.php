<?
class Rower
{
	private $tryb;
	private $user;
	private $file;
	private $trips=array();
	private $serwis=array();
	private $best=array();
	private $wasTrip=array();
	private $auth=false;
	private $database="json";
	
	public function setAuth($auth) {
		$this->auth=$auth;
	}
	
	public function setDatabase($database) {
		$this->database=$database;
	}
	
	public function setName($database) {
		$this->database=$database;
	}

	public function timeReadable($hours, $minutes, $seconds) {
		return sprintf("%01d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
	}

	//print div with trip
	private function printTrip($trip, $date) {
		echo "\t<div class=\"column\"><table><tr>\n\t\t<th colspan=\"" . ($this->tryb!="szlaki" ? 4 : 3) ."\">
			<a href=\"showmap.php?tryb=" . $this->tryb . "&amp;file=". $date . "\">" 
			. $trip['desc'] .
			"</a>
			<a href=\"showmap.php?source=osm&amp;tryb=" . $this->tryb . "&amp;file=" . $date . "\">"
			. ($this->tryb!="szlaki" ? $date : "OSM") .
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
			echo "<td><a href=\"info.php?tryb=" . $this->tryb . "&amp;filename=" . $date . "\">
				<img width=\"250\" height=\"125\" src=\"download.php?tryb=" . $this->tryb . "&amp;filename=mini-" . $trip['map'] . "\" alt=\"" . $trip['desc'] . " - " .  $date . "\" />
			</a>
			</td>\n\t</tr></table></div>\n";
	}

	//print all trips
	private function printTrips() {
		echo "<div class=\"grid\">";
		foreach($this->trips as $date=>$trip) {
			$this->printTrip($trip, $date);
		}
		echo "</div>";
	}
	
	private function printTripInfo() {
		echo "\t<div class=\"column\"><table><tr>\n\t\t<th colspan=\"" . ($this->tryb!="szlaki" ? 4 : 3) ."\">
			<a href=\"showmap.php?tryb=" . $this->tryb . "&amp;file=". $this->file . "\">" 
			. $this->trips[$this->file]['desc'] .
			"</a>
			<a href=\"showmap.php?source=osm&amp;tryb=" . $this->tryb . "&amp;file=" . $this->file . "\">"
			. ($this->tryb!="szlaki" ? $this->file : "OSM") .
			"</a>
			</th>\n\t</tr>
			\n\t<tr>\n\t\t<td>".
			$this->trips[$this->file]['dist'] . "km" .
			"</td>\n\t\t";
			if($this->tryb!="szlaki"){
				echo "<td>"
					. $this->trips[$this->file]['time_readable'] .
					"</td>\n\t\t<td>"
					. $this->trips[$this->file]['avg'] . "km/h" .
					"</td>\n\t\t";
			}
			else {
				echo "<td>"
					. $this->trips[$this->file]['time_readable'].
					"</td>";
			}
			echo "<td><a href=\"download.php?tryb=" . $this->tryb . "&amp;filename=" . $this->file . ".png\">
				<img width=\"640\" height=\"320\" src=\"download.php?tryb=" . $this->tryb . "&amp;filename=" . $this->file . ".png\" alt=\"" . $this->trips[$this->file]['desc'] . " - " .  $this->file . "\" />
			</a>
			</td>\n\t</tr></table></div>\n";
	}

	//create own array with trips based on json data
	private function createDataFromJson($filename, $mode){
		$file=file_get_contents($filename);
		$json=json_decode($file, true);
		switch($mode){
			case "gpx":
				$this->trips=array();
				foreach($json as $date=>$stats) {
					$this->trips[$date]['date']=$date;
					$this->trips[$date]['desc']=$stats['desc'];
					$this->trips[$date]['dist']=sprintf("%.2f", $stats['dist']);
					$this->trips[$date]['map']=$date.'.png';
					$time=explode(':', $stats['time']);
					if($this->tryb=="szlaki"){
						$this->trips[$date]['seconds']=floor($stats['dist']/18*3600);
					}
					else{
						$this->trips[$date]['seconds']=$time[0]*60+$time[1];
					}
					$this->trips[$date]['avg']=sprintf("%.2f", round($this->trips[$date]['dist']/$this->trips[$date]['seconds']*3600, 2));
					$hours=(int)($this->trips[$date]['seconds']/3600);
					$minutes=(int)(($this->trips[$date]['seconds']-$hours*3600)/60);
					$seconds=(int)($this->trips[$date]['seconds']-$hours*3600-$minutes*60);
					$this->trips[$date]['time_readable']=$this->timeReadable($hours, $minutes, $seconds);
				}
				break;
			case "serwis":
				$i=0;
				foreach($json as $czesc) {
					$this->serwis[$i]['name']=$czesc['name'];
					$this->serwis[$i]['dist']=$czesc['dist'];
					$this->serwis[$i]['date']=$czesc['date'];
					$i++;
				}
				break;
			case "best":
				foreach($json['max'] as $distance=>$best) {
					$this->best['max'][$distance]['avg']=$best['avg'];
					$this->best['max'][$distance]['file']=$best['file'];
					$this->best['max'][$distance]['max_start']=$best['max_start'];
					$this->best['max'][$distance]['max_end']=$best['max_end'];
				}
				break;
			case "besttrip":
				foreach($json['trips'][$this->file] as $distance=>$best) {
					$this->best['trips'][$this->file][$distance]['avg']=$best['avg'];
					$this->best['trips'][$this->file][$distance]['max_start']=$best['max_start'];
					$this->best['trips'][$this->file][$distance]['max_end']=$best['max_end'];
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
		else if($mode=="best"){
			
		}
	}
	
	//sort array by dates descending or names if szlaki
	private function sortTrips() {
		if($this->tryb!="szlaki") {
			krsort($this->trips);
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

	//check if file exists
	private function checkFile() {
		if(isset($_GET['filename'])){
			$filename=basename($_GET['filename']);
			if(array_key_exists($filename, $this->trips)){
				$this->file=$filename;
			}
			else {
				$this->file=-1;
			}
		}
		else{
			$this->file=-1;
		}
	}

	//calculate distance since last check
	private function calculateSerwis() {
		foreach($this->serwis as &$czesc) {
			$czesc['driven']=0;
			reset($this->trips);
			while(key($this->trips)>=$czesc['date']) {
				$czesc['driven']+=$this->trips[key($this->trips)]['dist'];
				next($this->trips);
			}
		}
	}

	//calculate monthly stats
	private function createStats() {
		foreach($this->trips as $date=>$trip) {
			$year=substr($date, 0, 4);
			$month=substr($date, 4, 2);
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
		echo "</table>
		<form id=\"formserwis\" name=\"logout\" action=\"gpx.php\" method=\"post\">
			<input type=\"hidden\" name=\"serwis\" value=\"serwis\"/>
			<input type=\"hidden\" name=\"username\" value=\"" . $_SESSION["username"] ."\" />
			<input type=\"submit\" value=\"Uaktualnij\"/>
		</form>
		</div>";
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
		reset($this->trips);
		$endDate=date('Ymd', strtotime("-" . $weeks*7 . "day"));
		$date=substr(key($this->trips), 0, strpos(key($this->trips),'.'));
		while($date>=$endDate) {
			$diff=(strtotime($today)-strtotime($date))/(60*60*24);
			$this->wasTrip[$diff]=1;
			next($this->trips);
			$date=substr(key($this->trips), 0, strpos(key($this->trips),'.'));
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

	//print best
	private function printBest() {
		echo "<div id=\"best\" class=\"grid\">";
		$j=0;
		foreach($this->best['max'] as $distance=>$value){
			$distances[$j]=$distance;
			$j++;
		}
		for($i=0; $i<2; $i++) {
			echo "<div class=\"column\"><table><tr><th>Dystans</th><th>Średnia</th><th>Data</th></tr>";
			for($j=$i*4; $j<$i*4+4; $j++) {
				echo "<tr><td>" . $distances[$j]/1000 . " km</td><td>" . $this->best['max'][$distances[$j]]['avg'] . " km/h</td><td>" . $this->best['max'][$distances[$j]]['file'] . " (" . $this->best['max'][$distances[$j]]['max_start'] . " km - " . $this->best['max'][$distances[$j]]['max_end'] . " km)</td></tr>";
			}
			echo "</tr></table></div>";
		}
	}
	
	private function printBestInfo() {
		echo "<div id=\"best\" class=\"grid\">";
		$j=0;
		foreach($this->best['trips'][$this->file] as $distance=>$value){
			$distances[$j]=$distance;
			$j++;
		}
		for($i=0; $i<2; $i++) {
			echo "<div class=\"column\"><table><tr><th>Dystans</th><th>Średnia</th><th>Data</th></tr>";
			for($j=$i*4; $j<$i*4+4; $j++) {
				echo "<tr><td>" . $distances[$j]/1000 . " km</td><td>" . $this->best['trips'][$this->file][$distances[$j]]['avg'] . " km/h</td><td>" . $this->best['trips'][$this->file][$distances[$j]]['max_start'] . " km - " . $this->best['trips'][$this->file][$distances[$j]]['max_end'] . " km)</td></tr>";
			}
			echo "</tr></table></div>";
		}
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
				<form action=\"showmap.php?multi=true\" method=\"post\">
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
	
	private function printHeaderInfo() {
		echo "<div id=\"logout\">
			<form id=\"formlogout\" name=\"logout\" action=\"gpx.php\" method=\"post\">
			<input type=\"hidden\" name=\"op\" value=\"logout\"/>
			<input type=\"hidden\" name=\"username\" value=\"" . $_SESSION["username"] ."\" />
			Zalogowany jako " . $_SESSION["username"] . "
			<input type=\"submit\" value=\"Wyloguj\"/>
			</form>
			</div>";
	}
	
	public function updateSerwis($data_path){
			$this->createData($data_path.'/'.$this->user.'/serwis.json', "serwis");
			foreach($this->serwis as &$czesc) {
				unset($czesc['driven']);
				$czesc['date']=date('Ymd');
			}
			file_put_contents($data_path.'/'.$this->user.'/serwis.json', json_encode($this->serwis, JSON_PRETTY_PRINT));
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
			
			if(isset($_POST['serwis']) && $_POST['serwis']=='serwis'){
				$this->updateSerwis($data_path);
			}
			
			$this->createData($data_path.'/'.$this->user.'/serwis.json', "serwis");
			$this->createData($data_path.'/'.$this->user.'/best.json', "best");

			if($this->tryb=="gpx"){
				$this->printStats();
				$this->printSerwis();
				$this->printBest();
				$this->printCalendar();
			}

			$this->printTrips();
		}
	}

	public function run_info() {
		echo "<div id=\"container\">";
		if(!$this->auth) {
			$this->printNotAuthenticated();
		}
		if($this->auth) {
			$data_path="users";
			$this->user=$_SESSION["username"];
			$this->checkMode();
			$this->printHeaderInfo();

			//read data
			$this->createData($data_path.'/'.$this->user.'/' .$this->tryb.'.json', "gpx");
			$this->checkFile();
			if($this->tryb=="gpx"){
				$this->createData($data_path.'/'.$this->user.'/best.json', "besttrip");
			}
			
			$this->printTripInfo();
			
			if($this->tryb=="gpx" && $this->file!=-1){
				$this->printBestInfo();
			}
		}
	}
}
?>