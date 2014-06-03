<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-recalc.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Server-side response to Ajax recalculation requests


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

//	Base loading stuff - use double dirname() so this works with symbolic links for multiple installations

	define('QA_BASE_DIR', dirname(dirname(empty($_SERVER['SCRIPT_FILENAME']) ? __FILE__ : $_SERVER['SCRIPT_FILENAME'])).'/');

	error_reporting(0);

	require 'qa-base.php';

	$qa_root_url_relative=qa_post_text('qa_root');
	$qa_request=qa_post_text('qa_request');
	
	function qa_db_fail_handler()
	{
		echo "0\n\nA database error occurred.";
		exit;
	}


//	Main code

	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-recalc.php';
	
	qa_base_db_connect('qa_db_fail_handler');
	$qa_login_user=qa_get_logged_in_user($qa_db);
	
	if ($qa_login_user['level']>=QA_USER_LEVEL_ADMIN) {
		$state=qa_post_text('state');
		$stoptime=time()+3;
		
		while ( qa_recalc_perform_step($qa_db, $state) && (time()<$stoptime) )
			;
			
		$message=qa_recalc_get_message($state);
	
	} else {
		$state='';
		$message=qa_lang('admin/no_privileges');
	}
	
	qa_base_db_disconnect();
	
	header('Content-Type: text/plain');
	
	echo "1\n".$state."\n".qa_html($message);
?>