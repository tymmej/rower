<?
class Rower
{
	//general
	private $auth=false;
	private $user;	//user name
	private $data_path='users';
	private $database='sqlite';	//or json
	private $mode;	//from cli or normal
	private $key;	//for google api
	private $tryb;	//gpx, szlaki, inne
	private $file;	//usually content of json
	private $filename;
	private $ids=array(1=>'gpx', 2=>'szlaki', 3=>'inne');
	private $distances=array(500, 1000, 2000, 5000, 10000, 15000, 20000, 50000);
	private $modes=array('all', 'trip');

	//for rower
	private $trips=array();
	private $serwis=array();
	private $best=array();
	private $calendar=array();
	private $howManyTrips=-1;

	//for process
	private $desc;
	private $gpx;
	private $status;
	private $distance=0;
	private $time=0;
	private $stats=array();
	private $enc;
	
	public function __construct($key='') {
		$this->key=$key;
	}

	public function setAuth($auth) {
		$this->auth=$auth;
	}
	
	public function setDatabase($database) {
		$this->database=$database;
	}
	
	private function timeReadable($hours, $minutes, $seconds) {
		return sprintf("%01d", $hours) . "h " . sprintf("%02d", $minutes) . "m " . sprintf("%02d", $seconds) . "s";
	}
	
	//----print functions

	//print all trips
	private function printTrips($howMany) {
		$counter=1;
		echo '<div class="grid">';
		foreach($this->trips as $date=>$trip) {
			$this->printTrip($trip, $date, 250, 125);
			$counter++;
			if($counter>$this->howManyTrips && $this->howManyTrips != -1){
				break;
			}
		}
		echo '</div>';
	}
	
	//print div with trip
	private function printTrip($trip, $date, $width, $height) {
		echo '<div class="column"><table><tr><th colspan="' . ($this->tryb!='szlaki' ? 4 : 3) .'">
			<a href="showmap.php?tryb=' . $this->tryb . '&amp;file='. $date . '">'
						. $trip['desc'] . '</a>
			<a href="showmap.php?source=osm&amp;tryb=' . $this->tryb . '&amp;file=' . $date . '">'
						. ($this->tryb!='szlaki' ? $date : 'OSM') .
						'</a>
			</th></tr>
			<tr><td>'. $trip['dist'] . 'km</td>';
		if($this->tryb!='szlaki'){
			echo '<td>' . $trip['time_readable'] . '</td><td>' . $trip['avg'] . 'km/h</td>';
		}
		else {
			echo '<td>' . $trip['time_readable']. '</td>';
		}
		echo '<td>
			<a href="' . ($width==250 ? 'gpx' : 'download') .'.php?mode=trip&amp;tryb=' . $this->tryb . '&amp;filename=' . $date . ($width==250 ? '' : '.png') .'">
				<img width="' . $width . '" height="' . $height . '" src="download.php?tryb=' . $this->tryb . '&amp;filename=' . ($width==250 ? 'mini-' : '') . $trip['map'] . '" alt="' . $trip['desc'] . ' - ' .  $date . '" />
			</a>
			</td></tr>
			</table>
			</div>';
	}

	//print monthly stats
	private function printStats() {
		echo '<div id="stats" class="grid">';
		$months=array('', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień');
		//$months=array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		foreach($this->stats as $year => $stat) {
			echo '<div class="column"><table>
	<tr><td></td><th colspan="2">'.$year.'</th></tr>
	<tr><td>Razem</td>';
			$hours=(int)($stat['time']/3600);
			$minutes=(int)(($stat['time']-$hours*3600)/60);
			$seconds=(int)($stat['time']-$hours*3600-$minutes*60);
			echo '<td>' . sprintf("%06.2f", $stat['distance']) . ' km';
			echo '</td><td>' . $this->timeReadable($hours, $minutes, $seconds) . '</td>';
			echo '</tr>';

			//print stats
			for($i=12; $i>=1; $i--) {
				$empty=1;
				$this->stats='';
				$this->stats.='<tr><td>' . $months[$i] . '</td>';
				$i_zero=sprintf("%02d",$i);
				if(isset($stat[$i_zero]['time']) && $stat[$i_zero]['time']!=0) {
					$empty=0;
					$hours=(int)($stat[$i_zero]['time']/3600);
					$minutes=(int)(($stat[$i_zero]['time']-$hours*3600)/60);
					$seconds=(int)($stat[$i_zero]['time']-$hours*3600-$minutes*60);
					$this->stats.='<td>';
					$this->stats.=sprintf("%06.2f",$stat[$i_zero]['distance']) . ' km';
					$this->stats.='</td><td>' . $this->timeReadable($hours, $minutes, $seconds) . '</td>';
				}
				else {
					$this->stats.='<td></td><td></td>';
				}
				if(!$empty) {
					echo $this->stats.'</tr>';
				}
			}
			echo '</table></div>';
		}
		echo '</div>';
	}

