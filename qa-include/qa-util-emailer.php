<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-emailer.php
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

	if (QA_EXTERNAL_EMAILER) {
	
		require_once QA_EXTERNAL_DIR.'qa-external-emailer.php';
	
	} else {
	
		function qa_send_email($params)
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

?>