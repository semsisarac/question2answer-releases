<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-search.php
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

	require_once QA_INCLUDE_DIR.'qa-app-format.php';

//	Perform the search if appropriate

	if (strlen(qa_get('q'))) {
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';

		$inquery=qa_get('q');
		$words=qa_string_to_words($inquery);
		$retrieve=2*QA_DB_RETRIEVE_QS_AS+1;
		
		qa_options_set_pending(array('page_size_search', 'voting_on_qs', 'votes_separated', 'show_user_points'));
		
		$questions=qa_db_select_with_pending($qa_db,
			qa_db_search_posts_selectspec($qa_db, $qa_login_userid, $words, $words, $words, $words, $qa_start, $retrieve)
		);
		
		$pagesize=qa_get_option($qa_db, 'page_size_search');
		$gotcount=count($questions);
		$questions=array_slice($questions, 0, $pagesize);
		$usershtml=qa_userids_handles_html($qa_db, $questions);
	}
	
//	Prepare content for theme

	qa_content_prepare(true);

	if (strlen(qa_get('q')))
		$qa_content['search']['value']=qa_html($inquery);
	
	if (isset($questions)) {
		if (count($questions))
			$qa_content['title']=qa_lang_sub_html('main/results_for_x', qa_html($inquery));
		else
			$qa_content['title']=qa_lang_sub_html('main/no_results_for_x', qa_html($inquery));
			
		$qa_content['q_list']['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		);
		
		$qa_content['q_list']['qs']=array();
		foreach ($questions as $question) {
			$fields=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_get_vote_view($qa_db, 'Q'), qa_get_option($qa_db, 'show_user_points'));

			$matchpostidscore=array(); // which anchor to link to in question?

			$matchparts=explode(',', $question['matchparts']);
			foreach ($matchparts as $matchpart)
				if (sscanf($matchpart, '%f:%f', $matchpostid, $matchscore)==2)
					@$matchpostidscore[$matchpostid]+=$matchscore;
			
			$matchpostid=array_search(max($matchpostidscore), $matchpostidscore);

			if ($matchpostid != $question['postid'])
				$fields['url']=qa_path_html(qa_q_request($question['postid'], $question['title']), null, null, null, $matchpostid);

			$qa_content['q_list']['qs'][]=$fields;
		}

		$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $qa_start+$gotcount,
			qa_get_option($qa_db, 'pages_prev_next'), array('q' => $inquery), $gotcount>=$retrieve);

	} else
		$qa_content['title']=qa_lang_html('main/search_title');
	
	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=qa_html_suggest_qs_tags();

?>