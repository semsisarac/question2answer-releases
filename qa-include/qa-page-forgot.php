<?php

/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-forgot.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Controller for 'forgot my password' page


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

	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';


//	Check we're not using single-sign on integration and that we're not logged in
	
	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');
		
	if (isset($qa_login_userid))
		qa_redirect('');


//	Queue appropriate options requests
	
	qa_captcha_pending();
	qa_options_set_pending(array('captcha_on_reset_password'));

	if (qa_clicked('doforgot')) {
		$inemailhandle=qa_post_text('emailhandle');
		
		$errors=array();
		
		if (strpos($inemailhandle, '@')===false) // handles can't contain @ symbols
			$matchusers=qa_db_user_find_by_handle($qa_db, $inemailhandle);
		else
			$matchusers=qa_db_user_find_by_email($qa_db, $inemailhandle);
			
		if (count($matchusers)!=1) // if we get more than one match (should be impossible) also give an error
			$errors['emailhandle']=qa_lang('users/user_not_found');

		if (qa_get_option($qa_db, 'captcha_on_reset_password'))
			qa_captcha_validate($qa_db, $_POST, $errors);

		if (empty($errors)) {
			$inuserid=$matchusers[0];
			qa_start_reset_user($qa_db, $inuserid);
			qa_redirect('reset', array('e' => $inemailhandle)); // redirect to page where code is entered
		}
			

	} else
		$inemailhandle=qa_get('e');

	
//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/reset_title');

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'tall',
		
		'fields' => array(
			'email_handle' => array(
				'label' => qa_lang_html('users/email_handle_label'),
				'tags' => ' NAME="emailhandle" ID="emailhandle" ',
				'value' => qa_html(@$inemailhandle),
				'error' => qa_html(@$errors['emailhandle']),
				'note' => qa_lang_html('users/send_reset_note'),
			),
		),
		
		'buttons' => array(
			'send' => array(
				'label' => qa_lang_html('users/send_reset_button'),
			),
		),
		
		'hidden' => array(
			'doforgot' => '1',
		),
	);
	
	if (qa_get_option($qa_db, 'captcha_on_reset_password'))
		qa_set_up_captcha_field($qa_db, $qa_content, $qa_content['form']['fields'], @$errors);
	
	$qa_content['focusid']='emailhandle';


/*
	Omit PHP closing tag to help avoid accidental output
*/