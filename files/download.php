<?php

require_once("user.php");
$USER = new User("registration_callback");

$file_name = basename($_GET['filename']);
$ext = pathinfo($file_name, PATHINFO_EXTENSION);

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
}
else{
	$tryb="gpx";
}

if($USER->authenticated) {	
	if($ext == "png") {
		$file = $data_path . "/" . $user . "/maps/" . $tryb . "/" . $file_name;
	}
	else if($ext == "gpx") {
		$file = $data_path . "/" . $user . "/" . $tryb . "/" . $file_name;
	}
	if(file_exists($file)){
		header('Content-Description: File Transfer');
	
		if($ext == "png") {
			header('Content-Type: image/png');
		}
		else if($ext == "gpx") {
			header('Content-Type: application/gpx+xml');
		}
		header('Content-Disposition: inline; filename=' . $file_name);
		header('Content-Transfer-Encoding: binary');
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 3600*24*30));
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
	}
	else {
		echo 'File does not exist';
	}
}
else {
	echo "Please log in";
}
?>
