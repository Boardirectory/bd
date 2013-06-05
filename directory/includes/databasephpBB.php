<?php
if(!defined('IN_DIRECTORY'))
	exit;

if(!isset($phpbb_root_path))
	global $phpbb_root_path;
include($phpbb_root_path . 'config.php');
GDS::$DBPHPBB = new PDO('mysql:host=localhost;dbname=board', 'board', $dbpasswd, array(
	PDO::ATTR_PERSISTENT	=> true,
));
unset($dbpasswd);
GDS::$DBPHPBB->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
?>
