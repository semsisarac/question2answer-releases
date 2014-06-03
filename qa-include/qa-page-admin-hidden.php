<?php
	
/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-hidden.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Controller for admin page showing hidden questions, answers and comments


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

	
//	Queue requests for pending admin options

	qa_admin_pending();
	
	qa_options_set_pending(array('voting_on_qs', 'votes_separated', 'show_when_created', 'show_user_points', 'block_bad_words'));


//	Find recently hidden questions, answers, comments

	list($hiddenquestions, $hiddenanswers, $hiddencomments, $categories)=qa_db_select_with_pending($qa_db,
		qa_db_recent_qs_selectspec($qa_login_userid, 0, null, null, true),
		qa_db_recent_a_qs_selectspec($qa_login_userid, 0, null, null, true),
		qa_db_recent_c_qs_selectspec($qa_login_userid, 0, null, null, true),
		qa_db_categories_selectspec()
	);
	
	
//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges())
		return;
		
		
//	Combine sets of questions and get information for users

	$questions=qa_any_sort_and_dedupe(array_merge($hiddenquestions, $hiddenanswers, $hiddencomments));
	
	$usershtml=qa_userids_handles_html($qa_db, qa_any_get_userids_handles($questions));


//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/recent_hidden_title');
	
	$qa_content['error']=qa_admin_page_error($qa_db);
	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		foreach ($questions as $question) {
			$htmlfields=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				false, qa_using_categories($qa_db) ? $categories : null, false,
				qa_get_option($qa_db, 'show_when_created'), true, qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
			
			unset($htmlfields['answers']); // show less info than usual

			if (isset($htmlfields['what_url'])) // link directly to relevant content
				$htmlfields['url']=$htmlfields['what_url'];

			$qa_content['q_list']['qs'][]=$htmlfields;
		}

	} else
		$qa_content['title']=qa_lang_html('admin/no_hidden_found');
		
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();
	

/*
	Omit PHP closing tag to help avoid accidental output
*/