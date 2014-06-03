<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-users.php
	Version: 1.0-beta-3
	Date: 2010-03-31 12:13:41 GMT


	This software is licensed for use in websites which are connected to the
	public world wide web and which offer unrestricted access worldwide. It
	may also be freely modified for use on such websites, so long as a
	link to http://www.question2answer.org/ is displayed on each page.


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
	define('QA_USER_LEVEL_EDITOR', 50); // edit all posts
	define('QA_USER_LEVEL_ADMIN', 100); // grant editor privileges, edit user details
	define('QA_USER_LEVEL_SUPER', 120); // grant admin privileges
	
	if (QA_EXTERNAL_USERS) {

		require_once QA_EXTERNAL_DIR.'qa-external-users.php';
		
	} else {
		
		define('QA_MIN_PASSWORD_LEN', 4);
		define('QA_NEW_PASSWORD_LEN', 8);

		function qa_handle_email_validate($db, $handle, $email, $allowuserid=null)
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$errors=array();
			
			if (empty($handle))
				$errors['handle']=qa_lang('users/handle_empty');
	
			elseif (preg_match('/[\\@\\+\\/]/', $handle))
				$errors['handle']=qa_lang_sub('users/handle_has_bad', '@ + /');
			
			elseif (qa_strlen($handle)>QA_DB_MAX_HANDLE_LENGTH)
				$errors['handle']=qa_lang_sub('main/max_length_x', QA_DB_MAX_HANDLE_LENGTH);
			
			else {
				$handleusers=qa_db_user_find_by_handle($db, $handle);
				if (count($handleusers) && ( (!isset($allowuserid)) || (count($handleusers)>1) || ($handleusers[0]!=$allowuserid) ) )
					$errors['handle']=qa_lang('users/handle_exists');
			}
			
			if (empty($email))
				$errors['email']=qa_lang('users/email_required');
			
			elseif (!qa_email_validate($email))
				$errors['email']=qa_lang('users/email_invalid');
			
			elseif (qa_strlen($email)>QA_DB_MAX_EMAIL_LENGTH)
				$errors['email']=qa_lang_sub('main/max_length_x', QA_DB_MAX_EMAIL_LENGTH);
				
			else {
				$emailusers=qa_db_user_find_by_email($db, $email);
				if (count($emailusers) && ( (!isset($allowuserid)) || (count($emailusers)>1) || ($emailusers[0]!=$allowuserid) ) )
					$errors['email']=qa_lang('users/email_exists');
			}
			
			return $errors;
		}

		function qa_profile_fields_validate($db, $name, $location, $website, $about)
		{
			require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$errors=array();
			
			if (qa_strlen($name)>QA_DB_MAX_PROFILE_CONTENT_LENGTH)
				$errors['name']=qa_lang_sub('main/max_length_x', QA_DB_MAX_PROFILE_CONTENT_LENGTH);
				
			if (qa_strlen($location)>QA_DB_MAX_PROFILE_CONTENT_LENGTH)
				$errors['location']=qa_lang_sub('main/max_length_x', QA_DB_MAX_PROFILE_CONTENT_LENGTH);

			if (qa_strlen($website)>QA_DB_MAX_PROFILE_CONTENT_LENGTH)
				$errors['website']=qa_lang_sub('main/max_length_x', QA_DB_MAX_PROFILE_CONTENT_LENGTH);
				
			if (qa_strlen($about)>QA_DB_MAX_PROFILE_CONTENT_LENGTH)
				$errors['about']=qa_lang_sub('main/max_length_x', QA_DB_MAX_PROFILE_CONTENT_LENGTH);
			
			return $errors;
		}
		
		function qa_password_validate($password)
		{
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
			$errors=array();
	
			$minpasslen=max(QA_MIN_PASSWORD_LEN, 1);
			
			if (qa_strlen($password)<$minpasslen)
				$errors['password']=qa_lang_sub('users/password_min', $minpasslen);
			
			return $errors;
		}
		
		function qa_create_new_user($db, $email, $password, $handle)
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-db-points.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';

			$userid=qa_db_user_create($db, $email, $password, $handle, QA_USER_LEVEL_BASIC, @$_SERVER['REMOTE_ADDR']);
			qa_db_points_update_ifuser($db, $userid, null);
			
			qa_notification_pending();
			
			$options=qa_get_options($db, array('custom_welcome', 'site_url'));
			
			$custom=trim($options['custom_welcome']);
			
			qa_send_notification($db, $userid, $email, $handle, qa_lang('emails/welcome_subject'), qa_lang('emails/welcome_body'), array(
				'^password' => $password,
				'^url' => $options['site_url'],
				'^custom' => empty($custom) ? '' : ($custom."\n\n"),
			));
			
			return $userid;
		}
		
		function qa_start_session()
		{
			@ini_set('session.gc_maxlifetime', 86400); // worth a try, but won't help in shared hosting environment
			if (!isset($_SESSION))
				session_start();
		}
		
		function qa_set_session_cookie($handle, $sessioncode, $remember)
		{
			setcookie('qa_session', $handle.'/'.$sessioncode.'/'.($remember ? 1 : 0), $remember ? (time()+2592000) : 0, '/');
		}
		
		function qa_clear_session_cookie()
		{
			setcookie('qa_session', false, 0, '/');
		}
		
		function qa_set_logged_in_user($db, $userid, $remember=false)
		{
			qa_start_session();
			
			if (isset($userid)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
		
				$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));
				
				$_SESSION['qa_session_userid']=$userid;
				$_SESSION['qa_session_level']=$userinfo['level'];
				$_SESSION['qa_session_handle']=$userinfo['handle'];
				$_SESSION['qa_session_email']=$userinfo['email'];
				
				// PHP sessions time out too quickly on the server side, so we also set a cookie as backup
				$sessioncode=qa_db_user_rand_sessioncode();
				qa_db_user_set($db, $userid, 'sessioncode', $sessioncode);
				qa_set_session_cookie($userinfo['handle'], $sessioncode, $remember);
				
			} else {
				require_once QA_INCLUDE_DIR.'qa-db-users.php';

				qa_db_user_set($db, $_SESSION['qa_session_userid'], 'sessioncode', NULL);
				qa_clear_session_cookie();

				unset($_SESSION['qa_session_userid']);
				unset($_SESSION['qa_session_level']);
				unset($_SESSION['qa_session_handle']);
				unset($_SESSION['qa_session_email']);				
			}
		}
		
		function qa_start_reset_user($db, $userid)
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';		
	
			qa_db_user_set($db, $userid, 'resetcode', qa_db_user_rand_resetcode());

			qa_notification_pending();
			qa_options_set_pending(array('site_url'));
			
			$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));

			if (!qa_send_notification($db, $userid, $userinfo['email'], $userinfo['handle'], qa_lang('emails/reset_subject'), qa_lang('emails/reset_body'), array(
				'^code' => $userinfo['resetcode'],
				'^url' => qa_path('reset', array('c' => $userinfo['resetcode'], 'e' => $userinfo['email']), qa_get_option($db, 'site_url')),
			)))
				qa_fatal_error('Could not send reset password email');
		}
		
		function qa_complete_reset_user($db, $userid)
		{
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';		
		
			$password=qa_random_alphanum(max(QA_MIN_PASSWORD_LEN, QA_NEW_PASSWORD_LEN));
			
			qa_notification_pending();
			qa_options_set_pending(array('site_url'));
			
			$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));
			
			if (!qa_send_notification($db, $userid, $userinfo['email'], $userinfo['handle'], qa_lang('emails/new_password_subject'), qa_lang('emails/new_password_body'), array(
				'^password' => $password,
				'^url' => qa_get_option($db, 'site_url'),
			)))
				qa_fatal_error('Could not send new password - password not reset');
			
			qa_db_user_set_password($db, $userid, $password); // do this last, to be safe
			qa_db_user_set($db, $userid, 'resetcode', ''); // so can't be reused
		}
		
		function qa_get_logged_in_user($db)
		{
			qa_start_session();
			
			if (!empty($_COOKIE['qa_session'])) {
				@list($handle, $sessioncode, $remember)=explode('/', $_COOKIE['qa_session']);
				
				if ($remember)
					qa_set_session_cookie($handle, $sessioncode, $remember); // extend 'remember me' cookies each time

				$sessioncode=trim($sessioncode); // to prevent passing in blank values to match uninitiated DB rows

				// Try to recover session from the database if PHP session has timed out
				if ( (!isset($_SESSION['qa_session_userid'])) && (!empty($handle)) && (!empty($sessioncode)) ) {
					require_once QA_INCLUDE_DIR.'qa-db-selects.php';
					
					$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($handle, false));
					
					if (strtolower($userinfo['sessioncode']) == strtolower($sessioncode)) {
						$_SESSION['qa_session_userid']=$userinfo['userid'];
						$_SESSION['qa_session_level']=$userinfo['level'];
						$_SESSION['qa_session_handle']=$userinfo['handle'];
						$_SESSION['qa_session_email']=$userinfo['email'];

					} else
						qa_clear_session_cookie(); // if not valid, remove it to save future checks
				}
			}
			
			if (isset($_SESSION['qa_session_userid']))
				return array(
					'userid' => $_SESSION['qa_session_userid'],
					'level' => $_SESSION['qa_session_level'],
					'handle' => $_SESSION['qa_session_handle'],
					'email' => $_SESSION['qa_session_email'],
				);
				
			return null;
		}
		
		function qa_get_mysql_user_column_type()
		{
			return 'INT UNSIGNED';
		}

		function qa_get_one_user_html($handle, $microformats)
		{
			return strlen($handle) ? ('<A HREF="'.qa_path_html('user/'.urlencode($handle)).
				'" CLASS="qa-user-link'.($microformats ? ' url nickname' : '').'">'.qa_html($handle).'</A>') : '';
		}
		
		function qa_get_logged_in_user_html($db, $userinfo, $rooturl)
		{
			return qa_get_one_user_html(@$userinfo['handle'], false);
		}
		
		function qa_get_user_email($db, $userid)
		{
			$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));

			return $userinfo['email'];
		}
		
		function qa_user_report_action($db, $userid, $action, $questionid, $answerid, $commentid)
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			qa_db_user_written($db, $userid, @$_SERVER['REMOTE_ADDR']);
		}
		
		function qa_user_level_string($level)
		{
			if ($level>=QA_USER_LEVEL_SUPER)
				$string='users/level_super';
			elseif ($level>=QA_USER_LEVEL_ADMIN)
				$string='users/level_admin';
			elseif ($level>=QA_USER_LEVEL_EDITOR)
				$string='users/level_editor';
			else
				$string='users/registered_user';
			
			return qa_lang($string);
		}
		
		function qa_get_login_links($rooturl, $tourl)
		{
			return array(
				'login' => qa_path('login', array('to' => $tourl), $rooturl),
				'register' => qa_path('register', array('to' => $tourl), $rooturl),
				'logout' => qa_path('logout', null, $rooturl),
			);
		}	
	}
	
?>