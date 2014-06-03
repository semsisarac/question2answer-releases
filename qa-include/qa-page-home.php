<?php

/*
	Question2Answer 1.3-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-home.php
	Version: 1.3-beta-2
	Date: 2010-11-11 10:26:02 GMT
	Description: Controller for most question listing pages and custom pages


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';


//	Common function to load the appropriate set of questions

	function qa_home_load_ifcategory($pagesizeoption, $feedoption, $cachecountoption, $allsomekey, $allnonekey, $catsomekey, $catnonekey,
		$questionselectspec1=null, $questionselectspec2=null, $questionselectspec3=null, $questionselectspec4=null, $pageselectspec=null)
	{
		global $categoryslug, $questions, $count, $categories, $categoryid,
			$pagesize, $showcategoryonposts, $sometitle, $nonetitle, $qa_template, $qa_content, $suggest, $showfeed, $qa_request;
		
		@list($questions1, $questions2, $questions3, $questions4, $categories, $categoryid, $custompage)=qa_db_select_with_pending(
			$questionselectspec1,
			$questionselectspec2,
			$questionselectspec3,
			$questionselectspec4,
			qa_db_categories_selectspec(),
			isset($categoryslug) ? qa_db_slug_to_category_id_selectspec($categoryslug) : null,
			$pageselectspec
		);
		
		if (isset($categoryslug) && isset($custompage) && (!($custompage['flags']&QA_PAGE_FLAGS_EXTERNAL))) {
			$qa_template='custom';
			$qa_content=qa_content_prepare();
			$qa_content['title']=qa_html($custompage['heading']);
			$qa_content['custom']=$custompage['content'];
			
			if (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) {
				$qa_content['navigation']['sub']=array(
					'admin/pages' => array(
						'label' => qa_lang('admin/edit_custom_page'),
						'url' => qa_path_html('admin/pages', array('edit' => $custompage['pageid'])),
					),
				);
			}
			
			return false;
		}
		
		if (isset($categoryslug) && !isset($categoryid)) {
			$modulenames=qa_list_modules('page');
			
			foreach ($modulenames as $tryname) {
				$trypage=qa_load_module('page', $tryname);
				
				if (method_exists($trypage, 'match_request') && $trypage->match_request($qa_request)) {
					$qa_template='plugin';
					$qa_content=$trypage->process_request($qa_request);
					if (empty($qa_content))
						$qa_content=qa_content_prepare();
					return false;
				}
			}
	
			header('HTTP/1.0 404 Not Found');
			$qa_template='not-found';
			$qa_content=qa_content_prepare();
			$qa_content['error']=qa_lang_html('main/page_not_found');
			$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags());
			
			return false;
		}
		
		$questions=array_merge(
			is_array($questions1) ? $questions1 : array(),
			is_array($questions2) ? $questions2 : array(),
			is_array($questions3) ? $questions3 : array(),
			is_array($questions4) ? $questions4 : array()
		);
			
		$pagesize=qa_opt($pagesizeoption);
		
		if (isset($categoryid)) {
			$categorytitlehtml=qa_category_html($categories[$categoryid]);
			$sometitle=qa_lang_html_sub($catsomekey, $categorytitlehtml);
			$nonetitle=qa_lang_html_sub($catnonekey, $categorytitlehtml);
			$showcategoryonposts=false;
			$suggest=qa_html_suggest_qs_tags(qa_using_tags(), $categories[$categoryid]['tags']);
			$showfeed=qa_opt($feedoption) && qa_opt('feed_per_category');

		} else {
			if (isset($cachecountoption))
				$count=qa_opt($cachecountoption);
			
			$sometitle=qa_lang_html($allsomekey);
			$nonetitle=qa_lang_html($allnonekey);
			$showcategoryonposts=qa_using_categories();
			$suggest=qa_html_suggest_qs_tags(qa_using_tags());
			$showfeed=qa_opt($feedoption);
		}
		
		return true;
	}
	

//	Get list of questions, page size and other bits of HTML for appropriate version of page

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
				return $qa_content;
				
			if (isset($categoryid)) {
				$count=$categories[$categoryid]['qcount'];
				$suggest=qa_html_suggest_qs_tags(qa_using_tags());

			} else
				$suggest=qa_html_suggest_ask($categoryid);
			break;

		case 'unanswered':
			if (!qa_home_load_ifcategory(
				'page_size_una_qs', 'feed_for_unanswered', 'cache_unaqcount', 'main/unanswered_qs_title', 'main/no_una_questions_found', 'main/unanswered_qs_in_x', 'main/no_una_questions_in_x',
				qa_db_unanswered_qs_selectspec($qa_login_userid, isset($categoryslug) ? 0 : $qa_start, $categoryslug)
			))
				return $qa_content;
			break;
			
		case 'answers': // not currently in navigation
			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_activity', null, 'main/recent_as_title', 'main/no_answers_found', 'main/recent_as_in_x', 'main/no_answers_in_x',
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslug)
			))
				return $qa_content;
			break;
			
		case 'comments': // not currently in navigation
			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_activity', null, 'main/recent_cs_title', 'main/no_comments_found', 'main/recent_cs_in_x', 'main/no_comments_in_x',
				qa_db_recent_c_qs_selectspec($qa_login_userid, 0, $categoryslug)
			))
				return $qa_content;
			break;
			
		case 'activity': // not currently in navigation
			$categoryqcount=true;

			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_activity', null, 'main/recent_activity_title', 'main/no_questions_found', 'main/recent_activity_in_x', 'main/no_questions_found_in_x',
				qa_db_recent_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_c_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_edit_qs_selectspec($qa_login_userid, 0, $categoryslug)
			))
				return $qa_content;
			break;
		
		case 'qa':
		default: // home page itself shows combined recent questions asked and answered - also 'qa' page does the same
			if ($qa_request_0_lc!='qa') {
				$categorypathprefix='';
				$feedpathprefix='qa';
				$categoryslug=strlen($qa_request_0_lc) ? $qa_request_0_lc : null;
			}
			
			$categoryqcount=true;
			
			if (!qa_home_load_ifcategory(
				'page_size_home', 'feed_for_qa', null, 'main/recent_qs_as_title', 'main/no_questions_found', 'main/recent_qs_as_in_x', 'main/no_questions_found_in_x',
				qa_db_recent_qs_selectspec($qa_login_userid, 0, $categoryslug),
				qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslug),
				null, null,
				isset($categoryslug) ? qa_db_page_full_selectspec($categoryslug, false) : null
			))
				return $qa_content;
			
			if ( ($qa_request_0_lc!='qa') && (!isset($categoryid)) && qa_opt('show_home_description') )
				$description=qa_opt('home_description');
			else
				$description=null;

			if (($qa_request_0_lc=='') && qa_opt('show_custom_home')) {
				$qa_template='custom';
				$qa_content=qa_content_prepare();
				$qa_content['title']=qa_html(qa_opt('custom_home_heading'));
				$qa_content['description']=qa_html($description);
				$qa_content['custom']=qa_opt('custom_home_content');
				return $qa_content;
			}

			if (count($questions)<$pagesize)
				$suggest=qa_html_suggest_ask($categoryid);
			break;
	}
	
	
//	Sort and remove any question referenced twice, chop down to size, get user information for display

	$questions=qa_any_sort_and_dedupe($questions);
	
	if (isset($pagesize))
		$questions=array_slice($questions, 0, $pagesize);

	$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));


//	Prepare content for theme
	
	$qa_content=qa_content_prepare(true, $categoryid);

	$qa_content['q_list']['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	);
	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		$qa_content['title']=$sometitle;
	
		foreach ($questions as $question)
			$qa_content['q_list']['qs'][]=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				$showcategoryonposts ? $categories : array(), qa_post_html_defaults('Q'));
	
	} else
		$qa_content['title']=$nonetitle;
		
	$qa_content['description']=qa_html($description);
	
	if (isset($count) && isset($pagesize))
		$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $count, qa_opt('pages_prev_next'));
	
	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=$suggest;
		
	if (qa_using_categories() && count($categories))
		$qa_content['navigation']['cat']=qa_category_navigation($categories, $categoryid, $categorypathprefix, $categoryqcount);
	
	if ($showfeed)
		$qa_content['feed']=array(
			'url' => qa_path_html(qa_feed_request($feedpathprefix.(isset($categoryid) ? ('/'.$categories[$categoryid]['tags']) : ''))),
			'label' => strip_tags($sometitle),
		);
		
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/