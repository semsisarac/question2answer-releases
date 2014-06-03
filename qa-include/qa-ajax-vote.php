<?php

/*
	Question2Answer 1.0.1-beta (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-vote.php
	Version: 1.0.1-beta
	Date: 2010-05-11 12:36:30 GMT
	Description: Server-side response to Ajax voting requests


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


//	Main code

	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-votes.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	
	$qa_root_url_relative=qa_post_text('qa_root');
	$qa_request=qa_post_text('qa_request');
	$postid=qa_post_text('postid');
	
	function qa_db_fail_handler()
	{
		echo "0\nA database error occurred.";
		exit;
	}

	qa_base_db_connect('qa_db_fail_handler');

	$qa_login_user=qa_get_logged_in_user($qa_db);
	$qa_login_userid=@$qa_login_user['userid'];
	$qa_cookieid=qa_cookie_get();

	$voteerror=qa_user_vote_error($qa_db, $qa_login_userid, $postid, qa_post_text('vote'), $qa_request);

	header("Content-Type: text/plain");
	
	if ($voteerror===false) {
		qa_options_set_pending(array('site_theme', 'site_language', 'votes_separated'));
	
		$post=qa_db_select_with_pending($qa_db,
			qa_db_full_post_selectspec($qa_login_userid, $postid)
		);
		
		$fields=qa_post_html_fields($post, $qa_login_userid, $qa_cookieid, array(), qa_get_option($db, 'votes_separated') ? 'updown' : 'net');
		
		$themeclass=qa_load_theme_class(qa_get_option($qa_db, 'site_theme'), 'voting', null);

		echo "1\n";
		$themeclass->voting_inner_html($fields);

	} else
		echo "0\n".$voteerror;

	qa_base_db_disconnect();
	
?>