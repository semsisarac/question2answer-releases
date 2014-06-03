<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-home.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for most question listing pages and custom pages


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


//	Common function to load the appropriate set of questions

	function qa_home_load_ifcategory($pagesizeoption, $feedoption, $cachecountoption, $allsomekey, $allnonekey, $catsomekey, $catnonekey,
		$questionselectspec1=null, $questionselectspec2=null, $questionselectspec3=null, $pageselectspec=null)
	{
		global $qa_db, $categoryslug, $questions, $count, $categories, $categoryid,
			$pagesize, $showcategoryonposts, $sometitle, $nonetitle, $qa_template, $qa_content, $suggest, $showfeed;
		
		qa_options_set_pending(array($pagesizeoption, $feedoption, 'feed_per_category'));
		
		@list($questions1, $questions2, $questions3, $count, $categories, $categoryid, $custompage)=qa_db_select_with_pending($qa_db,
			$questionselectspec1,
			$questionselectspec2,
			$questionselectspec3,
			(isset($cachecountoption) && !isset($categoryslug)) ? qa_db_options_cache_selectspec($cachecountoption) : null,
			qa_db_categories_selectspec(),
			isset($categoryslug) ? qa_db_slug_to_category_id_selectspec($categoryslug) : null,
			$pageselectspec
		);
		
		if (isset($categoryslug) && isset($custompage)) {
			$qa_template='custom';
			qa_content_prepare();
			$qa_content['title']=qa_html($custompage['heading']);
			$qa_content['custom']=$custompage['content'];
			return false;
		}
		
		if (isset($categoryslug) && !isset($categoryid)) {
			header('HTTP/1.0 404 Not Found');
			$qa_template='not-found';
			qa_content_prepare();
			$qa_content['error']=qa_lang_html('main/page_not_found');
			$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags($qa_db));
			
			return false;
		}
		
		$questions=array_merge(
			is_array($questions1) ? $questions1 : array(),
			is_array($questions2) ? $questions2 : array(),
			is_array($questions3) ? $questions3 : array()
		);
			
		$pagesize=qa_get_option($qa_db, $pagesizeoption);
		
		if (isset($categoryid)) {
			$categorytitlehtml=qa_category_html($categories[$categoryid]);
			$sometitle=qa_lang_html_sub($catsomekey, $categorytitlehtml);
			$nonetitle=qa_lang_html_sub($catnonekey, $categorytitlehtml);
			$showcategoryonposts=false;
			$suggest=qa_html_suggest_qs_tags(qa_using_tags($qa_db), $categories[$categoryid]['tags']);
			$showfeed=qa_get_option($qa_db, $feedoption) && qa_get_option($qa_db, 'feed_per_category');

		} else {
			$sometitle=qa_lang_html($allsomekey);
			$nonetitle=qa_lang_html($allnonekey);
			$showcategoryonposts=qa_using_categories($qa_db);
			$suggest=qa_html_suggest_qs_tags(qa_using_tags($qa_db));
			$showfeed=qa_get_option($qa_db, $feedoption);
		}
		
		return true;
	}
	

