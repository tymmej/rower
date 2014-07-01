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

require_once("functions.php");

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
