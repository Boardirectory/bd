<?php
/**
 *
 * @author Jeroen Bollen jbollensb@gmail.com
 * @package GDS
 * @version 3.1.0
 * @copyright (c) 2012 Jeroen Bollen
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 */
 
if(!defined('IN_DIRECTORY')) {
	exit;
}

class Template {
	private $loader;
	private $twig;
	public $vars = Array();
	public $template;
	private $root;
	private $page = 'page.html';
	
	public function __construct() {
		include_once(GDS::$DirectoryRootPath . 'Twig/Autoloader.php');
		Twig_Autoloader::register();
		$this->root = GDS::$DirectoryRootPath . 'templates';
	}
	
	public function setMode($mode) {
		if($mode == 'theme') {
			$this->root = GDS::$DirectoryRootPath . 'themes';
			$this->page = 'default.css';
		}
	}
	public function outputPage($template = NULL) {
		$this->loader = new Twig_Loader_Filesystem($this->root);
		$this->twig = new Twig_Environment($this->loader, array(
			'cache'			=> GDS::$DirectoryRootPath . 'cache/Twig',
			'auto_reload'	=> true,
		));
		if($template != NULL) {
			$this->template = $template;
		}
		$this->prepareVars();
		echo($this->twig->loadTemplate($this->page)->render($this->vars));
		if(!GDS::DEBUGMODE)
			die();
		echo('<br><pre style="background:#FFFFFF;font-size:13px;font-family:Courrier;font-weight:bold;padding:5px;color:#000000;">');
		echo('CurrentUser Name: ' . GDS::$CurrentUser->data['user_name']);
		echo("\nCurrentUser ID: " . GDS::$CurrentUser->data['user_id']);
		echo("\nSession ID: " . session_id());
		
		/**
		 * Extraordinary Debug Information
		 * May Contain Sensitive Information and Reveil Personal Data
		 * Do Only Use When Crucial and Avoid Using Real Personal Data
		 */

		//	echo("\n\nGet: \n");
		//	var_dump($_GET);
		
		echo('</pre>');
		die();
	}
	private function prepareVars() {
		$this->vars['lang'] = GDS::$CurrentUser->lang;
		$this->vars['template'] = $this->template;
		$this->vars['directory_root'] = GDS::$DirectoryRootPath;
		$this->vars['style']['image_path'] = GDS::$DirectoryRootPath . 'themes/images/default/';
		$this->vars['style']['name'] = 'default';
	}
}
?>