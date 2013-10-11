<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<?php
$uploadfile="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/tmp/" . basename($_FILES['gpx']['name']);

if (move_uploaded_file($_FILES['gpx']['tmp_name'], $uploadfile)) {
	$xml = XMLReader::open($uploadfile);
	$xml->setParserProperty(XMLReader::VALIDATE, true);
	if($xml->isValid()){
		$status=1;
		$desc=preg_replace("/[^A-Za-z0-9ążśźęćńółĄŻŚŹĘĆŃÓŁ-]/u", "", $_POST['desc']);
        	$cmd="/media/a1f63e22-1c18-4ff1-b63c-f4fcda0408eb/www/rower/process.sh " . basename($_FILES['gpx']['name']) . " " . $desc;
		exec($cmd);
	}
	else{
		unlink($uploadfile);
	}
}
else {
	$status=0;
}

echo "<head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
        <title>Rower</title>";
if($status){
	echo "<meta http-equiv=\"Refresh\" content=\"3; url=index.php\"";
}
echo "<link rel=\"stylesheet\" href=\"gpx.css\" />
</head>
<body>";

if($status){
	echo "Dodano";
}
else {
	echo "Błąd";
}

?>

</body>
</html>
