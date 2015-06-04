<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */
require_once 'BaseApp.php';
ini_set('error_log', '/var/log/php/selectivestatus_webapp.log');

use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\GraphUser;

/**
 * html-safe shorthand function
 */
function _h($str)
{
	return SelectiveTweets_WebApp::htmlSafe($str);
}

/**
 * Controller for the Selective Tweets web app
 */
class SelectiveTweets_WebApp extends SelectiveTweets_BaseApp
{
	protected $fb_uid;

	protected function init()
	{
		session_start();
		parent::init();
		$this->initFacebookSession();
	}

	protected function initFacebookSession()
	{
		// first try from redirect
		try {
			$helper = new FacebookRedirectLoginHelper(ROOT_URL);
			$this->fb = $helper->getSessionFromRedirect();
		} catch(\Exception $ex) {
			// When validation fails or other local issues
		}
		if (!$this->fb) {
			// next try from canvas
			try {
				$helper = new FacebookCanvasLoginHelper();
				$this->fb = $helper->getSession();
			} catch(\Exception $ex) {
				// When validation fails or other local issues
			}
		}
		if (!$this->fb) {
			// next try from JS
			try {
				$helper = new FacebookJavaScriptLoginHelper();
				$this->fb = $helper->getSession();
			} catch(\Exception $ex) {
				// When validation fails or other local issues
			}
		}
		// finally fall back to an existing session, if we have one
		if (!$this->fb && !empty($_SESSION['fb_token'])) {
			try {
				$this->fb = new FacebookSession($_SESSION['fb_token']);
				$this->fb->validate();
			} catch(\Exception $ex) {
				// When validation fails or other local issues
			}
		}
		if ($this->fb) {
			// Logged in
			try {
				$_SESSION['fb_token'] = $this->fb->getToken();
				$user_profile = (new FacebookRequest(
					$this->fb, 'GET', '/me'
					))->execute()->getGraphObject(GraphUser::className());
				$this->fb_uid = $user_profile->getId();
			} catch (\Exception $e) {
				$this->fb = null;
			}
		}
		if (!$this->fb) {
			session_destroy();
		}
	}

	/**
	 * Accessor for the Facebook SDK
	 *
	 * @return Facebook
	 */
	public function getFacebook()
	{
		return $this->fb;
	}

	/**
	 * Returns the twitter ID we've associated with the curent Facebook user
	 *
	 * @return string  Twitter ID, null if none
	 */
	public function getTwitterName()
	{
		if ($fb_uid = $this->getFBUID()) {
			return $this->db->queryScalar("SELECT twitterid from selective_status_users where fbuid = " . $this->db->quote($fb_uid) . " limit 1");
		}
	}

	/**
	 * Returns the Facebook UID for the current user
	 *
	 * @return bigmofoint  Whatever precision Facebook are using for GUIDs these days
	 */
	public function getFBUID()
	{
		return $this->fb_uid;
	}

	/**
	 * Returns whether we have permission to publish to the stream for the current Facebook user
	 *
	 * @return boolean
	 */
	public function getCanPublishStream()
	{
		try {
			if (!$this->fb) {
				return false;
			}
			$permissions = (new FacebookRequest(
				$this->fb, 'GET', '/me/permissions'
			))->execute()->getGraphObject();
			foreach($permissions->asArray() as $permission) {
				if ($permission->permission == 'publish_actions' && $permission->status == 'granted') {
					return true;
				}
			}
			// else
			return false;
		} catch (Exception $e) {
			// may not be logged in
		}
	}

	/**
	 * Returns whether we have permission to manage and publish to the stream for Facebook pages that the current Facebook user is an admin of
	 *
	 * @return boolean
	 */
	public function getCanPostToPages()
	{
		try {
			if (!$this->fb) {
				return false;
			}
			$permissions = (new FacebookRequest(
				$this->fb, 'GET', '/me/permissions'
			))->execute()->getGraphObject();
			foreach($permissions->asArray() as $permission) {
				if (($permission->permission == 'publish_actions' || $permission->permission == 'manage_pages') && $permission->status == 'granted') {
					return true;
				}
			}
			// else
			return false;
		} catch (Exception $e) {
			// may not be logged in
		}
	}

	/**
	 * Redirect to another URL within Facebook canvas
	 */
	public function redirect($url)
	{
		if (substr($url, 0, 1) == '/') {
			header('Location: ' . $url);
		} else {
			echo "<script>window.top.location = '" . $url . "';</script>";
		}
		exit();
	}

