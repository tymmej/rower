<?php

require_once("user.php");
$USER = new User("registration_callback");

include 'key.php';

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
	else {
		$tryb="gpx";
	}
}
else{
	$tryb="gpx";
}

if($USER->authenticated) {
echo "<!DOCTYPE html>
<head>
	<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\"/>
	<!-- Source: http://wiki.openstreetmap.org/wiki/Openlayers_Track_example -->
	<title>Mapka OSM</title>
	<!-- bring in the OpenLayers javascript library
		 (here we bring it from the remote site, but you could
		 easily serve up this javascript yourself) -->
	<!-- <script src=\"http://www.openlayers.org/api/OpenLayers.js\"></script> -->
	<script src=\"js/OpenLayers.js\"></script>
	<!-- bring in the OpenStreetMap OpenLayers layers.
		 Using this hosted file will make sure we are kept up
		 to date with any necessary changes -->
	<script src=\"//www.openstreetmap.org/openlayers/OpenStreetMap.js\"></script>
 
	<script type=\"text/javascript\">
		// Start position for the map (hardcoded here for simplicity,
		// but maybe you want to get this from the URL params)
		var lat=51.15
		var lon=16.90
		var zoom=12
 
		var map; //complex object of type OpenLayers.Map

		qs = document.location.search.split(\"+\").join(\" \");

		var params = {}, tokens,
			re = /[?&]?([^=]+)=([^&]*)/g;

		while (tokens = re.exec(qs)) {
			params[decodeURIComponent(tokens[1])]
				= decodeURIComponent(tokens[2]);
		}

		document.title = \"Mapka OSM \" + params.file;

		function init() {
			map = new OpenLayers.Map (\"map\", {
				controls:[
					new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.PanZoomBar(),
//					new OpenLayers.Control.LayerSwitcher(),
					new OpenLayers.Control.Attribution()],
				maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
				maxResolution: 156543.0399,
				numZoomLevels: 19,
				units: 'm',
				projection: new OpenLayers.Projection(\"EPSG:900913\"),
				displayProjection: new OpenLayers.Projection(\"EPSG:4326\")
			} );
 
			// Define the map layer
			// Here we use a predefined layer that will be kept up to date with URL changes
			layerMapnik = new OpenLayers.Layer.OSM.Mapnik(\"Mapnik\");
			map.addLayer(layerMapnik);
//			layerCycleMap = new OpenLayers.Layer.OSM.CycleMap(\"CycleMap\");
//			map.addLayer(layerCycleMap);
//			layerMarkers = new OpenLayers.Layer.Markers(\"Markers\");
//			map.addLayer(layerMarkers);
 
			// Add the Layer with the GPX Track
			var lgpx = new OpenLayers.Layer.Vector(\"Track\", {
				strategies: [new OpenLayers.Strategy.Fixed()],
				protocol: new OpenLayers.Protocol.HTTP({
					url: \"download.php?tryb=" . $tryb . "&filename=\" + params.file + \".gpx\",
					format: new OpenLayers.Format.GPX()
				}),
				style: {strokeColor: \"red\", strokeWidth: 5, strokeOpacity: 0.5},
				projection: new OpenLayers.Projection(\"EPSG:4326\")
			});
			map.addLayer(lgpx);
 
			var lonLat = new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection(\"EPSG:4326\"), map.getProjectionObject());
//			map.setCenter(lonLat, zoom);
			var dataExtent;
			var setExtent = function()
			{
				if(dataExtent)
					dataExtent.extend(this.getDataExtent());
				else
					dataExtent = this.getDataExtent();
				map.zoomToExtent(dataExtent);
			};
			lgpx.events.register(\"loadend\", lgpx, setExtent);

			var size = new OpenLayers.Size(21, 25);
			var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
			var icon = new OpenLayers.Icon('http://www.openstreetmap.org/openlayers/img/marker.png',size,offset);
			layerMarkers.addMarker(new OpenLayers.Marker(lonLat,icon));
		}
	</script>
 
</head>
<!-- body.onload is called once the page is loaded (call the 'init' function) -->
<body onload=\"init();\">
	<!-- define a DIV into which the map will appear. Make it take up the whole window -->
	<div id=\"map\" style=\"top: 0; left: 0; bottom: 0; right: 0; position: fixed;\"></div>
</body>
</html>";
}
?>
