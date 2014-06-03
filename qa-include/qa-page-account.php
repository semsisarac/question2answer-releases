<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-account.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for user account page


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

	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	
	
//	Check we're not using single-sign on integration, that we're logged in, and we're not blocked
	
	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
		
	if (!isset($qa_login_userid))
		qa_redirect('login');
		
	if (qa_user_permit_error($qa_db)) {
		qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return;
	}

	
//	Get current information on user

	qa_options_set_pending(array('confirm_user_emails'));

	list($useraccount, $userprofile)=qa_db_select_with_pending($qa_db,
		qa_db_user_account_selectspec($qa_login_userid, true),
		qa_db_user_profile_selectspec($qa_login_userid, true)
	);
	
	$doconfirms=qa_get_option($qa_db, 'confirm_user_emails') && ($useraccount['level']<QA_USER_LEVEL_EXPERT);
	$isconfirmed=($useraccount['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;

	
//	Process profile if saved

	if (qa_clicked('dosaveprofile')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inhandle=qa_post_text('handle');
		$inemail=qa_post_text('email');
		$inname=qa_post_text('name');
		$inlocation=qa_post_text('location');
		$inwebsite=qa_post_text('website');
		$inabout=qa_post_text('about');
		
		$errors=array_merge(
			qa_handle_email_validate($qa_db, $inhandle, $inemail, $qa_login_userid),
			qa_profile_fields_validate($qa_db, $inname, $inlocation, $inwebsite, $inabout)
		);

		if (!isset($errors['handle']))
			qa_db_user_set($qa_db, $qa_login_userid, 'handle', $inhandle);

		if (!isset($errors['email']))
			if ($inemail != $useraccount['email']) {
				qa_db_user_set($qa_db, $qa_login_userid, 'email', $inemail);
				qa_db_user_set_flag($qa_db, $qa_login_userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed=false;
				
				if ($doconfirms)
					qa_send_new_confirm($qa_db, $qa_login_userid);
			}
		
		if (!isset($errors['name']))
			qa_db_user_profile_set($qa_db, $qa_login_userid, 'name', $inname);

		if (!isset($errors['location']))
			qa_db_user_profile_set($qa_db, $qa_login_userid, 'location', $inlocation);

		if (!isset($errors['website']))
			qa_db_user_profile_set($qa_db, $qa_login_userid, 'website', $inwebsite);

		if (!isset($errors['about']))
			qa_db_user_profile_set($qa_db, $qa_login_userid, 'about', $inabout);

		list($useraccount, $userprofile)=qa_db_select_with_pending($qa_db,
			qa_db_user_account_selectspec($qa_login_userid, true),
			qa_db_user_profile_selectspec($qa_login_userid, true)
		);

		qa_logged_in_user_flush();
	}


//	Process change password if clicked

	if (qa_clicked('dochangepassword')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inoldpassword=qa_post_text('oldpassword');
		$innewpassword1=qa_post_text('newpassword1');
		$innewpassword2=qa_post_text('newpassword2');
		
		$errors=array();
		
		if (strtolower(qa_db_calc_passcheck($inoldpassword, $useraccount['passsalt'])) != strtolower($useraccount['passcheck']))
			$errors['oldpassword']=qa_lang_html('users/password_wrong');

		$errors=array_merge($errors, qa_password_validate($innewpassword1));

		if ($innewpassword1 != $innewpassword2)
			$errors['newpassword2']=qa_lang_html('users/password_mismatch');
			
		if (empty($errors)) {
			qa_db_user_set_password($qa_db, $qa_login_userid, $innewpassword1);
			unset($inoldpassword);
		}
	}


//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('profile/my_account_title');

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'wide',
		
		'fields' => array(
			'duration' => array(
				'type' => 'static',
				'label' => qa_lang_html('users/member_for'),
				'value' => qa_time_to_string(time()-$useraccount['created']),
			),
			
			'type' => array(
				'type' => 'static',
				'label' => qa_lang_html('users/member_type'),
				'value' => qa_html(qa_user_level_string($useraccount['level'])),
			),
			
			'handle' => array(
				'label' => qa_lang_html('users/handle_label'),
				'tags' => ' NAME="handle" ',
				'value' => qa_html(isset($inhandle) ? $inhandle : $useraccount['handle']),
				'error' => qa_html(@$errors['handle']),
			),
			
			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'tags' => ' NAME="email" ',
				'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
				'error' => isset($errors['email']) ? qa_html($errors['email']) :
					(($doconfirms && !$isconfirmed) ? qa_insert_login_links(qa_lang_html('users/email_please_confirm')) : null),
			),
		
			'name' => array(
				'label' => qa_lang_html('users/full_name'),
				'tags' => ' NAME="name" ',
				'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
				'error' => qa_html(@$errors['name']),
			),
		
			'location' => array(
				'label' => qa_lang_html('users/location'),
				'tags' => ' NAME="location" ',
				'value' => qa_html(isset($inlocation) ? $inlocation : @$userprofile['location']),
				'error' => qa_html(@$errors['location']),
			),

			'website' => array(
				'label' => qa_lang_html('users/website'),
				'tags' => ' NAME="website" ',
				'value' => qa_html(isset($inwebsite) ? $inwebsite : @$userprofile['website']),
				'error' => qa_html(@$errors['website']),
			),

			'about' => array(
				'label' => qa_lang_html('users/about'),
				'tags' => ' NAME="about" ',
				'value' => qa_html(isset($inabout) ? $inabout : @$userprofile['about']),
				'error' => qa_html(@$errors['about']),
				'rows' => 8,
			),
		),
		
		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html('users/save_profile'),
			),
		),
		
		'hidden' => array(
			'dosaveprofile' => '1'
		),
	);
	
	if (qa_clicked('dosaveprofile') && empty($errors))
		$qa_content['form']['ok']=qa_lang_html('users/profile_saved');

	
	$qa_content['form_2']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'wide',
		
		'title' => qa_lang_html('users/change_password'),
		
		'fields' => array(
			'old' => array(
				'label' => qa_lang_html('users/old_password'),
				'tags' => ' NAME="oldpassword" ',
				'value' => qa_html(@$inoldpassword),
				'type' => 'password',
				'error' => @$errors['oldpassword'],
			),
		
			'new_1' => array(
				'label' => qa_lang_html('users/new_password_1'),
				'tags' => ' NAME="newpassword1" ',
				'type' => 'password',
				'error' => @$errors['password'],
			),

			'new_2' => array(
				'label' => qa_lang_html('users/new_password_2'),
				'tags' => ' NAME="newpassword2" ',
				'type' => 'password',
				'error' => @$errors['newpassword2'],
			),
		),
		
		'buttons' => array(
			'change' => array(
				'label' => qa_lang_html('users/change_password'),
			),
		),
		
		'hidden' => array(
			'dochangepassword' => '1',
		),
	);
	
	if (qa_clicked('dochangepassword') && empty($errors))
		$qa_content['form']['ok']=qa_lang_html('users/password_changed');
	

/*
	Omit PHP closing tag to help avoid accidental output
*/