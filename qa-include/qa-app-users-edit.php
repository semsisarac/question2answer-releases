<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-users-edit.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: User management (application level) for creating/modify users


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


	define('QA_MIN_PASSWORD_LEN', 4);
	define('QA_NEW_PASSWORD_LEN', 8);


	function qa_handle_email_validate($db, $handle, $email, $allowuserid=null)
/*
	Return $errors fields for any invalid aspect of user-entered $handle (username) and $email.
	Also rejects existing values in database unless they belongs to $allowuserid (if set).
*/
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
/*
	Return $errors fields for any invalid aspect of user-entered profile information
*/
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
/*
	Return $errors fields for any invalid aspect of user-entered password
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';

		$errors=array();

		$minpasslen=max(QA_MIN_PASSWORD_LEN, 1);
		
		if (qa_strlen($password)<$minpasslen)
			$errors['password']=qa_lang_sub('users/password_min', $minpasslen);
		
		return $errors;
	}

	
	function qa_create_new_user($db, $email, $password, $handle, $level=QA_USER_LEVEL_BASIC)
/*
	Create a new user (application level) with $email, $password, $handle and $level.
	Handles user points, notification and optional email confirmation.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$userid=qa_db_user_create($db, $email, $password, $handle, $level, @$_SERVER['REMOTE_ADDR']);
		qa_db_points_update_ifuser($db, $userid, null);
		
		qa_notification_pending();
		
		$options=qa_get_options($db, array('custom_welcome', 'site_url', 'confirm_user_emails'));
		
		$custom=trim($options['custom_welcome']);
		
		if ($options['confirm_user_emails'] && ($level<QA_USER_LEVEL_EXPERT))
			$confirm=strtr(qa_lang('emails/welcome_confirm'), array(
				'^url' => qa_get_new_confirm_url($db, $userid, $handle)
			));
		else
			$confirm='';
		
		qa_send_notification($db, $userid, $email, $handle, qa_lang('emails/welcome_subject'), qa_lang('emails/welcome_body'), array(
			'^password' => $password,
			'^url' => $options['site_url'],
			'^custom' => empty($custom) ? '' : ($custom."\n\n"),
			'^confirm' => $confirm,
		));
		
		return $userid;
	}

	
	function qa_send_new_confirm($db, $userid)
/*
	Set a new email confirmation code for the user and send it out
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		qa_notification_pending();
		qa_options_set_pending(array('site_url'));
		
		$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));
		
		if (!qa_send_notification($db, $userid, $userinfo['email'], $userinfo['handle'], qa_lang('emails/confirm_subject'), qa_lang('emails/confirm_body'), array(
			'^url' => qa_get_new_confirm_url($db, $userid, $userinfo['handle']),
		)))
			qa_fatal_error('Could not send email confirmation');
	}

	
	function qa_get_new_confirm_url($db, $userid, $handle)
/*
	Set a new email confirmation code for the user and return the corresponding link
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		
		$emailcode=qa_db_user_rand_emailcode();
		qa_db_user_set($db, $userid, 'emailcode', $emailcode);
		
		return qa_path('confirm', array('c' => $emailcode, 'u' => $handle), qa_get_option($db, 'site_url'));
	}

	
	function qa_complete_confirm($db, $userid)
/*
	Complete the email confirmation process for the user
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		
		qa_db_user_set_flag($db, $userid, QA_USER_FLAGS_EMAIL_CONFIRMED, true);
		qa_db_user_set($db, $userid, 'emailcode', ''); // to prevent re-use	of the code
	}

	
	function qa_start_reset_user($db, $userid)
/*
	Start the 'I forgot my password' process for $userid, sending reset code
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';

		qa_db_user_set($db, $userid, 'emailcode', qa_db_user_rand_emailcode());

		qa_notification_pending();
		qa_options_set_pending(array('site_url'));
		
		$userinfo=qa_db_select_with_pending($db, qa_db_user_account_selectspec($userid, true));

		if (!qa_send_notification($db, $userid, $userinfo['email'], $userinfo['handle'], qa_lang('emails/reset_subject'), qa_lang('emails/reset_body'), array(
			'^code' => $userinfo['emailcode'],
			'^url' => qa_path('reset', array('c' => $userinfo['emailcode'], 'e' => $userinfo['email']), qa_get_option($db, 'site_url')),
		)))
			qa_fatal_error('Could not send reset password email');
	}

	
	function qa_complete_reset_user($db, $userid)
/*
	Successfully finish the 'I forgot my password' process for $userid, sending new password
*/
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
		qa_db_user_set($db, $userid, 'emailcode', ''); // so can't be reused
	}

	
	function qa_logged_in_user_flush()
/*
	Flush any information about the currently logged in user, so it is retrieved from database again
*/
	{
		global $qa_cached_logged_in_user;
		
		$qa_cached_logged_in_user=null;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/