	//print serwis
	private function printSerwis() {
		echo '<div id="serwis" class="grid">
	<table>
		<tr><th>Część</th><th>Przejechane</th><th>Co ile</th></tr>';
		foreach($this->serwis as $czesc) {
			echo '<tr><td>' . $czesc['name'] . '</td><td>' . sprintf("%.2f", $czesc['driven']) . '</td><td>' . sprintf("%.2f", $czesc['dist']) . '</td></tr>';
		}
		echo '</table>
		<form id="formserwis" name="logout" action="gpx.php" method="post">
			<input type="hidden" name="serwis" value="serwis"/>
			<input type="submit" value="Uaktualnij"/>
		</form>
		</div>';
	}
	
	//print calendar
	private function printCalendar() {
		$daysOfWeek=array('nd', 'pn', 'wt', 'śr', 'cz', 'pt', 'so');
		$dayOfWeek=date('N');
		$weeks=6;
		echo '<div id="calendar" class="grid">';
		for($i=0; $i>-$weeks; $i--) {
			echo '<div class="column"><table><tr><th colspan="7">' . $i . '</th></tr><tr>';
			for($j=7; $j; $j--) {
				echo '<th>' . $daysOfWeek[($j+$dayOfWeek)%7] .'</th>';
			}
			echo '</tr><tr>';
			for($j=$i*7; $j>($i-1)*7; $j--) {
				echo '<td class="trip' . $this->calendar[-$j] . '">'.  date('d', strtotime($j . 'day')) . '</td>';
			}
			echo '</tr></table></div>';
		}
		echo '</div>';
	}
	
	//print best
	private function printBest($best) {
		echo '<div id="best" class="grid">';
		$j=0;
		$items=4;
		foreach($best as $distance=>$value){
			$distances[$j]=$distance;
			$j++;
		}
		for($i=0; $i<sizeof($best)/$items; $i++) {
			echo '<div class="column"><table><tr><th>Dystans</th><th>Średnia</th><th>Data</th></tr>';
			for($j=$i*$items; $j<($i+1)*$items; $j++) {
				echo '<tr><td>' . $distances[$j]/1000 . ' km</td><td>' . ($best[$distances[$j]]['avg']==-1 ? '-' : sprintf("%.2f", $best[$distances[$j]]['avg']). ' km/h') . '</td><td>' . ($best[$distances[$j]]['avg']==-1 ? '-' : (isset($best[$distances[$j]]['file']) ? $best[$distances[$j]]['file'] : '') . ' (' . sprintf("%.2f", $best[$distances[$j]]['max_start']) . ' km - ' . sprintf("%.2f", $best[$distances[$j]]['max_end']) . ' km)').'</td></tr>';
			}
			echo '</table></div>';
		}
		echo '</div>';
	}
	
	private function printStartOfHTML(){
		echo '<!DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Rower</title>
	<link rel="stylesheet" href="theme/gpx.css" />
</head>
<body>
	<div id="container">';
	}
	
