<?php

if(!defined('IN_DIRECTORY')) {
	exit;
}

function getVar($var, $default) {
	if(!array_key_exists($var, $_POST)) {
		if(!array_key_exists($var, $_GET))
			return $default;
		else
			$result = $_GET[$var];
	} else
		$result = $_POST[$var];
	settype($result, gettype($default));
	if(!$result) $result = $default;
	if(is_array($result)) {
		foreach($result as $key => $value) {
			$result[$key] = utf8_encode(str_replace(array("\r\n", "\r"), array("\n", "\n"), $value));
		}
		return $result;
	}
	return utf8_encode(str_replace(array("\r\n", "\r"), array("\n", "\n"), $result));
}

function getCookie($cookie, $default) {
	if(!array_key_exists($cookie, $_COOKIE))
			return $default;
	$result = $_COOKIE[$cookie];
	settype($result, gettype($default));
	if(is_array($result)) {
		foreach($result as $key => $value) {
			$result[$key] = utf8_encode(str_replace(array("\r\n", "\r"), array("\n", "\n"), $value));
		}
		return $result;
	}
	return utf8_encode(str_replace(array("\r\n", "\r"), array("\n", "\n"), $result));
}
?>