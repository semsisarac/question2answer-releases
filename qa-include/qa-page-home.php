<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-home.php
	Version: 1.0-beta-2
	Date: 2010-03-08 13:08:01 GMT


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

//	Get list of questions and page size for appropriate version of page
	
	switch ($qa_template) {
		case 'questions':
			qa_options_set_pending(array('page_size_qs', 'voting_on_qs', 'votes_separated'));
			
			list($questions, $count)=qa_db_select_with_pending($qa_db,
				qa_db_recent_qs_selectspec($qa_login_userid, $qa_start),
				qa_db_options_cache_selectspec('cache_qcount')
			);

			$pagesize=qa_get_option($qa_db, 'page_size_qs');
			$pagetitle=qa_lang_html('main/recent_qs_title');
			$suggest=qa_html_suggest_ask();
			break;
			
		case 'answers':
			qa_options_set_pending(array('page_size_as', 'voting_on_qs', 'votes_separated'));
			
			list($questions, $count)=qa_db_select_with_pending($qa_db,
				qa_db_recent_a_qs_selectspec($qa_login_userid, $qa_start),
				qa_db_options_cache_selectspec('cache_acount')
			);

			$pagesize=qa_get_option($qa_db, 'page_size_as');
			$pagetitle=qa_lang_html('main/recent_as_title');
			$suggest=qa_html_suggest_ask();
			break;
		
		default:
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';

			qa_options_set_pending(array('page_size_home', 'voting_on_qs', 'votes_separated'));
		
			list($askedquestions, $answeredquestions)=qa_db_select_with_pending($qa_db,
				qa_db_recent_qs_selectspec($qa_login_userid, 0),
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0)
			);
			
			$pagesize=qa_get_option($qa_db, 'page_size_home');
			$pagetitle=qa_lang_html('main/recent_activity_title');

		//	Sort them to find most recent activity

			$questions=array_merge($askedquestions, $answeredquestions);
			foreach ($questions as $key => $question)
				$questions[$key]['sort']=-$question[isset($question['apostid']) ? 'acreated' : 'created'];
			
			qa_sort_by($questions, 'sort');
			$suggest=(count($questions)>=$pagesize) ? qa_html_suggest_qs_tags() : qa_html_suggest_ask();			
			break;
	}
	
//	Remove two copies of a question (e.g. if appeared for both recent Q and A, or for two answers)

	$keyseenq=array();
	foreach ($questions as $key => $question)
		if (isset($keyseenq[$question['postid']]))
			unset($questions[$key]);
		else
			$keyseenq[$question['postid']]=true;
	
//	Chop the list down to size

	$questions=array_slice($questions, 0, $pagesize);

//	Get user information for appropriate users

	$userids_handles=array();
	foreach ($questions as $question)
		$userids_handles[]=array(
			'userid' => $question[isset($question['apostid']) ? 'auserid' : 'userid'],
			'handle' => @$question[isset($question['apostid']) ? 'ahandle' : 'handle'],
		);
	
	$usershtml=qa_userids_handles_html($qa_db, $userids_handles);

//	Prepare content for theme
	
	qa_content_prepare(true);

	$qa_content['title']=$pagetitle;
	
	$qa_content['q_list']['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	);
	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions))
		foreach ($questions as $question)
			$qa_content['q_list']['qs'][]=isset($question['apostid'])
				? qa_a_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, qa_get_vote_view($qa_db, 'Q'), $question['apostid'], $question['acreated'], $question['auserid'], $question['acookieid'], $question['apoints'])
				: qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, qa_get_vote_view($qa_db, 'Q'));
	else
		$qa_content['title']=qa_lang_html('main/no_questions_found');
	
	if (isset($count))
		$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $count, qa_get_option($qa_db, 'pages_prev_next'));
	
	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=$suggest;
	
?>