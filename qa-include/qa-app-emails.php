<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-emails.php
	Version: 1.0-beta-3
	Date: 2010-03-31 12:13:41 GMT


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

	require_once QA_INCLUDE_DIR.'qa-app-options.php';

	function qa_notification_pending()
	{
		qa_options_set_pending(array('from_email', 'site_title'));
	}
	
	function qa_send_notification($db, $userid, $email, $handle, $subject, $body, $subs)
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
	
?>