//	Get list of questions, page size and other bits of HTML for appropriate version of page

	qa_options_set_pending(array('voting_on_qs', 'voting_on_q_page_only', 'votes_separated', 'show_when_created', 'permit_anon_view_ips', 'show_user_points', 'block_bad_words'));
	
	$qa_request_0_lc=$qa_request_lc_parts[0];
	$categorypathprefix=$qa_request_0_lc;
	$categoryslug=@$qa_request_parts[1];
	$feedpathprefix=$qa_request_0_lc;
	$showfeed=false;
	$categoryqcount=false;
	$description=null;
	
	switch ($qa_request_0_lc) { // this file doesn't just serve the home page
		case 'questions':
			$categoryqcount=true;

			if (!qa_home_load_ifcategory(
				'page_size_qs', 'feed_for_questions', 'cache_qcount', 'main/recent_qs_title', 'main/no_questions_found', 'main/recent_qs_in_x', 'main/no_questions_found_in_x',
				qa_db_recent_qs_selectspec($qa_login_userid, $qa_start, $categoryslug)
			))
				return;
				
			if (isset($categoryid)) {
				$count=$categories[$categoryid]['qcount'];
				$suggest=qa_html_suggest_qs_tags(qa_using_tags($qa_db));

			} else
				$suggest=qa_html_suggest_ask($categoryid);
			break;

		case 'unanswered':
			if (!qa_home_load_ifcategory(
				'page_size_una_qs', 'feed_for_unanswered', 'cache_unaqcount', 'main/unanswered_qs_title', 'main/no_una_questions_found', 'main/unanswered_qs_in_x', 'main/no_una_questions_in_x',
				qa_db_unanswered_qs_selectspec($qa_login_userid, isset($categoryslug) ? 0 : $qa_start, $categoryslug)
			))
				return;
			break;
			
		case 'answers': // not currently in navigation
			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_activity', null, 'main/recent_as_title', 'main/no_answers_found', 'main/recent_as_in_x', 'main/no_answers_in_x',
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslug)
			))
				return;
			break;
			
		case 'comments': // not currently in navigation
			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_activity', null, 'main/recent_cs_title', 'main/no_comments_found', 'main/recent_cs_in_x', 'main/no_comments_in_x',
				qa_db_recent_c_qs_selectspec($qa_login_userid, 0, $categoryslug)
			))
				return;
			break;
			
		case 'activity': // not currently in navigation
			$categoryqcount=true;

			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_activity', null, 'main/recent_activity_title', 'main/no_questions_found', 'main/recent_activity_in_x', 'main/no_questions_found_in_x',
				qa_db_recent_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_c_qs_selectspec($qa_login_userid, 0, $categoryslug)
			))
				return;
			break;
		
		case 'qa':
		default: // home page itself shows combined recent questions asked and answered - also 'qa' page does the same
			if ($qa_request_0_lc!='qa') {
				qa_options_set_pending(array('show_home_description', 'home_description', 'show_custom_home', 'custom_home_heading', 'custom_home_content'));
				$categorypathprefix='';
				$feedpathprefix='qa';
				$categoryslug=strlen($qa_request_0_lc) ? $qa_request_0_lc : null;
			}
			
			$categoryqcount=true;
			
			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_qa', null, 'main/recent_qs_as_title', 'main/no_questions_found', 'main/recent_qs_as_in_x', 'main/no_questions_found_in_x',
				qa_db_recent_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslug),
				null,
				isset($categoryslug) ? qa_db_page_full_selectspec($categoryslug, false) : null
			))
				return;
			
			if ( ($qa_request_0_lc!='qa') && (!isset($categoryid)) && qa_get_option($qa_db, 'show_home_description') )
				$description=qa_get_option($qa_db, 'home_description');
			else
				$description=null;

			if (($qa_request_0_lc=='') && qa_get_option($qa_db, 'show_custom_home')) {
				$qa_template='custom';
				qa_content_prepare();
				$qa_content['title']=qa_html(qa_get_option($qa_db, 'custom_home_heading'));
				$qa_content['description']=qa_html($description);
				$qa_content['custom']=qa_get_option($qa_db, 'custom_home_content');
				return;
			}

			if (count($questions)<$pagesize)
				$suggest=qa_html_suggest_ask($categoryid);
			break;
	}
	
	
//	Sort and remove any question referenced twice, chop down to size, get user information for display

	$questions=qa_any_sort_and_dedupe($questions);
	
	if (isset($pagesize))
		$questions=array_slice($questions, 0, $pagesize);

	$usershtml=qa_userids_handles_html($qa_db, qa_any_get_userids_handles($questions));


//	Prepare content for theme
	
	qa_content_prepare(true, $categoryid);

	$qa_content['q_list']['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	);
	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		$qa_content['title']=$sometitle;
	
		foreach ($questions as $question)
			$qa_content['q_list']['qs'][]=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid,
				$usershtml, qa_using_tags($qa_db), $showcategoryonposts ? $categories : array(), qa_get_vote_view($qa_db, 'Q'),
				qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
				qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
	
	} else
		$qa_content['title']=$nonetitle;
		
	$qa_content['description']=qa_html($description);
	
	if (isset($count) && isset($pagesize))
		$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $count, qa_get_option($qa_db, 'pages_prev_next'));
	
	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=$suggest;
		
	if (qa_using_categories($qa_db) && count($categories))
		$qa_content['navigation']['cat']=qa_category_navigation($categories, $categoryid, $categorypathprefix, $categoryqcount);
	
	if ($showfeed)
		$qa_content['feed']=array(
			'url' => qa_path_html(qa_feed_request($feedpathprefix.(isset($categoryid) ? ('/'.$categories[$categoryid]['tags']) : ''))),
			'label' => strip_tags($sometitle),
		);


/*
	Omit PHP closing tag to help avoid accidental output
*/