<?php
include_once('key.php');

require_once('user.php');
$USER = new User('registration_callback');

require_once('functions.php');

$obj=new Rower($key);
$obj->setAuth($USER->authenticated);
$obj->run('process');

?>
