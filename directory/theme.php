<?php
define('IN_DIRECTORY', true);
if(!$_GET['theme']) {
	header('HTTP/1.0 404 Not Found');
	die();
}
header('Content-type: text/css; charset=UTF-8');
include('classes/GDS.php');
GDS::InitializeTemplate();
GDS::$Template->setMode('theme');
GDS::$Template->outputPage($_GET['theme'] . '.css');
?>