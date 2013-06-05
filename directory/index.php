<?php
define('IN_DIRECTORY', true);
include_once('./classes/GDS.php');
GDS::InitializeGeneral();
GDS::$Template->outputPage('index.html');
?>