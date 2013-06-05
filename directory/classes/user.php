<?php 
if(!defined('IN_DIRECTORY'))
	exit;

class User {
	public $data = array();
	public $groups = array();
	public $permissionset = array();
	
	/**
	 * Setup the user with the specified ID.
	 * @param int $id Grab the data matching the specified user from the database and feed it into the current object. 
	 * @return bool Returns true if the user exists, false if not
	 */
	public function setup($id) {
		$query = GDS::$DB->query('SELECT * FROM users WHERE user_id = ' . $id);
		if(($this->data = $query->fetch(PDO::FETCH_ASSOC)))
			return true;
		return true;
	}
	
	public function getGroups() {
		$query = GDS::$DB->prepare('SELECT group_id FROM group_users WHERE user_id = ' . $this->data['user_id']);
		$query->execute();
		if(($result = $query->fetch(PDO::FETCH_NUM))) {
			$groups = array_keys($result);
			foreach($groups as  $group)
				$this->groups[$group] = true;
		}
	}
	
	public function getPermissions() {
		
	}
	
	public function setupPermissions() {
		$query = GDS::$DB->prepare('SELECT * FROM group_auth WHERE group_id = ' . $this->groups[0]);
		$groupsTotal = count($this->groups);
		for($i = 1; $i < $groupsTotal; $i++)
			$query->queryString .= 'OR group_id = ' . $this->groups[$i];
		$query->execute();
		$permissions = array();
		while($permission = $query->fetch(PDO::FETCH_ASSOC)) {
			if($permission['permission_type'] == 2) {
				$permissions[$permission['permission_name']] = 2;
				continue;
			}
			if(array_key_exists($permission['permission_name'], $permissions) && $permissions[$permission['permission_name']] == 2)
					continue;
			$permissions[$permission['permission_name']] = 1;
			
		}
		$query = '';
		foreach($permissions as $permissionName => $permissionType)
			if($permissionType == 1) {
				$query .= "INSERT INTO user_auth (user_id, permission_name) VALUES ({$this->data['user_id']}, '$permissionName');";
				$this->permissionset[$permissionName] = true;
			}
		GDS::$DB->exec($query);
	}
}

class CurrentUser extends User {
	public $lang = array();
	
	/**
	 * Check a user's login details and if valid create a session for the current user. 
	 * @param String $username The username of the user that should be logged in. 
	 * @return bool Returns true if the login details were correct and the session is created. False if the details were incorrect. 
	 */
	public function login($username, $password) {
		/*
		 * Get the user's data from the GDS database. 
		 */
		$query = GDS::$DB->prepare('SELECT * FROM users WHERE user_name = :name');
		$query->bindValue(':name', $username, PDO::PARAM_STR);
		$query->execute();
		$this->data = $query->fetch(PDO::FETCH_ASSOC);
		if(!$this->data)
			return false;
		
		/*
		 * Get the user's phpBB password from the phpBB database. 
		 * Check if the password was correct using phpBB's ported function. 
		 */
		require_once(GDS::$DirectoryRootPath . 'includes/databasephpBB.php');
		$query = GDS::$DBPHPBB->prepare('SELECT user_password FROM users WHERE user_id = ' . $this->data['user_phpbb_id']);
		$query->execute();
		if(!$hash = $query->fetch(PDO::FETCH_ASSOC)['user_password'])
			return false;
		require_once(GDS::$DirectoryRootPath . 'includes/phpbbfunctions.php');
		if(!phpbb_check_hash($password, $hash))
				return false;
		
		$this->createSession();
		return true;
	}

	/**
	 * Destroys the old session and creates a new session for the current user. 
	 * @param String $username The username of the user that should be logged in. 
	 * @return bool Returns true if the login details were correct and the session is created. False if the details were incorrect. 
	 */
	private function createSession() {
		session_start();
		$time = time();
		$_SESSION['user_id'] = $this->data['user_id'];
		$_SESSION['session_start'] = $_SESSION['session_time'] = $time;
		GDS::$DB->exec('DELETE FROM sessions WHERE session_id = \'' . session_id() . '\'');
		$query = GDS::$DB->prepare('INSERT INTO sessions (session_id, user_id, session_start, session_time, session_ip)
			VALUES (:sid, :uid, :ss, :ss, :sip)');
		$query->execute(array(
			':sid'	=> session_id(),
			':uid'	=> $this->data['user_id'],
			':ss'	=> $time,
			':sip'	=> $_SERVER['REMOTE_ADDR'],
		));
		setcookie('bis_[user_id]', $this->data['user_id'], time() + 3600, '/', null, false, true);
		setcookie('bis_[session_start]', $time, time() + 3600, '/', null, false, true);
		setcookie('bis_[session_time]', $time, time() + 3600, '/', null, false, true);
	}
	
	/**
	 * Checks if the current user is logged in. 
	 */
	public function checkSession() {
		if(!session_id())
			session_start();
		$cookies = getCookie('bis_', array());
		if(isset($cookies['user_id'])) {
			$query = GDS::$DB->prepare('SELECT * FROM sessions WHERE session_id = \'' . session_id() . '\'');
			$query->execute();
			if(($session = $query->fetch(PDO::FETCH_ASSOC))) {
				if($_SESSION['user_id'] == $session['user_id'] && $_SESSION['session_start'] == $session['session_start'] && $_SESSION['session_time'] == $session['session_time'] && $_SERVER['REMOTE_ADDR'] == $session['session_ip'] && $session['user_id'] == $cookies['user_id'] && $session['session_start'] == $cookies['session_start'] && $session['session_time'] == $cookies['session_time']) {
					$time = time();
					$query = GDS::$DB->prepare('UPDATE sessions SET session_time = :time WHERE session_id = :sid');
					$query->execute(array(
						':time'	=> $time,
						':sid'	=> session_id(),
					));
					$_SESSION['session_time'] = $time;
					setcookie('bis_[user_id]', $session['user_id'], time() + 3600, '/', null, false, true);
					setcookie('bis_[session_start]', $session['session_start'], time() + 3600, '/', null, false, true);
					setcookie('bis_[session_time]', $time, time() + 3600, '/', null, false, true);
					$this->setup($session['user_id']);
					return;
				} else {
					$this->logout();
				}
			}
		}
		$this->setup(0);
	}
	
	/*
	 * Logout the user and clear the cookies. 
	 */
	public function logout() {
		if(!session_id())
			session_start();
		try {
			GDS::$DB->exec("DELETE FROM sessions WHERE session_id = '{session_id()}'");
		} catch(PDOException $ex) {
			GDS::HandleError(E_USER_NOTICE, 'Abnormal Logout: ' . $ex->getMessage(), $ex->getFile(), $ex->getLine());
		}
		session_regenerate_id(true);
		setcookie('bis_[user_id]', 0, time() - 1, null, null, false, true);
		setcookie('bis_[session_start]', 0, time() - 1, null, null, false, true);
		setcookie('bis_[session_time]', 0, time() - 1, null, null, false, true);

		$this->setup(0);
	}
	
	public function addLang($languageFile) {
		include(GDS::$DirectoryRootPath . "language/en/$languageFile");
		$this->lang = $lang;
	}
}
 