<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-register.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Controller for register page


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

	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-db-users.php';


//	Check we're not using single-sign on integration and that we're not logged in

	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User registration is handled by external code');
		
	if (isset($qa_login_userid))
		qa_redirect('');
	
	
//	Queue options that we'll be needing

	qa_options_set_pending(array('email_privacy', 'captcha_on_register'));
	qa_captcha_pending();

	
//	Process submitted form

	if (qa_clicked('doregister')) {
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
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			$userid=qa_create_new_user($qa_db, $inemail, $inpassword, $inhandle);
			qa_set_logged_in_user($qa_db, $userid);

			$topath=qa_get('to');
			
			if (isset($topath))
				qa_redirect($topath, null, null, true); // set $neaturls to true since index.php is already included in $topath if neat urls off
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
	
?>