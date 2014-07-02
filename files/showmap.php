<?php

require_once('user.php');
$USER = new User('registration_callback');

include 'key.php';
require_once('functions.php');

$data_path='users';
$user=$_SESSION['username'];

$tryb=checkTryb();

if(isset($_GET['multi'])){
	if($_GET['multi']=='true'){
		$multi=1;
	}
	else if($_GET['multi']=='false'){
		$multi=0;
	}
	else {
		$multi=0;
	}
}
else{
	$multi=0;
}

if(isset($_GET['source'])){
	if($_GET['source']=='google'){
		$source='google';
	}
	else if($_GET['source']=='osm'){
		$source='osm';
	}
	else {
		$source='google';
	}
}
else{
	$source='google';
}

if($multi) {
	$source='google';
}

if($USER->authenticated) {
	$file_name = basename($_GET['file']);
	$file = $data_path . '/' . $user . '/' . $tryb . '/' . $file_name . '.gpx';
	if(file_exists($file)){
		if($source=='google'){
			echo '<!DOCTYPE html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
			<title>Mapka Google</title>
			<style type="text/css">
				v:* {
					behavior:url(#default#VML);
				}
			</style>
	
			<!-- Make the document body take up the full screen -->
			<style type="text/css">
				html, body {width: 100%; height: 100%}
				body {margin-top: 0px; margin-right: 0px; margin-left: 0px; margin-bottom: 0px}
			</style>
			<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
			<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=' . $key . '&amp;sensor=false"></script>
			<script type="text/javascript" src="js/loadgpx.js"></script>
			<script type="text/javascript">
				function loadGPXFileIntoGoogleMap(map, filename) {
					$.ajax({url: filename,
						dataType: "xml",
						success: function(data) {
						  var parser = new GPXParser(data, map);
						  parser.setTrackColour("#ff0000"); // Set the track line colour
						  parser.setTrackWidth(5); // Set the track line width
						  parser.setMinTrackPointDelta(0.0001); // Set the minimum distance between track points
						  parser.centerAndZoom(data);
						  parser.addTrackpointsToMap(); // Add the trackpoints
						  parser.addWaypointsToMap(); // Add the waypoints
						}
					});
				}
	
				$(document).ready(function() {
					qs = document.location.search.split("+").join(" ");
					var params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
					while (tokens = re.exec(qs)) {
						params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
					}
					var mapOptions = {
						zoom: 8,
						mapTypeId: google.maps.MapTypeId.HYBRID
					};
					var map = new google.maps.Map(document.getElementById("map"),
						mapOptions);';
		if(!$multi) {
			echo 'loadGPXFileIntoGoogleMap(map,  "download.php?tryb=' . $tryb . '&filename=" + params.file + ".gpx");';
			echo 'document.title = "Mapka Google " + params.file;';
		}
		else{
			$files=array_diff(scandir($data_path . '/' . $user . '/gpx/'), array('..', '.'));
			$start=$_POST['start'];
			$lenstart=strlen($start);
			if($_POST['end'] != '') {
				$end=$_POST['end'];
			}
			else {
				$end=date('Ymd');
			}
			$lenend=strlen($end);
			foreach ($files as $key => $value) {
				if (substr($value, 0, $lenstart) >= $start && substr($value, 0, $lenend) <= $end) {
					echo 'loadGPXFileIntoGoogleMap(map, "download.php?tryb=' . $tryb . '&filename=' . $value . '");';
				}
			}
			echo 'document.title = "Mapka Google ' .  $start . ' - ' . $end .'"';
		}
		echo '});
			</script>
		</head>
		<body>
			<div id="map" style="width: 100%; height: 100%;"></div>
		</body>
	</html>';
		}
		else if($source=='osm'){
			echo '<!DOCTYPE html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
			<!-- Source: http://wiki.openstreetmap.org/wiki/Openlayers_Track_example -->
			<title>Mapka OSM</title>
			<script src="js/OpenLayers.js"></script>
			<script src="//www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>
			<script type="text/javascript">
				var lat=51.15
				var lon=16.90
				var zoom=12
				var map;
				qs = document.location.search.split("+").join(" ");
				var params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
				while (tokens = re.exec(qs)) {
					params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
				}
				document.title = "Mapka OSM " + params.file;
				function init() {
					map = new OpenLayers.Map ("map", {
						controls:[
							new OpenLayers.Control.Navigation(),
							new OpenLayers.Control.PanZoomBar(),
							new OpenLayers.Control.Attribution()],
						maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
						maxResolution: 156543.0399,
						numZoomLevels: 19,
						units: "m",
						projection: new OpenLayers.Projection("EPSG:900913"),
						displayProjection: new OpenLayers.Projection("EPSG:4326")
					} );
					layerMapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik");
					map.addLayer(layerMapnik);
					var lgpx = new OpenLayers.Layer.Vector("Track", {
						strategies: [new OpenLayers.Strategy.Fixed()],
						protocol: new OpenLayers.Protocol.HTTP({
							url: "download.php?tryb=' . $tryb . '&filename=" + params.file + ".gpx",
							format: new OpenLayers.Format.GPX()
						}),
						style: {strokeColor: "red", strokeWidth: 5, strokeOpacity: 0.5},
						projection: new OpenLayers.Projection("EPSG:4326")
					});
					map.addLayer(lgpx);
		 
					var lonLat = new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
					var dataExtent;
					var setExtent = function()
					{
						if(dataExtent)
							dataExtent.extend(this.getDataExtent());
						else
							dataExtent = this.getDataExtent();
						map.zoomToExtent(dataExtent);
					};
					lgpx.events.register("loadend", lgpx, setExtent);
					var size = new OpenLayers.Size(21, 25);
					var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
					var icon = new OpenLayers.Icon("http://www.openstreetmap.org/openlayers/img/marker.png",size,offset);
					layerMarkers.addMarker(new OpenLayers.Marker(lonLat,icon));
				}
			</script>
		</head>
		<body onload="init();">
		<div id="map" style="top: 0; left: 0; bottom: 0; right: 0; position: fixed;"></div>
		</body>
	</html>';
		}
	}
	else {
		echo 'File does not exist';
	}
}
?>
