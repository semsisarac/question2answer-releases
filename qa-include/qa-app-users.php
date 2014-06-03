<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-users.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: User management (application level) for basic user operations


	This software is free to use and modify for public websites, so long as a
	link to http://www.question2answer.org/ is displayed on each page. It may
	not be redistributed or resold, nor may any works derived from it.
	
	More about this license: http://www.question2answer.org/license.php


	THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL
	THE COPYRIGHT HOLDER BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
	LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	define('QA_USER_LEVEL_BASIC', 0);
	define('QA_USER_LEVEL_EXPERT', 20);
	define('QA_USER_LEVEL_EDITOR', 50);
	define('QA_USER_LEVEL_MODERATOR', 80);
	define('QA_USER_LEVEL_ADMIN', 100);
	define('QA_USER_LEVEL_SUPER', 120);
	
	define('QA_USER_FLAGS_EMAIL_CONFIRMED', 1);
	define('QA_USER_FLAGS_USER_BLOCKED', 2);

	
	if (QA_EXTERNAL_USERS) {

	//	If we're using single sign-on integration, load PHP file for that

		require_once QA_EXTERNAL_DIR.'qa-external-users.php';
		

	//	Access functions for user information
	
		function qa_get_logged_in_user_cache($db)
	/*
		Return array of information about the currently logged in user, cache to ensure only one call to external code
	*/
		{
			global $qa_cached_logged_in_user;
			
			if (!isset($qa_cached_logged_in_user)) {
				$user=qa_get_logged_in_user($db);
				$qa_cached_logged_in_user=isset($user) ? $user : false; // to save trying again
			}
			
			return @$qa_cached_logged_in_user;
		}
		
		
		function qa_get_logged_in_user_field($db, $field)
	/*
		Return $field of the currently logged in user, or null if not available
	*/
		{
			$user=qa_get_logged_in_user_cache($db);
			
			return @$user[$field];
		}


		function qa_get_logged_in_userid($db)
	/*
		Return the userid of the currently logged in user, or null if none
	*/
		{
			return qa_get_logged_in_user_field($db, 'userid');
		}
		
		
		
	} else {
		
		function qa_start_session()
	/*
		Open a PHP session if one isn't opened already
	*/
		{
			@ini_set('session.gc_maxlifetime', 86400); // worth a try, but won't help in shared hosting environment
			@ini_set('session.use_trans_sid', false); // sessions need cookies to work, since we redirect after login

			if (!isset($_SESSION))
				session_start();
		}

		
		function qa_set_session_cookie($handle, $sessioncode, $remember)
	/*
		Set cookie in browser for username $handle with $sessioncode (in database).
		Pass true if user checked 'Remember me' (either now or previously, as learned from cookie).
	*/
		{
			// if $remember is true, store in browser for a month, otherwise store only until browser is closed
			setcookie('qa_session', $handle.'/'.$sessioncode.'/'.($remember ? 1 : 0), $remember ? (time()+2592000) : 0, '/');
		}

		
		function qa_clear_session_cookie()
	/*
		Remove session cookie from browser
	*/
		{
			setcookie('qa_session', false, 0, '/');
		}

		
		function qa_set_logged_in_user($db, $userid, $handle='', $remember=false)
	/*
		Call for successful log in by $userid and $handle or successful log out with $userid=null.
		$remember states if 'Remember me' was checked in the login form.
	*/
		{
			qa_start_session();
			
			if (isset($userid)) {
				$_SESSION['qa_session_userid']=$userid;
				
				// PHP sessions time out too quickly on the server side, so we also set a cookie as backup.
				// Logging in from a second browser will make the previous browser's 'Remember me' no longer
				// work - I'm not sure if this is the right behavior - could see it either way.

				$sessioncode=qa_db_user_rand_sessioncode();
				qa_db_user_set($db, $userid, 'sessioncode', $sessioncode);
				qa_set_session_cookie($handle, $sessioncode, $remember);
				
			} else {
				require_once QA_INCLUDE_DIR.'qa-db-users.php';

				qa_db_user_set($db, $_SESSION['qa_session_userid'], 'sessioncode', '');
				qa_clear_session_cookie();

				unset($_SESSION['qa_session_userid']);
			}
		}

		
		function qa_get_logged_in_userid($db)
	/*
		Return the userid of the currently logged in user, or null if none logged in
	*/
		{
			global $qa_logged_in_userid_checked;
			
			if (!$qa_logged_in_userid_checked) { // only check once
				qa_start_session(); // this will load logged in userid from the native PHP session, but that's not enough
				
				if (!empty($_COOKIE['qa_session'])) {
					@list($handle, $sessioncode, $remember)=explode('/', $_COOKIE['qa_session']);
					
					if ($remember)
						qa_set_session_cookie($handle, $sessioncode, $remember); // extend 'remember me' cookies each time
	
					$sessioncode=trim($sessioncode); // trim to prevent passing in blank values to match uninitiated DB rows
	
					// Try to recover session from the database if PHP session has timed out
					if ( (!isset($_SESSION['qa_session_userid'])) && (!empty($handle)) && (!empty($sessioncode)) ) {
						require_once QA_INCLUDE_DIR.'qa-db-selects.php';
						
						$userinfo=qa_db_single_select($db, qa_db_user_account_selectspec($handle, false)); // don't get any pending
						
						if (strtolower(trim($userinfo['sessioncode'])) == strtolower($sessioncode))
							$_SESSION['qa_session_userid']=$userinfo['userid'];
						else
							qa_clear_session_cookie(); // if cookie not valid, remove it to save future checks
					}
				}

				$qa_logged_in_userid_checked=true;
			}
			
			return @$_SESSION['qa_session_userid'];
		}
		
		
		function qa_logged_in_user_selectspec($db)
	/*
		Return selectspec array (see qa-db.php) to get information about currently logged in user
	*/
		{
			global $qa_cached_logged_in_user;
			
			$userid=qa_get_logged_in_userid($db);

			if (isset($userid) && !isset($qa_cached_logged_in_user)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				return qa_db_user_account_selectspec($userid, true);
			}
			
			return null;
		}
		
		
		function qa_logged_in_user_load($db, $selectspec, $gotuser)
	/*
		Called after the information specified by qa_logged_in_user_selectspec() was retrieved
		from the database using $selectspec which returned $gotuser
	*/
		{
			global $qa_cached_logged_in_user;
			
			$qa_cached_logged_in_user=is_array($gotuser) ? $gotuser : false;
		}
		
		
		function qa_get_logged_in_user_field($db, $field)
	/*
		Return $field of the currently logged in user, cache to ensure only one call to external code
	*/
		{
			global $qa_cached_logged_in_user, $qa_logged_in_pending;
			
			$userid=qa_get_logged_in_userid($db);
			
			if (isset($userid) && !isset($qa_cached_logged_in_user)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				$qa_logged_in_pending=true;
				qa_db_select_with_pending($db); // if not yet loaded, retrieve via standard mechanism
			}
			
			return @$qa_cached_logged_in_user[$field];
		}
		
		
		function qa_get_mysql_user_column_type()
	/*
		Return column type to use for users (if not using single sign-on integration)
	*/
		{
			return 'INT UNSIGNED';
		}


		function qa_get_one_user_html($handle, $microformats)
	/*
		Return HTML to display for user with username $handle
	*/
		{
			return strlen($handle) ? ('<A HREF="'.qa_path_html('user/'.$handle).
				'" CLASS="qa-user-link'.($microformats ? ' url nickname' : '').'">'.qa_html($handle).'</A>') : '';
		}
		

		function qa_get_user_email($db, $userid)
	/*
		Return email address for user $userid (if not using single sign-on integration)
	*/
		{
			$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));

			return $userinfo['email'];
		}
		

		function qa_user_report_action($db, $userid, $action, $questionid, $answerid, $commentid)
	/*
		Called after a database write $action performed by a user $userid, relating to $questionid,
		$answerid and/or $commentid (if not using single sign-on integration)
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			qa_db_user_written($db, $userid, @$_SERVER['REMOTE_ADDR']);
		}

		
		function qa_user_level_string($level)
	/*
		Return textual representation of the user $level
	*/
		{
			if ($level>=QA_USER_LEVEL_SUPER)
				$string='users/level_super';
			elseif ($level>=QA_USER_LEVEL_ADMIN)
				$string='users/level_admin';
			elseif ($level>=QA_USER_LEVEL_MODERATOR)
				$string='users/level_moderator';
			elseif ($level>=QA_USER_LEVEL_EDITOR)
				$string='users/level_editor';
			elseif ($level>=QA_USER_LEVEL_EXPERT)
				$string='users/level_expert';
			else
				$string='users/registered_user';
			
			return qa_lang($string);
		}

		
		function qa_get_login_links($rooturl, $tourl)
	/*
		Return an array of links to login, register, email confirm and logout pages (if not using single sign-on integration)
	*/
		{
			return array(
				'login' => qa_path('login', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
				'register' => qa_path('register', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
				'confirm' => qa_path('confirm', null, $rooturl),
				'logout' => qa_path('logout', null, $rooturl),
			);
		}

	} // end of: if (QA_EXTERNAL_USERS) else { }


	function qa_get_logged_in_handle($db)
/*
	Return displayable handle/username of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field($db, QA_EXTERNAL_USERS ? 'publicusername' : 'handle');
	}


	function qa_get_logged_in_email($db)
/*
	Return email of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field($db, 'email');
	}


	function qa_get_logged_in_level($db)
/*
	Return level of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field($db, 'level');
	}

	
	function qa_get_logged_in_flags($db)
/*
	Return flags (see QA_USER_FLAGS_*) of currently logged in user, or null if none
*/
	{
		return QA_EXTERNAL_USERS ? 0 : qa_get_logged_in_user_field($db, 'flags');
	}

	
	function qa_user_permit_error($db, $permitoption=null, $actioncode=null)
/*
	Check whether the logged in user has permission to perform $permitoption.
	If $permitoption is null, this simply checks whether the user is blocked.
	Optionally provide an $actioncode to also check against user or IP rate limits.

	Possible results, in order of priority (i.e. if more than one reason, first given):
	'level' => a special privilege level (e.g. expert) is required
	'login' => the user should login or register
	'userblock' => the user has been blocked
	'ipblock' => the ip address has been blocked
	'confirm' => the user should confirm their email address
	'limit' => the user or IP address has reached a rate limit (if $actioncode specified)
	false => the operation can go ahead
*/
	{
		$permit=isset($permitoption) ? qa_get_option($db, $permitoption) : QA_PERMIT_ALL;

		$userid=qa_get_logged_in_userid($db);
		$userlevel=qa_get_logged_in_level($db);
		$userflags=qa_get_logged_in_flags($db);
		
		
		if ($permit>=QA_PERMIT_ALL)
			$error=false;
			
		elseif ($permit>=QA_PERMIT_USERS)
			$error=isset($userid) ? false : 'login';
			
		elseif ($permit>=QA_PERMIT_CONFIRMED) {
			if (!isset($userid))
				$error='login';
			
			elseif (
				QA_EXTERNAL_USERS || // not currently supported by single sign-on integration
				($userlevel>=QA_USER_LEVEL_EXPERT) || // if assigned to a higher level, no need
				($userflags & QA_USER_FLAGS_EMAIL_CONFIRMED) || // actual confirmation
				(!qa_get_option($db, 'confirm_user_emails')) // if this option off, we can't ask it of the user
			)
				$error=false;
			
			else
				$error='confirm';

		} elseif ($permit>=QA_PERMIT_EXPERTS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_EXPERT)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_EDITORS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_EDITOR)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_MODERATORS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_MODERATOR)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_ADMINS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_ADMIN)) ? false : 'level';
			
		else
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_SUPER)) ? false : 'level';
		

		if (isset($userid) && ($userflags & QA_USER_FLAGS_USER_BLOCKED) && ($error!='level'))
			$error='userblock';
		
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';

		if ((!$error) && qa_is_ip_blocked($db))
			$error='ipblock';
		
		if (isset($actioncode) && !$error)
			if (qa_limits_remaining($db, $userid, $actioncode)<=0)
				$error='limit';
		
		return $error;
	}
	
	
	function qa_user_use_captcha($db, $captchaoption)
/*
	Return whether a captcha should be presented for operation specified by $captchaoption
*/
	{
		$usecaptcha=false;
		
		if (qa_get_option($db, $captchaoption)) {
			$userid=qa_get_logged_in_userid($db);
			
			if ( (!isset($userid)) || !(
				QA_EXTERNAL_USERS ||
				(!qa_get_option($db, 'captcha_on_unconfirmed')) || // we might not care about unconfirmed users
				(!qa_get_option($db, 'confirm_user_emails')) || // if this option off, we can't ask it of the user
				(qa_get_logged_in_level($db)>=QA_USER_LEVEL_EXPERT) || // if assigned to a higher level, no need
				(qa_get_logged_in_flags($db) & QA_USER_FLAGS_EMAIL_CONFIRMED) // actual confirmation
			))
				$usecaptcha=true;
		}
		
		return $usecaptcha;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/