<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-external-example/qa-external-emailer.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Example of how to use your own email sending function


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

/*
	==============================================================
	THIS FILE ALLOWS YOU TO DEFINE YOUR OWN EMAIL SENDING FUNCTION
	==============================================================

	It is used if QA_EXTERNAL_EMAILER is set to true in qa-config.php.
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_send_email($params)
/*
	This is your custom email sending function - $params is an array with the elements below.
	Return true if delivery (or at least queueing) was successful, false if not.
	
	'fromemail' => email of sender (should also be used for Return-Path)
	'fromname' => name of sender (should also be used for Return-Path)
	'toemail' => email of 'to' recipient
	'toname' => name of 'to' recipient
	'subject' => subject line of message (in UTF-8)
	'body' => body text of message (in UTF-8)
	'html' => true if body is HTML, false if body is plain text
	
	For an example that uses the PHPMailer library, see qa-include/qa-util-emailer.php.
*/
	{
		return false;
	}
	
?>
