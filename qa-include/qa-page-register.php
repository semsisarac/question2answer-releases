<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-register.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Controller for register page


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

	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-db-users.php';


//	Check we're not using single-sign on integration, that we're not logged in, and we're not blocked

	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User registration is handled by external code');
		
	if (isset($qa_login_userid))
		qa_redirect('');
	
	if (qa_user_permit_error($qa_db)) {
		qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return;
	}		

	
//	Queue options that we'll be needing

	qa_options_set_pending(array('email_privacy', 'captcha_on_register'));
	qa_captcha_pending();

	
//	Process submitted form

	if (qa_clicked('doregister')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inemail=qa_post_text('email');
		$inpassword=qa_post_text('password');
		$inhandle=qa_post_text('handle');
		
		$errors=array_merge(
			qa_handle_email_validate($qa_db, $inhandle, $inemail),
			qa_password_validate($inpassword)
		);
		
		if (qa_get_option($qa_db, 'captcha_on_register'))
			qa_captcha_validate($qa_db, $_POST, $errors);
	
		if (empty($errors)) { // register and redirect
			$userid=qa_create_new_user($qa_db, $inemail, $inpassword, $inhandle);
			qa_set_logged_in_user($qa_db, $userid, $inhandle);

			$topath=qa_get('to');
			
			if (isset($topath))
				qa_redirect_raw($topath); // path already provided as URL fragment
			else
				qa_redirect('');
		}
	}


//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/register_title');

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'tall',
		
		'fields' => array(
			'handle' => array(
				'label' => qa_lang_html('users/handle_label'),
				'tags' => ' NAME="handle" ID="handle" ',
				'value' => qa_html(@$inhandle),
				'error' => qa_html(@$errors['handle']),
			),
			
			'password' => array(
				'type' => 'password',
				'label' => qa_lang_html('users/password_label'),
				'tags' => ' NAME="password" ID="password" ',
				'value' => qa_html(@$inpassword),
				'error' => qa_html(@$errors['password']),
			),

			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'tags' => ' NAME="email" ID="email" ',
				'value' => qa_html(@$inemail),
				'note' => qa_get_option($qa_db, 'email_privacy'),
				'error' => qa_html(@$errors['email']),
			),
		),
		
		'buttons' => array(
			'register' => array(
				'label' => qa_lang_html('users/register_button'),
			),
		),
		
		'hidden' => array(
			'doregister' => '1',
		),
	);
	
	if (qa_get_option($qa_db, 'captcha_on_register'))
		qa_set_up_captcha_field($qa_db, $qa_content, $qa_content['form']['fields'], @$errors);
	
	$qa_content['focusid']=isset($errors['handle']) ? 'handle'
		: (isset($errors['password']) ? 'password'
			: (isset($errors['email']) ? 'email' : 'handle'));
	

/*
	Omit PHP closing tag to help avoid accidental output
*/