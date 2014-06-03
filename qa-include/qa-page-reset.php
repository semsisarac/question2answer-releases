<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-reset.php
	Version: 1.0-beta-1
	Date: 2010-02-04 14:10:15 GMT


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
		qa_fatal_error(qa_lang('users/already_logged_in'));

	if (qa_clicked('doreset')) {
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
	
		$inemailhandle=qa_post_text('emailhandle');
		$incode=qa_post_text('code');
		
		$errors=array();
		
		if (strpos($inemailhandle, '@')===false)
			$matchusers=qa_db_user_find_by_handle($qa_db, $inemailhandle);
		else
			$matchusers=qa_db_user_find_by_email($qa_db, $inemailhandle);

		if (count($matchusers)==1) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';

			$inuserid=$matchusers[0];
			$userinfo=qa_db_select_with_pending($qa_db, qa_db_user_account_selectspec($inuserid, true));
			
			// strlen() check is vital otherwise we can reset code for most users by entering the empty string
			if (strlen($incode) && (strtolower($userinfo['resetcode']) == strtolower($incode))) {
				qa_complete_reset_user($qa_db, $inuserid);
				qa_redirect('login', array('e' => $inemailhandle, 'ps' => '1'));
	
			} else
				$errors['code']=qa_lang('users/reset_code_wrong');
			
		} else
			$errors['emailhandle']=qa_lang('users/user_not_found');

	} else {
		$inemailhandle=qa_get('e');
		$incode=qa_get('c');
	}
	
//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/reset_title');

	if (empty($inemailhandle) || isset($errors['emailhandle']))
		$forgotpath=qa_path('forgot');
	else
		$forgotpath=qa_path('forgot',  array('e' => $inemailhandle));
	
	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'tall',
		
		'ok' => qa_lang_html('users/reset_code_emailed'),
		
		'fields' => array(
			'email_handle' => array(
				'label' => qa_lang_html('users/email_handle_label'),
				'tags' => ' NAME="emailhandle" ID="emailhandle" ',
				'value' => qa_html(@$inemailhandle),
				'error' => qa_html(@$errors['emailhandle']),
			),

			'code' => array(
				'label' => qa_lang_html('users/reset_code_label'),
				'tags' => ' NAME="code" ID="code" ',
				'value' => qa_html(@$incode),
				'error' => qa_html(@$errors['code']),
				'note' => qa_lang_html('users/reset_code_emailed').' - '.
					'<A HREF="'.qa_html($forgotpath).'">'.qa_lang_html('users/reset_code_another').'</A>',
			),
		),
		
		'buttons' => array(
			'reset' => array(
				'label' => qa_lang_html('users/send_password_button'),
			),
		),
		
		'hidden' => array(
			'doreset' => '1',
		),
	);
	
	$qa_content['focusid']=isset($errors['emailhandle']) ? 'emailhandle' : 'code';

?>