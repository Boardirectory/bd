<?php

if(!defined('IN_DIRECTORY'))
	exit;

/**
 * General Directory Script Class
 * Contains all crucial variables
 */
class GDS {
	public static $CurrentUser;
	public static $Template;
	public static $DB;
	public static $DBPHPBB;
	public static $DirectoryRootPath = './';
	const DEBUGMODE = true;
	
	/**
	 * Path to the error log. 
	 * @var String
	 */
	public static $ErrorLog = 'protected/log.html';
	
	public static function Initialize() {
		include_once(GDS::$DirectoryRootPath . 'includes/constants.php');
		include_once(GDS::$DirectoryRootPath . 'includes/commonfunctions.php');
		set_error_handler(array('GDS', 'HandleError'));
		set_exception_handler(array('GDS', 'HandleException'));
		global $phpbb_root_path;
		$phpbb_root_path = './../';
	}
	
	public static function InitializeDB() {
		GDS::$DB = new PDO('mysql:host=localhost;dbname=GDSdb', 'GDSdb', base64_decode(file_get_contents(GDS::$DirectoryRootPath . 'protected/dbpass')), array(
			PDO::ATTR_PERSISTENT	=> true,
		));
		GDS::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	public static function InitializeTemplate() {
		include_once(GDS::$DirectoryRootPath . 'classes/template.php');
		GDS::$Template = new Template();
	}
	
	public static function InitializeCurrentUser() {
		session_name('GDS');
		include_once(GDS::$DirectoryRootPath . 'classes/user.php');
		GDS::$CurrentUser = new CurrentUser();
		
		/* Logout the user if needed */
		if(requestVar('logout', false))
				GDS::$CurrentUser->logout();
		
		/*
		 * Attempt to login the user when login data is provided. 
		 * If login failed or no login data is provided, check if the user is
		 * already logged in and setup that user. 
		 */
		if(($loginData = requestVar('login', array()))
				&& isset($loginData['username'])
				&& isset($loginData['password'])
				&& GDS::$CurrentUser->login($loginData['username'], $loginData['password'])) {
			
			GDS::$CurrentUser->getGroups();
			GDS::$CurrentUser->setupPermissions();
		} else {
			GDS::$CurrentUser->checkSession();
			GDS::$CurrentUser->getGroups();
			GDS::$CurrentUser->getPermissions();
		}
	}
	
	public static function InitializeGeneral() {
		GDS::Initialize();
		GDS::InitializeDB();
		GDS::InitializeCurrentUser();
		GDS::InitializeTemplate();
	}
	
	public static function HandleError($error, $message, $file, $line) {
		switch($error) {
			case E_USER_NOTICE;
				GDS::AddToErrorLog($message, $file, $line);
				return true;
			default;
				GDS::AddToErrorLog($message, $file, $line);
				GDS::Crash();
				return true;
		}
		return false;
	}
	
	/**
	 * Converts Exception into Loggable Error. 
	 * @param Exception $ex
	 * @return boolean
	 */
	public static function HandleException(Exception $ex) {
		GDS::HandleError(E_USER_ERROR, get_class($ex) . ': ' . $ex->getMessage(), $ex->getFile(), $ex->getLine());
		return true;
	}
	
	public static function AddToErrorLog($message, $file, $line) {
		$logh = fopen(GDS::$ErrorLog, 'a');
		fwrite($logh, time() . ': ' . $message . ' - ' . $file . ':' . $line . PHP_EOL);
		fclose($logh);
	}
	
	/**
	 * Stops the GDS and displays an error message. Should not be manually called. 
	 */
	public static function Crash() {
		if(!GDS::$Template) {
			include_once(GDS::$DirectoryRootPath . 'classes/template.php');
			GDS::$Template = new Template();
		}
		try {
			GDS::$Template->vars['error'] = 'An error occured. Please notify an administrator and try again later. ';
			GDS::$Template->outputPage('error.html');
		} catch(Exception $ex) {
			GDS::AddToErrorLog(get_class($ex) . ': ' . $ex->getMessage(), $ex->getFile(), $ex->getLine());
			die('An error occured. Please notify an administrator and try again later. ');
		}
		die('An error occured. Please notify an administrator and try again later. ');
	}
}
?>
