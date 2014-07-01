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

require_once('user.php');
$USER = new User('registration_callback');
//------------END-LOGIN------------//

require_once('functions.php');

$obj=new Rower;
$obj->setAuth($USER->authenticated);
$obj->run();

?>
