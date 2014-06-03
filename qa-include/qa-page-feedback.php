<?php
	
/*
	Question2Answer 1.3.2 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-feedback.php
	Version: 1.3.2
	Date: 2011-03-14 09:01:08 GMT
	Description: Controller for feedback page


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';


//	Get useful information on the logged in user

	if (isset($qa_login_userid) && !QA_EXTERNAL_USERS)
		list($useraccount, $userprofile)=qa_db_select_with_pending(
			qa_db_user_account_selectspec($qa_login_userid, true),
			qa_db_user_profile_selectspec($qa_login_userid, true)
		);

	$usecaptcha=qa_user_use_captcha('captcha_on_feedback');


//	Check feedback is enabled

	if (!qa_opt('feedback_enabled')) {
		header('HTTP/1.0 404 Not Found');
		$qa_template='not-found';
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('main/page_not_found');
		return $qa_content;
	}


//	Send the feedback form
	
	$feedbacksent=false;
	
	if (qa_clicked('dofeedback')) {
		require_once QA_INCLUDE_DIR.'qa-util-emailer.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$inmessage=qa_post_text('message');
		$inname=qa_post_text('name');
		$inemail=qa_post_text('email');
		$inreferer=qa_post_text('referer');
		
		if (empty($inmessage))
			$errors['message']=qa_lang('misc/feedback_empty');
		
		if ($usecaptcha)
			qa_captcha_validate($_POST, $errors);

		if (empty($errors)) {
			$subs=array(
				'^message' => $inmessage,
				'^name' => empty($inname) ? '-' : $inname,
				'^email' => empty($inemail) ? '-' : $inemail,
				'^previous' => empty($inreferer) ? '-' : $inreferer,
				'^url' => isset($qa_login_userid) ? qa_path('user/'.qa_get_logged_in_handle(), null, qa_opt('site_url')) : '-',
				'^ip' => @$_SERVER['REMOTE_ADDR'],
				'^browser' => @$_SERVER['HTTP_USER_AGENT'],
			);
			
			if (qa_send_email(array(
				'fromemail' => qa_email_validate(@$inemail) ? $inemail : qa_opt('from_email'),
				'fromname' => $inname,
				'toemail' => qa_opt('feedback_email'),
				'toname' => qa_opt('site_title'),
				'subject' => qa_lang_sub('emails/feedback_subject', qa_opt('site_title')),
				'body' => strtr(qa_lang('emails/feedback_body'), $subs),
				'html' => false,
			)))
				$feedbacksent=true;
			else
				$page_error=qa_lang_html('main/general_error');
		}
	}
	
	
//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('misc/feedback_title');
	
	$qa_content['error']=@$page_error;

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'tall',
		
		'fields' => array(
			'message' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html_sub('misc/feedback_message', qa_opt('site_title')),
				'tags' => ' NAME="message" ID="message" ',
				'value' => qa_html(@$inmessage),
				'rows' => 8,
				'error' => qa_html(@$errors['message']),
			),

			'name' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html('misc/feedback_name'),
				'tags' => ' NAME="name" ',
				'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
			),

			'email' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html('misc/feedback_email'),
				'tags' => ' NAME="email" ',
				'value' => qa_html(isset($inemail) ? $inemail : qa_get_logged_in_email()),
				'note' => $feedbacksent ? null : qa_opt('email_privacy'),
			),
		),
		
		'buttons' => array(
			'send' => array(
				'label' => qa_lang_html('main/send_button'),
			),
		),
		
		'hidden' => array(
			'dofeedback' => '1',
			'referer' => qa_html(isset($inreferer) ? $inreferer : @$_SERVER['HTTP_REFERER']),
		),
	);
	
	if ($usecaptcha && !$feedbacksent)
		qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors);


	$qa_content['focusid']='message';
	
	if ($feedbacksent) {
		$qa_content['form']['ok']=qa_lang_html('misc/feedback_sent');
		unset($qa_content['form']['buttons']);
	}

	
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/