	private function printHeader() {
		echo '<div id="logout">
			<form id="formlogout" name="logout" action="gpx.php" method="post">
			<input type="hidden" name="op" value="logout"/>
			<input type="hidden" name="username" value="' . $_SESSION['username'] .'" />
			Zalogowany jako ' . $_SESSION['username'] . '
			<input type="submit" value="Wyloguj"/>
			</form>
			</div>';
		if($this->mode=='trip') {
			echo '<div id="powrot">
			<a href="gpx.php?tryb=' . $this->tryb . '">Powrót</a>
			</div>';
		}
		if($this->mode=='all') {
			echo '<div id="upload">
			<form enctype="multipart/form-data" action="process.php" method="post">
			<input type="hidden" name="tryb" value="'.$this->tryb.'"/>
			Opis: <input name="desc" type="text" />
			<input name="gpx"  type="file" />
			<input type="submit" value="Dodaj" />
			</form>
			</div>';
			if($this->tryb=='gpx') {
				echo '<div id="podsumowanie">
					<form action="showmap.php?multi=true" method="post">
					od: <input name="start" type="text" />
					do: <input name="end" type="text" />
					<input type="submit" value="Pokaż" />
					</form>
					</div>';
			}
		
			echo '<div id="tryb">
				<a href="?tryb=">GPX</a>
				<a href="?tryb=szlaki">szlaki</a>
				<a href="?tryb=inne">inne</a>
				</div>';
		}
	}
	
	private function printNotAuthenticated() {
		echo '<div id="register"><form id="formregistration" class="controlbox" name="new user registration" action="gpx.php" method="post">
				<input type="hidden" name="op" value="register"/>
				<table>
					<tr><td>Login </td><td><input type="text" name="username" value="" /></td></tr>
					<tr><td>E-mail </td><td><input type="text" name="email" value="" /></td></tr>
					<tr><td>Hasło </td><td><input type="password" name="password1" value="" /></td></tr>
					<tr><td>Hasło (powtórz) </td><td><input type="password" name="password2" value="" /></td></tr>
				</table>
				<input type="button" value="Zarejestruj"/>
			</form>
			</div>
			<div id="login"><form id="formlogin" class="controlbox" name="log in" action="gpx.php" method="post">
				<input type="hidden" name="op" value="login"/>
				<table>
					<tr><td>Login </td><td><input type="text" name="username" value="" autocapitalize="none" autocorrect="off" /></td></tr>
					<tr><td>Hasło </td><td><input type="password" name="password1" value="" autocapitalize="none" autocorrect="off" /></td></tr>
				</table>
				<input type="submit" value="Zaloguj" />
				</form></div>';
	}
	
	private function printEndOfHTML(){
		echo '</div>
</body>
</html>';
	}

	private function printHTMLProcess(){
		if($this->mode==1) {
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Rower</title>
<link rel="stylesheet" href="theme/gpx.css" />';
		
			if($this->status){
				echo '<meta http-equiv="Refresh" content="3; url=gpx.php?mode=trip&amp;tryb=' . $this->tryb . '&amp;filename=' . str_replace('.gpx', '', $this->filename) . '"';
			}
		
			echo '</head>
<body>';
		
			if($this->status){
				echo 'Dodano';
			}
			else {
				echo 'Błąd';
			}
		
			echo '</body>
</html>';
		}
	}
	
	private function printTripsLink($howMany){
		echo '<div id="all">
				<a href="?trips=' . $howMany . '&tryb="' . $this->tryb . '">Mniej/Więcej</a>
				</div>';
	}
	//----end print functions
	
	//----read functions
	//read data and fill arrays
	private function createData($filename, $mode) {
		if($this->database=='json'){
			$this->createDataFromJson($this->data_path.'/'.$this->user.'/' . $filename . '.json', $mode);
		}
		else if($this->database=='sqlite'){
			if($filename=='inne' || $filename=='szlaki' || $filename=='serwis' || $filename=='best'){
				$filename='gpx';
			}
			$this->createDataFromSqlite($this->data_path.'/'.$this->user.'/' . $filename . '.sqlite', $mode);
		}
		if($mode=='gpx'){
			$this->processTrips();
			$this->sortTrips();
			if($this->tryb!='szlaki') {
				$this->createStats();
				$this->createCalendar();
			}
		}
		else if($mode=='serwis'){
			$this->calculateSerwis();
		}
		else if($mode=='best'){
			
		}
	}
	
