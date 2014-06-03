<?php
	
/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-tag.php
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
	
	$tag=$pass_tag; // picked up from index.php

//	Find the questions with this tag

	qa_options_set_pending(array('page_size_tag_qs', 'voting_on_qs', 'votes_separated'));
	
	list($questions, $qcount)=qa_db_select_with_pending($qa_db,
		qa_db_tag_recent_qs_selectspec($qa_login_userid, $tag, $qa_start),
		qa_db_tag_count_qs_selectspec($tag)
	);
	
	$pagesize=qa_get_option($qa_db, 'page_size_tag_qs');
	$questions=array_slice($questions, 0, $pagesize);	
	$usershtml=qa_userids_handles_html($qa_db, $questions);

//	Prepare content for theme
	
	qa_content_prepare(true);

	$qa_content['title']=qa_lang_sub_html('main/questions_tagged_x', qa_html($tag));
	
	if (!count($questions))
		$qa_content['q_list']['title']=qa_lang_html('main/no_questions_found');

	$qa_content['q_list']['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	);

	$qa_content['q_list']['qs']=array();
	foreach ($questions as $postid => $question)
		$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, qa_get_vote_view($qa_db, 'Q'));
		
	$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $qcount, qa_get_option($qa_db, 'pages_prev_next'));

	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=qa_html_suggest_qs_tags();
?>