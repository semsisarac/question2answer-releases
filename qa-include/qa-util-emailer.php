<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-emailer.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Wrapper for email sending function


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

	if (QA_EXTERNAL_EMAILER) {
	
		require_once QA_EXTERNAL_DIR.'qa-external-emailer.php';
	
	} else {
	
		function qa_send_email($params)
	/*
		Send the email based on the $params array - the following keys are required (some can be empty):
		fromemail, fromname, toemail, toname, subject, body, html
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-class.phpmailer.php';
		
			$mailer=new PHPMailer();
			$mailer->CharSet='utf-8';
			
			$mailer->From=$params['fromemail'];
			$mailer->Sender=$params['fromemail'];
			$mailer->FromName=$params['fromname'];
			$mailer->AddAddress($params['toemail'], $params['toname']);
			$mailer->Subject=$params['subject'];
			$mailer->Body=$params['body'];

			if ($params['html'])
				$mailer->IsHTML(true);
				
			return $mailer->Send();
		}
		
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/