	//read json data
	private function createDataFromJson($filename, $mode){
		$file=file_get_contents($filename);
		$json=json_decode($file, true);
		switch($mode){
			case 'gpx':
				$this->trips=array();
				foreach($json as $date=>$stats) {
					$this->trips[$date]['date']=$date;
					$this->trips[$date]['desc']=$stats['desc'];
					$this->trips[$date]['dist']=sprintf("%.2f", $stats['dist']);
					$time=explode(':', $stats['time']);
					if($this->tryb=='szlaki'){
						$this->trips[$date]['seconds']=floor($stats['dist']/18*3600);
					}
					else{
						$this->trips[$date]['seconds']=$time[0]*60+$time[1];
					}
				}
				break;
			case 'serwis':
				$i=0;
				foreach($json as $czesc) {
					$this->serwis[$i]['name']=$czesc['name'];
					$this->serwis[$i]['dist']=$czesc['dist'];
					$this->serwis[$i]['date']=$czesc['date'];
					$i++;
				}
				break;
			case 'best':
				foreach($json['max'] as $distance=>$best) {
					$this->best['max'][$distance]['avg']=$best['avg'];
					$this->best['max'][$distance]['file']=$best['file'];
					$this->best['max'][$distance]['max_start']=$best['max_start'];
					$this->best['max'][$distance]['max_end']=$best['max_end'];
				}
				break;
			case 'besttrip':
				foreach($json['trips'][$this->file] as $distance=>$best) {
					$this->best['trips'][$this->file][$distance]['avg']=$best['avg'];
					$this->best['trips'][$this->file][$distance]['max_start']=$best['max_start'];
					$this->best['trips'][$this->file][$distance]['max_end']=$best['max_end'];
				}
				break;
		}
	}

	//read sqlite data
	private function createDataFromSqlite($filename, $mode){
		switch($mode){
			case 'gpx':
				$id=array_search($this->tryb, $this->ids);
				$db=new PDO('sqlite:' . $filename);
				$sql='SELECT * FROM tracks WHERE type=:id';
				$q=$db->prepare($sql);
				$q->execute(array(':id'=>$id));
				while($r=$q->fetch(PDO::FETCH_ASSOC)){
					$date=$r['date'];
					if($this->tryb=='szlaki'){
						$date=sizeof($this->trips);
					}
					$this->trips[$date]['date']=$date;
					$this->trips[$date]['desc']=$r['title'];
					$this->trips[$date]['dist']=sprintf("%.2f", $r['distance']);
					if($this->tryb=='szlaki'){
						$this->trips[$date]['seconds']=floor($r['distance']/18*3600);
					}
					else {
						$this->trips[$date]['seconds']=$r['time'];
					}
				}
				$db=NULL;
				break;
			case 'serwis':
				$i=0;
				$db=new PDO('sqlite:' . $filename);
				$sql='SELECT * FROM serwis';
				$q=$db->prepare($sql);
				$q->execute(array());
				while($r=$q->fetch(PDO::FETCH_ASSOC)){
					$this->serwis[$i]['name']=$r['name'];
					$this->serwis[$i]['dist']=$r['distance'];
					$this->serwis[$i]['date']=$r['date'];
					$i++;
				}
				$db=NULL;
				break;
			case 'best':
				$db=new PDO('sqlite:' . $filename);
				$sql='SELECT * FROM best';
				$q=$db->prepare($sql);
				$q->execute(array());
				while($r=$q->fetch(PDO::FETCH_ASSOC)){
					$this->best['max'][$r['distance']]['avg']=$r['avg'];
					$this->best['max'][$r['distance']]['file']=$r['name'];
					$this->best['max'][$r['distance']]['max_start']=$r['start'];
					$this->best['max'][$r['distance']]['max_end']=$r['end'];
				}
				$db=NULL;
				break;
			case 'besttrip':
				$db=new PDO('sqlite:' . $filename);
				foreach($this->distances as $distance) {
					$table='best'.sprintf("%05d", $distance);
					$sql="SELECT * FROM $table WHERE name=:name";
					$q=$db->prepare($sql);
					$q->execute(array(':name'=>$this->file));
					while($r=$q->fetch(PDO::FETCH_ASSOC)){
						$this->best['trips'][$this->file][$distance]['avg']=$r['avg'];
						$this->best['trips'][$this->file][$distance]['max_start']=$r['start'];
						$this->best['trips'][$this->file][$distance]['max_end']=$r['end'];
					}
				}
				$db=NULL;
				break;
		}
	}

	//----end read functions

