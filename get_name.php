<?php

$gpx=simplexml_load_file($argv[1]);
echo $gpx->trk->name;

?>
