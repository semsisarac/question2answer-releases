<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-login.php
	Version: 1.0-beta-2
	Date: 2010-03-08 13:08:01 GMT


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

	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');
		
	if (isset($qa_login_userid))
		qa_redirect('');
	
	if (qa_clicked('dologin')) {
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	
		$inemailhandle=qa_post_text('emailhandle');
		$inpassword=qa_post_text('password');
		
		$errors=array();
		
		if (strpos($inemailhandle, '@')===false)
			$matchusers=qa_db_user_find_by_handle($qa_db, $inemailhandle);
		else
			$matchusers=qa_db_user_find_by_email($qa_db, $inemailhandle);

		if (count($matchusers)==1) {
			$inuserid=$matchusers[0];
			$userinfo=qa_db_select_with_pending($qa_db, qa_db_user_account_selectspec($inuserid, true));
			
			if (strtolower(qa_db_calc_passcheck($inpassword, $userinfo['passsalt'])) == strtolower($userinfo['passcheck'])) {
				require_once QA_INCLUDE_DIR.'qa-app-users.php';

				qa_set_logged_in_user($qa_db, $inuserid);
				qa_db_user_logged_in($qa_db, $inuserid, $_SERVER['REMOTE_ADDR']);
				
				$topath=qa_get('to');
				
				if (isset($topath))
					qa_redirect($topath, null, null, true); // index.php already included if appropriate
				else
					qa_redirect('');

			} else
				$errors['password']=qa_lang('users/password_wrong');

		} else
			$errors['emailhandle']=qa_lang('users/user_not_found');

	} else
		$inemailhandle=qa_get('e');
		
	$passwordsent=qa_get('ps');
	
//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/login_title');

	if (empty($inemailhandle) || isset($errors['emailhandle']))
		$forgotpath=qa_path('forgot');
	else
		$forgotpath=qa_path('forgot', array('e' => $inemailhandle));
	
	$forgothtml='<A HREF="'.qa_html($forgotpath).'">'.qa_lang_html('users/forgot_link').'</A>';
	
	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'tall',
		
		'ok' => $passwordsent ? qa_lang_html('users/password_sent') : null,
		
		'fields' => array(
			'email_handle' => array(
				'label' => qa_lang_html('users/email_handle_label'),
				'tags' => ' NAME="emailhandle" ID="emailhandle" ',
				'value' => qa_html(@$inemailhandle),
				'error' => qa_html(@$errors['emailhandle']),
			),
			
			'password' => array(
				'type' => 'password',
				'label' => qa_lang_html('users/password_label'),
				'tags' => ' NAME="password" ID="password" ',
				'value' => qa_html(@$inpassword),
				'error' => empty($errors['password']) ? '' : (qa_html(@$errors['password']).' - '.$forgothtml),
				'note' => $passwordsent ? qa_lang_html('users/password_sent') : $forgothtml,
			),
		),
		
		'buttons' => array(
			'login' => array(
				'label' => qa_lang_html('users/login_button'),
			),
		),
		
		'hidden' => array(
			'dologin' => '1',
		),
	);
	
	$qa_content['focusid']=(isset($inemailhandle) && !isset($errors['emailhandle'])) ? 'password' : 'emailhandle';

?>