	//add new fields to trips
	private function processTrips() {
		foreach ($this->trips as &$trip) {
			if($this->tryb=='szlaki'){
				$trip['map']=$trip['desc'].'.png';
			}
			else {
				$trip['map']=$trip['date'].'.png';
			}
			$trip['map']=removePolish($trip['map']);
			$trip['avg']=sprintf("%.2f", round($trip['dist']/$trip['seconds']*3600, 2));
			$hours=(int)($trip['seconds']/3600);
			$minutes=(int)(($trip['seconds']-$hours*3600)/60);
			$seconds=(int)($trip['seconds']-$hours*3600-$minutes*60);
			$trip['time_readable']=$this->timeReadable($hours, $minutes, $seconds);
		}
	}

	//create calendar
	private function createCalendar(){
		$daysOfWeek=array('nd', 'pn', 'wt', 'śr', 'cz', 'pt', 'so');
		$dayOfWeek=date('N');
		$today=date('Ymd');
		$weeks=6;
		for($i=$weeks*7; $i; $i--) {
			$this->calendar[$i-1]=0;
		}
		reset($this->trips);
		$endDate=date('Ymd', strtotime("-" . $weeks*7 . 'day'));
		$date=substr(key($this->trips), 0, strpos(key($this->trips),'.'));
		while($date>=$endDate) {
			$diff=(strtotime($today)-strtotime($date))/(60*60*24);
			$this->calendar[$diff]=1;
			next($this->trips);
			$date=substr(key($this->trips), 0, strpos(key($this->trips),'.'));
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

	//sort array by dates descending or names if szlaki
	private function sortTrips() {
		if($this->tryb!='szlaki') {
			krsort($this->trips);
		}
		else {
			foreach ($this->trips as $trip) {
				$desc[]  = $trip['desc'];
			}
			array_multisort($this->trips, SORT_ASC, $desc);
		}
	}
	
	
	//check which data we display, single trip or all
	private function checkMode() {
		if(in_array($_GET['mode'], $this->modes)){
			$this->mode=$_GET['mode'];
		}
		else{
			$this->mode='all';
		}
	}

	//check how many trips
	private function checkHowManyTrips() {
		if(isset($_GET['trips'])){
			if(preg_match("/^-?[1-9][0-9]*$/D", $_GET['trips'])){
				$this->howManyTrips=$_GET['trips'];
			}
			else if($_GET['trips']=="all"){
				$this->howManyTrips=-1;
			}
			else{
				$this->howManyTrips=4;
			}
		}
		else{
			$this->howManyTrips=4;
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
	
	private function updateSerwis(){
			$this->createData('serwis', 'serwis');
			foreach($this->serwis as &$czesc) {
				unset($czesc['driven']);
				$czesc['date']=date('Ymd'); 
			}
			if($this->database=='json'){
				file_put_contents($this->data_path.'/'.$this->user.'/serwis.json', json_encode($this->serwis, JSON_PRETTY_PRINT));
			}
			else if($this->database=='sqlite'){
				$db=new PDO('sqlite:' . $this->data_path.'/'.$this->user.'/gpx.sqlite');
				$sql='UPDATE serwis SET date=:date';
				$q=$db->prepare($sql);
				$q->execute(array(':date'=>date(Ymd)));
				$db=NULL;
			}
	}

//functions for process

	private function haversineDistance($curLat, $curLon, $prevLat, $prevLon) {
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
	
	//difference between two times
	private function timeDiff($curTime, $prevTime) {
		$curTime=strtotime($curTime);
		$prevTime=strtotime($prevTime);
	
		return $curTime-$prevTime;
	}
	
	//--rdp
	private function findPerpendicularDistance($p, $p1,$p2) {
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
	
	private function properRDP($points,$epsilon){
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
	
	private function rdppoints($points, $numOfPoints) {
		$epsilon=0.00001;
		$count=count($points);
		if($count>$numOfPoints){
			do {
				$count>$numOfPoints ? $epsilon*=1.1 : $epsilon/=1.1;
				$reduced=properRDP($points, $epsilon);
				$count=count($reduced);
			} while($count>$numOfPoints||$count<$numOfPoints-10);
		}
		else {
			$reduced=$points;
		}
		return $reduced;
	}
	//--end rdp
	
	//https://gist.github.com/abarth500/1477057
	private function encodeGPolylineNum($num){
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
			$nn = str_repeat('0',5 - strlen($nn)).$nn;
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
		return implode('',$num);
	}
	
	private function checkAllBest($path){
		if($this->database=='json'){
			$text=file_get_contents($path);
			$this->best=json_decode($text, true);
		}
		else if($this->database=='sqlite'){
			$this->createData('best', 'best');
		}
		
		foreach($this->distances as $distance){
			$name=str_replace('.gpx', '', $this->filename);
			$newbest=$this->checkBest($this->stats, $name, $distance);
			
			if($this->database=='json'){
				$this->best['trips'][$name][$distance]=array();
				$this->best['trips'][$name][$distance]['avg']=$newbest['maxavg'];
				$this->best['trips'][$name][$distance]['max_start']=$newbest['max_start'];
				$this->best['trips'][$name][$distance]['max_end']=$newbest['max_end'];
				file_put_contents($path, json_encode($this->best, JSON_PRETTY_PRINT));
			}
			else if($this->database=='sqlite'){
				$table='best'.sprintf("%05d",$distance);
				$db=new PDO('sqlite:' . $this->data_path.'/'.$this->user.'/' . 'gpx' . '.sqlite');
				$sql="INSERT INTO $table (name, avg, start, end) VALUES (:name, :avg, :start, :end)";
				$q=$db->prepare($sql);
				$q->execute(array(':name'=>$name,':avg'=>$newbest['avg'], ':start'=>$newbest['max_start'], ':end'=>$newbest['max_end']));
				$db=NULL;
			}
			if($newbest['avg']>$this->best['max'][$distance]['avg']){
				if($this->database=='json'){
					$this->best['max'][$distance]['avg']=$newbest['avg'];
					$this->best['max'][$distance]['file']=$name;
					$this->best['max'][$distance]['max_start']=$newbest['max_start'];
					$this->best['max'][$distance]['max_end']=$newbest['max_end'];
					file_put_contents($path, json_encode($this->best, JSON_PRETTY_PRINT));
				}
				else if($this->database=='sqlite'){
					$db=new PDO('sqlite:' . $this->data_path.'/'.$this->user.'/' . 'gpx' . '.sqlite');
					$sql='UPDATE best SET name=:name, avg=:avg, start=:start, end=:end WHERE distance=:distance';
					$q=$db->prepare($sql);
					$q->execute(array(':name'=>$name,':avg'=>$newbest['avg'], ':start'=>$newbest['max_start'], ':end'=>$newbest['max_end'], ':distance'=>$distance));
					$db=NULL;
				}
			}
		}
	}
	
	//check for best average speed
	private function checkBest($stats, $name, $whichdistance){
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
		
		$newbest=array();
		if($maxavg>-1){
			$newbest['avg']=$maxavg;
			$newbest['max_start']=$max_start;
			$newbest['max_end']=$max_end;
		}
		else{
			$newbest['avg']=-1;
			$newbest['max_start']=0;
			$newbest['max_end']=0;
		}
		return $newbest;
	}
	
	private function setFilename(){
		if(isset($_FILES['gpx']['name'])){
			$this->filename=basename($_FILES['gpx']['name']);
			$this->desc=preg_replace("/[^A-Za-z0-9ążśźęćńółĄŻŚŹĘĆŃÓŁ-\s]/u", "", $_POST['desc']);
			//1-upload; 2-autoprocess
			$this->mode=1;
		}
		else {
			$this->filename=$argv[1];
			$this->desc=$argv[2];
			$this->mode=2;
		}
	}
	
	private function moveFile(){
		if($this->mode==1){
			$uploadfile='tmp/' . $this->filename;
			if (move_uploaded_file($_FILES['gpx']['tmp_name'], $uploadfile)) {
				//check if file valid by using xml checks
				$xml=XMLReader::open($uploadfile);
				$xml->setParserProperty(XMLReader::VALIDATE, true);
				if($xml->isValid()) {
					$this->status=1;
					rename($uploadfile, $this->file);
				}
				else{
					unlink($uploadfile);
					$this->status=0;
				}
			}
			else {
				$this->status=0;
			}
		}
		else {
			$this->status=1;
		}
	}
	
	private function calculateDistanceAndTime(){		
		$this->stats[0]['time']=0;
		$this->stats[0]['distance']=0;
		$i=1;
		
		foreach ($this->gpx->trk->trkseg as $trkseg) {
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
		
				$this->distance+=$this->haversineDistance($cur['lat'], $cur['lon'], $prev['lat'], $prev['lon']);
				$this->time+=$this->timeDiff($cur['time'], $prev['time']);
		
				$this->stats[$i]['time']=$this->time;
				$this->stats[$i]['distance']=$this->distance;
				$i++;
				$prev=$cur;
			}
		}
		
		$this->distance=round($this->distance/1000, 2);
	}
	
	private function addNewTrip($filename){
		if($this->database=='json'){
			$this->pushNewTripToJson($this->tryb);
		}
		else if($this->database=='sqlite'){
			$this->pushNewTripToSqlite($this->tryb);
		}
	}
	
	private function pushNewTripToJson($filename){
		$text=file_get_contents($this->data_path.'/'.$this->user.'/' . $filename . '.json');
		$json=json_decode($text, true);
		
		//create new trip
		$new_trip=array();
		$new_trip['desc']=$this->desc;
		$new_trip['dist']=$this->distance;
		if($this->tryb!='szlaki') {
			$new_trip['time']=floor($this->time/60) . ':' . $this->time%60;
		}
		else {
			$new_trip['time']=0;
		}
		
		//push to json
		$json[str_replace('.gpx', '', $this->filename)]=$new_trip;
		
		//write data
		file_put_contents($this->data_path.'/'.$this->user.'/' . $filename . '.json', json_encode($json, JSON_PRETTY_PRINT));
	}
	
	private function pushNewTripToSqlite($filename){
		if($filename=='inne' || $filename=='szlaki'){
			$filename='gpx';
		}
		
		//create new trip
		$new_trip=array();
		$new_trip['type']=array_search($this->tryb, $this->ids);
		$new_trip['title']=$this->desc;
		$new_trip['distance']=$this->distance;
		if($this->tryb!='szlaki') {
			$new_trip['time']=$this->time;
			$new_trip['date']=str_replace('.gpx', '', $this->filename);
		}
		else {
			$new_trip['time']=-1;
			$new_trip['date']=-1;
		}
		
		$db=new PDO('sqlite:' . $this->data_path.'/'.$this->user.'/' . $filename . '.sqlite');
		$sql='INSERT INTO tracks (type, date, title, distance, time) VALUES (:type, :date, :title, :distance, :time)';
		$q=$db->prepare($sql);
		$q->execute(array(':type'=>$new_trip['type'], ':date'=>$new_trip['date'], ':title'=>$new_trip['title'], ':distance'=>$new_trip['distance'], ':time'=>$new_trip['time']));
		$db=NULL;
	}
	
	private function createMaps($key, $path){
		$this->encodePolyline();
		
		$url="http://maps.googleapis.com/maps/api/staticmap?key=".$key."&sensor=false&size=640x320&path=weight:3|color:rend|enc:";
		$url.=$this->enc;
		$urlmini="http://maps.googleapis.com/maps/api/staticmap?key=".$key."&sensor=false&size=250x125&path=weight:3|color:rend|enc:";
		$urlmini.=$this->enc;
		$imagename=removePolish(str_replace('.gpx', '.png', $this->filename));
		$img = $path . '/' . $imagename;
		$imgmini = $path . '/mini-' . $imagename;
		file_put_contents($img, file_get_contents($url));
		file_put_contents($imgmini, file_get_contents($urlmini));
	}
	
	private function encodePolyline(){
		//create map as image
		//https://gist.github.com/abarth500/1477057
		$i=0;
		
		$newgpx=array();
		foreach ($this->gpx->trk->trkseg as $trkseg) {
			foreach ($trkseg->trkpt as $pt) {
				$cur=(array)$pt;
				$newgpx[$i]['lat']=$cur['@attributes']['lat'];
				$newgpx[$i]['lon']=$cur['@attributes']['lon'];
				$i++;
			}
		}
		
		$this->enc = '';
		$old = true;
		$skip=(int)$i/50;
		$i=0;
		foreach($newgpx as $latlng){
			if($i%$skip==0){
				if($old === true){
					$this->enc .= $this->encodeGPolylineNum($latlng['lat']).
					$this->encodeGPolylineNum($latlng['lon']);
				}else{
					$this->enc .= $this->encodeGPolylineNum($latlng['lat'] - $old['lat']).
					$this->encodeGPolylineNum($latlng['lon'] - $old['lon']);
				}
				$old = $latlng;
			}
			$i++;
		}
	}

	//run everything
	public function run($var) {
		
		if($var=='rower'){
			$this->printStartOfHTML();
			if(!$this->auth) {
				$this->printNotAuthenticated();
			}
			else if($this->auth) {
				$this->user=$_SESSION['username'];
				$this->tryb=checkTryb();
				$this->checkMode();
				$this->checkHowManyTrips();

				$this->printHeader();
				
				if($this->mode=='all'){
					//read data
					$this->createData($this->tryb, 'gpx');
				
					if(isset($_POST['serwis']) && $_POST['serwis']=='serwis'){
						$this->updateSerwis();
					}
					if($this->howManyTrips<=4 && $this->howManyTrips>=0){
						$this->printTrips($this->howManyTrips);
						$this->printTripsLink(-1);
					}
					if($this->tryb=='gpx'){
						$this->createData('serwis', 'serwis');
						$this->createData('best', 'best');
						$this->printCalendar();
						$this->printStats();
						$this->printSerwis();
						$this->printBest($this->best['max']);
					}
					if($this->howManyTrips>4 || $this->howManyTrips==-1){
						$this->printTripsLink(4);
						$this->printTrips($this->howManyTrips);
					}
				}
				else if($this->mode=='trip'){	
					//read data
					$this->createData($this->tryb, 'gpx');
					$this->checkFile();
					if($this->file!=-1){
						$this->printTrip($this->trips[$this->file], $this->file, 640, 320);
						if($this->tryb=='gpx'){
							$this->createData('best', 'besttrip');
							$this->printBest($this->best['trips'][$this->file]);
						}
					}
					else{
						echo 'File does not exist';
					}
				}
			}
			$this->printEndOfHTML();
		}
		else if($var=='process'){
			if(!$this->auth) {
				$this->printNotAuthenticated();
			}
			else if($this->auth) {
				$this->user=$_SESSION["username"];
				
				$this->tryb=checkTryb();
				
				$this->setFilename();
				$this->file=$this->data_path . '/' . $this->user . '/' . $this->tryb . '/' . $this->filename;
				$this->moveFile();
				
				if($this->status){
					$text=file_get_contents($this->file);
					
					//check if gpx contains name tag, if not: add
					if(!preg_match("/<name>/", $text)) {
						$text = preg_replace("/<trk>/", "<trk><name>" . $this->desc . "</name>", $text);
						file_put_contents($this->file, $text);
					}
					
					$this->gpx=simplexml_load_file($this->file);
					$this->calculateDistanceAndTime();
					
					$this->addNewTrip($this->tryb);
					
					//check for best averages
					if($this->tryb=="gpx"){
						$this->checkAllBest($this->data_path . '/' . $this->user . '/best.json');
					}
					
					$this->createMaps($this->key, $this->data_path . '/' . $this->user . '/maps/' . $this->tryb);
				}
				$this->printHTMLProcess();
			}
		}
	}
}

function removePolish($string) {
	$pl=array('Ę','ę','Ó','ó','Ł','ł','Ś','ś','Ą','ą','Ż','ż','Ź','ź','Ć','ć','Ń','ń', ' ');
	$nopl=array('e','e','o','o','l','l','s','s','a','a','z','z','z','z','c','c','n','n', '');
	return str_replace($pl, $nopl, $string);
}

function checkTryb() {
	$tryby=array('gpx', 'szlaki', 'inne');
	if(isset($_GET['tryb'])){
		$name=$_GET['tryb'];
	}
	else if(isset($_POST['tryb'])){
		$name=$_POST['tryb'];
	}
	else{
		$name='gpx';
	}
	if(in_array($name, $tryby)){
		return $name;
	}
	else{
		return 'gpx';
	}
}

?>