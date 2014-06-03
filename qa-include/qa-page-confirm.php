<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-confirm.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Controller for email confirmation page (can also request a new code)


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


//	Check we're not using single-sign on integration, that we're not already confirmed, and that we're not blocked
	
	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');
		
	if (qa_user_permit_error($qa_db)) {
		qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return;
	}


//	Check if we've been asked to send a new link or have a successful email confirmation

	$incode=trim(qa_get('c')); // trim to prevent passing in blank values to match uninitiated DB rows
	$inhandle=qa_get('u');
	$useremailed=false;
	$userconfirmed=false;
	
	if (isset($qa_login_userid) && qa_clicked('dosendconfirm')) { // button clicked to send a link
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		qa_send_new_confirm($qa_db, $qa_login_userid);
		$useremailed=true;
	
	} elseif (strlen($incode)) { // non-empty code detected from the URL
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
	
		if (!empty($inhandle)) { // match based on code and handle provided on URL
			$userinfo=qa_db_select_with_pending($qa_db, qa_db_user_account_selectspec($inhandle, false));
	
			if (strtolower(trim(@$userinfo['emailcode']))==strtolower($incode)) {
				qa_complete_confirm($qa_db, $userinfo['userid']);
				$userconfirmed=true;
			}
		}
		
		if ((!$userconfirmed) && isset($qa_login_userid)) { // as a backup, also match code on URL against logged in user
			$userinfo=qa_db_select_with_pending($qa_db, qa_db_user_account_selectspec($qa_login_userid, true));
			
			if ($userinfo['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) // if they confirmed before, just show message as if it happened now
				$userconfirmed=true;
			
			elseif (strtolower(trim($userinfo['emailcode']))==strtolower($incode)) {
				qa_complete_confirm($qa_db, $qa_login_userid);
				$userconfirmed=true;
			}
		}
	}


//	Prepare content for theme
	
	qa_content_prepare();
	
	$qa_content['title']=qa_lang_html('users/confirm_title');

	if ($useremailed)
		$qa_content['error']=qa_lang_html('users/confirm_emailed'); // not an error, but display it prominently anyway
	
	elseif ($userconfirmed) {
		$qa_content['error']=qa_lang_html('users/confirm_complete');
		
		if (!isset($qa_login_userid))
			$qa_content['suggest_next']=strtr(
				qa_lang_html('users/log_in_to_access'),
				
				array(
					'^1' => '<A HREF="'.qa_path_html('login', array('e' => $inhandle)).'">',
					'^2' => '</A>',
				)
			);

		elseif ($qa_login_userid == $userinfo['userid'])
			$qa_content['suggest_next']=strtr(
				qa_lang_html('users/view_account_page'),
				
				array(
					'^1' => '<A HREF="'.qa_path_html('account').'">',
					'^2' => '</A>',
				)
			);

	} elseif (isset($qa_login_userid)) { // if logged in, allow sending a fresh link
		if (strlen($incode))
			$qa_content['error']=qa_lang_html('users/confirm_wrong_resend');

		$qa_content['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_path_html('confirm').'" ',
			
			'style' => 'tall',
			
			'fields' => array(
				'email' => array(
					'label' => qa_lang_html('users/email_label'),
					'value' => qa_html(qa_get_logged_in_email($qa_db)),
					'type' => 'static',
				),
			),
			
			'buttons' => array(
				'send' => array(
					'tags' => ' NAME="dosendconfirm" ',
					'label' => qa_lang_html('users/send_confirm_button'),
				),
			),
		);

	} else
		$qa_content['error']=qa_insert_login_links(qa_lang_html('users/confirm_wrong_log_in'), 'confirm');


/*
	Omit PHP closing tag to help avoid accidental output
*/