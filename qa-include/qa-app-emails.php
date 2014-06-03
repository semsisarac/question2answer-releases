<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-emails.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Wrapper functions for sending email notifications to users


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

	require_once QA_INCLUDE_DIR.'qa-app-options.php';


	function qa_notification_pending()
/*
	Queue option requests required for qa_send_notification()
*/
	{
		qa_options_set_pending(array('from_email', 'site_title'));
	}
	

	function qa_send_notification($db, $userid, $email, $handle, $subject, $body, $subs)
/*
	Send email to person with $userid and/or $email and/or $handle (null/invalid values
	are ignored or retrieved from user database as appropriate). Email uses $subject
	and $body, after substituting each key in $subs with its corresponding value.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-util-emailer.php';
		
		qa_notification_pending();
		
		if (isset($userid)) {
			$needemail=!qa_email_validate(@$email); // take from user if invalid, e.g. @ used in practice
			$needhandle=empty($handle);
			
			if ($needemail || $needhandle) {
				if (QA_EXTERNAL_USERS) {
					if ($needhandle) {
						$handles=qa_get_public_from_userids($db, array($userid));
						$handle=@$handles[$userid];
					}
					
					if ($needemail)
						$email=qa_get_user_email($db, $userid);
				
				} else {
					$useraccount=qa_db_select_with_pending($db,
						qa_db_user_account_selectspec($userid, true)
					);
					
					if ($needhandle)
						$handle=@$useraccount['handle'];
	
					if ($needemail)
						$email=@$useraccount['email'];
				}
			}
		}
			
		if (isset($email) && qa_email_validate($email)) {
			$subs['^site_title']=qa_get_option($db, 'site_title');
			$subs['^handle']=$handle;
			$subs['^email']=$email;
			$subs['^open']="\n- - - - -\n\n";
			$subs['^close']="\n\n- - - - -\n";
		
			return qa_send_email(array(
				'fromemail' => qa_get_option($db, 'from_email'),
				'fromname' => qa_get_option($db, 'site_title'),
				'toemail' => $email,
				'toname' => $handle,
				'subject' => strtr($subject, $subs),
				'body' => (empty($handle) ? '' : $handle.",\n\n").strtr($body, $subs),
				'html' => false,
			));
		
		} else
			return false;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/