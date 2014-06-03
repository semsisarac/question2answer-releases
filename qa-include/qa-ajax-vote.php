<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-vote.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Server-side response to Ajax voting requests


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

	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-votes.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	

	function qa_ajax_vote_db_fail_handler()
	{
		echo "QA_AJAX_RESPONSE\n0\nA database error occurred.";
		exit;
	}

	qa_base_db_connect('qa_ajax_vote_db_fail_handler');

	$postid=qa_post_text('postid');
	$qa_login_userid=qa_get_logged_in_userid($qa_db);
	$qa_cookieid=qa_cookie_get();

	$voteerror=qa_user_vote_error($qa_db, $qa_login_userid, $postid, qa_post_text('vote'), $qa_request);

	if ($voteerror===false) {
		qa_options_set_pending(array('site_theme', 'site_language', 'votes_separated'));
	
		$post=qa_db_select_with_pending($qa_db,
			qa_db_full_post_selectspec($qa_login_userid, $postid)
		);
		
		$fields=qa_post_html_fields($post, $qa_login_userid, $qa_cookieid, array(), false, null, qa_get_option($qa_db, 'votes_separated') ? 'updown' : 'net');
		
		$themeclass=qa_load_theme_class(qa_get_option($qa_db, 'site_theme'), 'voting', null, null);

		echo "QA_AJAX_RESPONSE\n1\n";
		$themeclass->voting_inner_html($fields);

	} else
		echo "QA_AJAX_RESPONSE\n0\n".$voteerror;

	qa_base_db_disconnect();
	

/*
	Omit PHP closing tag to help avoid accidental output
*/