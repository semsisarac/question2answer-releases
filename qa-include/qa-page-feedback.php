<?php
	
/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-feedback.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Controller for feedback page


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
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';


//	Queue required options and get useful information on the logged in user

	qa_options_set_pending(array('email_privacy', 'from_email', 'feedback_enabled', 'feedback_email', 'site_url', 'captcha_on_feedback'));
	qa_captcha_pending();
	
	if (isset($qa_login_userid) && !QA_EXTERNAL_USERS)
		list($useraccount, $userprofile)=qa_db_select_with_pending($qa_db,
			qa_db_user_account_selectspec($qa_login_userid, true),
			qa_db_user_profile_selectspec($qa_login_userid, true)
		);


//	Check feedback is enabled

	if (!qa_get_option($qa_db, 'feedback_enabled')) {
		qa_content_prepare();
		$qa_content['error']=qa_lang_html('main/page_not_found');
		return;
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
			$errors['message']=qa_lang('main/feedback_empty');
		
		if (qa_get_option($qa_db, 'captcha_on_feedback'))
			qa_captcha_validate($qa_db, $_POST, $errors);

		if (empty($errors)) {
			$subs=array(
				'^message' => $inmessage,
				'^name' => empty($inname) ? '-' : $inname,
				'^email' => empty($inemail) ? '-' : $inemail,
				'^previous' => empty($inreferer) ? '-' : $inreferer,
				'^url' => isset($qa_login_userid) ? qa_path('user/'.(QA_EXTERNAL_USERS ? $qa_login_user['publicusername'] : @$useraccount['handle']), null, qa_get_option($qa_db, 'site_url')) : '-',
				'^ip' => @$_SERVER['REMOTE_ADDR'],
				'^browser' => @$_SERVER['HTTP_USER_AGENT'],
			);
			
			if (qa_send_email(array(
				'fromemail' => qa_email_validate(@$inemail) ? $inemail : qa_get_option($qa_db, 'from_email'),
				'fromname' => $inname,
				'toemail' => qa_get_option($qa_db, 'feedback_email'),
				'toname' => qa_get_option($qa_db, 'site_title'),
				'subject' => qa_lang_sub('emails/feedback_subject', qa_get_option($qa_db, 'site_title')),
				'body' => strtr(qa_lang('emails/feedback_body'), $subs),
				'html' => false,
			)))
				$feedbacksent=true;
			else
				$page_error=qa_lang_html('main/general_error');
		}
	}
	
	
//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('main/feedback_title');
	
	$qa_content['error']=@$page_error;

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'tall',
		
		'fields' => array(
			'message' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_sub_html('main/feedback_message', qa_get_option($qa_db, 'site_title')),
				'tags' => ' NAME="message" ID="message" ',
				'value' => qa_html(@$inmessage),
				'rows' => 8,
				'error' => qa_html(@$errors['message']),
			),

			'name' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html('main/feedback_name'),
				'tags' => ' NAME="name" ',
				'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
			),

			'email' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html('main/feedback_email'),
				'tags' => ' NAME="email" ',
				'value' => qa_html(isset($inemail) ? $inemail : $qa_login_email),
				'note' => $feedbacksent ? null : qa_get_option($qa_db, 'email_privacy'),
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
	
	if (qa_get_option($qa_db, 'captcha_on_feedback') && !$feedbacksent)
		qa_set_up_captcha_field($qa_db, $qa_content, $qa_content['form']['fields'], @$errors);

	$qa_content['focusid']='message';
	
	if ($feedbacksent) {
		$qa_content['form']['ok']=qa_lang_html('main/feedback_sent');
		unset($qa_content['form']['buttons']);
	}
?>