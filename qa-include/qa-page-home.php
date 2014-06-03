<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-home.php
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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

//	Get list of questions and page size for appropriate version of page

	qa_options_set_pending(array('voting_on_qs', 'votes_separated', 'show_user_points'));
	
	switch ($qa_template) {
		case 'questions':
			qa_options_set_pending(array('page_size_qs'));
			
			list($questions, $count)=qa_db_select_with_pending($qa_db,
				qa_db_recent_qs_selectspec($qa_login_userid, $qa_start),
				qa_db_options_cache_selectspec('cache_qcount')
			);

			$pagesize=qa_get_option($qa_db, 'page_size_qs');
			$sometitle=qa_lang_html('main/recent_qs_title');
			$nonetitle=qa_lang_html('main/no_questions_found');
			$suggest=qa_html_suggest_ask();
			break;

		case 'unanswered':
			qa_options_set_pending(array('page_size_una_qs'));
			
			list($questions, $count)=qa_db_select_with_pending($qa_db,
				qa_db_unanswered_qs_selectspec($qa_login_userid, $qa_start),
				qa_db_options_cache_selectspec('cache_unaqcount')
			);

			$pagesize=qa_get_option($qa_db, 'page_size_una_qs');
			$sometitle=qa_lang_html('main/unanswered_qs_title');
			$nonetitle=qa_lang_html('main/no_una_questions_found');
			$suggest=qa_html_suggest_qs_tags();
			break;
			
		case 'answers': // not currently in navigation
			$questions=qa_db_select_with_pending($qa_db,
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0)
			);

			$sometitle=qa_lang_html('main/recent_as_title');
			$nonetitle=qa_lang_html('main/no_answers_found');
			$suggest=qa_html_suggest_qs_tags();
			break;
			
		case 'comments': // not currently in navigation
			$questions=qa_db_select_with_pending($qa_db,
				qa_db_recent_c_qs_selectspec($qa_login_userid, 0)
			);
			
			$sometitle=qa_lang_html('main/recent_cs_title');
			$nonetitle=qa_lang_html('main/no_comments_found');
			$suggest=qa_html_suggest_qs_tags();
			break;
		
		default:
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';

			qa_options_set_pending(array('page_size_home'));
		
			list($askedquestions, $answeredquestions)=qa_db_select_with_pending($qa_db,
				qa_db_recent_qs_selectspec($qa_login_userid, 0),
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0)
			);
			
			$questions=array_merge($askedquestions, $answeredquestions);

			$pagesize=qa_get_option($qa_db, 'page_size_home');
			$sometitle=qa_lang_html('main/recent_qs_as_title');
			$nonetitle=qa_lang_html('main/no_questions_found');
			$suggest=(count($questions)>=$pagesize) ? qa_html_suggest_qs_tags() : qa_html_suggest_ask();			
			break;
	}
	
//	Sort and remove any question referenced twice, chop down to size, get user information

	$questions=qa_any_sort_and_dedupe($questions);
	
	if (isset($pagesize))
		$questions=array_slice($questions, 0, $pagesize);

	$usershtml=qa_userids_handles_html($qa_db, qa_any_get_userids_handles($questions));

//	Prepare content for theme
	
	qa_content_prepare(true);

	$qa_content['q_list']['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	);
	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		$qa_content['title']=$sometitle;
	
		foreach ($questions as $question)
			$qa_content['q_list']['qs'][]=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_get_vote_view($qa_db, 'Q'), qa_get_option($qa_db, 'show_user_points'));
	
	} else
		$qa_content['title']=$nonetitle;
	
	if (isset($count) && isset($pagesize))
		$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $count, qa_get_option($qa_db, 'pages_prev_next'));
	
	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=$suggest;
	
?>