<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-tag.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for page for specific tags


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	
	$tag=$pass_tag; // picked up from index.php


//	Find the questions with this tag

	qa_options_set_pending(array('page_size_tag_qs', 'voting_on_qs', 'voting_on_q_page_only', 'votes_separated', 'show_when_created', 'show_user_points', 'feed_for_tag_qs', 'permit_anon_view_ips', 'block_bad_words'));
	
	list($questions, $qcount, $categories)=qa_db_select_with_pending($qa_db,
		qa_db_tag_recent_qs_selectspec($qa_login_userid, $tag, $qa_start),
		qa_db_tag_count_qs_selectspec($tag),
		qa_db_categories_selectspec()
	);
	
	$pagesize=qa_get_option($qa_db, 'page_size_tag_qs');
	$questions=array_slice($questions, 0, $pagesize);
	$usershtml=qa_userids_handles_html($qa_db, $questions);


//	Prepare content for theme
	
	qa_content_prepare(true);

	$qa_content['title']=qa_lang_html_sub('main/questions_tagged_x', qa_html($tag));
	
	if (!count($questions))
		$qa_content['q_list']['title']=qa_lang_html('main/no_questions_found');

	$qa_content['q_list']['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	);

	$qa_content['q_list']['qs']=array();
	foreach ($questions as $postid => $question)
		$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
			true, qa_using_categories($qa_db) ? $categories : null, qa_get_vote_view($qa_db, 'Q'),
			qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
			qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
		
	$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $qcount, qa_get_option($qa_db, 'pages_prev_next'));

	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=qa_html_suggest_qs_tags(true);

	if (qa_get_option($qa_db, 'feed_for_tag_qs'))
		$qa_content['feed']=array(
			'url' => qa_path_html(qa_feed_request('tag/'.$tag)),
			'label' => qa_lang_html_sub('main/questions_tagged_x', qa_html($tag)),
		);


/*
	Omit PHP closing tag to help avoid accidental output
*/