	/**
	 * Redirect to FB login dialog for this app
	 */
	public function redirectToLogin($url = '')
	{
		try {
			$helper = new FacebookRedirectLoginHelper(ROOT_URL . $url);
			$this->redirect($helper->getLoginUrl());
		} catch (\Exception $e) {
			error_log($e->getMessage());
			$this->redirect(ROOT_URL);
		}
	}

	/**
	 * Require a user logged in to this app - redirectToLogin() if necessary
	 */
	public function requireLogin()
	{
		if (!$this->getFBUID()) {
			$this->redirectToLogin();
		}
	}

	/**
	 * html-encode
	 */
	public function htmlSafe($str)
	{
		return htmlspecialchars($str);
	}

	/**
	 * Returns what we know about the current user
	 *
	 * @return array
	 */
	public function getUserData()
	{
		$fbuid = $this->getFBUID();
		$rs = $this->db->query("SELECT * from selective_status_users where fbuid = " . $this->db->quote($fbuid) . " limit 1");
		if ($rs && $row = $rs->fetch()) {
			return $row;
		}
	}

	/**
	 * Returns array of data about Facebook pages the current user is an admin of
	 *
	 * @return array
	 */
	public function getUserPages()
	{
		$users_pages = array();
		if (!$this->fb) {
			return array();
		}
		$accounts = (new FacebookRequest(
			$this->fb, 'GET', '/me/accounts'
		))->execute()->getGraphObject(GraphUser::className());
		if ($accounts = $accounts->asArray()) {
			foreach ($accounts['data'] as $account) {
				if ($account->category != 'Application') {
					if (!empty($account->id) && !empty($account->name)) {
						$account->twitterid = '';
						$users_pages[$account->id] = $account;
					} else {
						$this->log('invalid account: ' . print_r($account, true), 'badaccounts');
					}
				}
			}
			$rs = $this->db->query("SELECT * from selective_status_users where fbuid IN ('" . implode("', '", array_keys($users_pages)) . "')");
			while ($rs && $row = $rs->fetch()) {
				$users_pages[$row['fbuid']]->twitterid = $row['twitterid'];
			}
		}
		return $users_pages;
	}

	/**
	 * Process THE ONE STEP form
	 */
	public function saveProfile()
	{
		if (isset($_REQUEST['username'])) {
			$username = trim($_REQUEST['username']);
			$fbuid = $this->getFBUID();
			if (isset($_POST['sub_clear'])) { $username = ''; }
			if ($username) {
				$result = $this->db->exec("INSERT IGNORE INTO selective_status_users (fbuid, twitterid) VALUES (" . $this->db->quote($fbuid) . ", " . $this->db->quote($username) . ");");
			}
			$result = $this->db->exec("UPDATE selective_status_users SET twitterid = " . $this->db->quote($username) . ", is_page = 0 WHERE fbuid = " . $this->db->quote($fbuid) . " LIMIT 1");
			return true;
		}
	}

	/**
	 * Process Pages form
	 */
	public function savePages(&$pages)
	{
		if (empty($_POST)) {
			return;
		}
		$donestuff = false;
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 8) == 'username') {
				$the_page_id = trim(substr($key, 8));
				$access_token = !empty($pages[$the_page_id]) ? $pages[$the_page_id]->access_token : '';
				$result = $this->db->exec("INSERT IGNORE INTO selective_status_users (fbuid, twitterid, is_page, fb_oauth_access_token) VALUES ("
					. $this->db->quote($the_page_id) . ", " . $this->db->quote($value) . ", 1, " . $this->db->quote($access_token) . ")"
					. " ON DUPLICATE KEY UPDATE twitterid = " . $this->db->quote($value) . ", is_page = 1, fb_oauth_access_token = " . $this->db->quote($access_token));
				$donestuff = true;
			}
		}
		if ($donestuff) {
			$pages = $this->getUserPages();
		}
		return $donestuff;
	}

	/**
	 * Process Settings form
	 */
	public function saveSettings()
	{
		if (isset($_POST['sub_save'])) {
			$fbuid = $this->getFBUID();
			$show_follow_link = !empty($_POST['show_twitter_link']) ? 2 : 0;
			$allow_tag_anywhere = !empty($_POST['allow_tag_anywhere']) ? 1 : 0;
			$replace_names = !empty($_POST['replace_names']) ? 1 : 0;
			$prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
			$this->db->exec("UPDATE selective_status_users SET allow_tag_anywhere = " . $this->db->quote($allow_tag_anywhere) . ", replace_names = " . $this->db->quote($replace_names) . ", show_twitter_link = " . $this->db->quote($show_follow_link) . ", prefix = " . $this->db->quote($prefix) . " WHERE fbuid = " . $this->db->quote($fbuid) . " LIMIT 1");
			return true;
		}
	}
}

