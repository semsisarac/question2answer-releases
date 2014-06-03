<?php

/*
	Question2Answer 1.4-beta-1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-q-list.php
	Version: 1.4-beta-1
	Date: 2011-05-25 07:38:57 GMT
	Description: Controller for most question listing pages, plus custom pages and plugin pages


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

	
	function qa_q_list_page_content($questions, $pagesize, $start, $count, $sometitle, $nonetitle,
		$categories, $categoryid, $categoryqcount, $categorypathprefix, $feedpathprefix, $suggest, $pagelinkparams=array())
/*
	Returns the $qa_content structure for a question list page showing $questions retrieved from the database.
	If $pagesize is not null, it sets the max number of questions to display.
	If $count is not null, pagination is determined by $start and $count.
	The page title is $sometitle unless there are no questions shown, in which case it's $nonetitle.
	$categories should contain the category list from the database, and $categoryid the current category shown.
	For the category navigation menu, per-category question counts are shown if $categoryqcount is true, and the 
	menu links have $categorypathprefix as their prefix. But if $categorypathprefix is null, it's not shown.
	If $feedpathprefix is set, the page has an RSS feed whose URL uses that prefix.
	If there are no links to other pages, $suggest is used to suggest what the user should do.
	
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
	
		global $qa_login_userid, $qa_cookieid, $qa_request; // get globals from qa-page.php

		
	//	Chop down to size, get user information for display

		if (isset($pagesize))
			$questions=array_slice($questions, 0, $pagesize);
	
		$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));


	//	Prepare content for theme
		
		$qa_content=qa_content_prepare(true, array_keys(qa_category_path($categories, $categoryid)));
	
		$qa_content['q_list']['form']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		);
		
		$qa_content['q_list']['qs']=array();
		
		if (count($questions)) {
			$qa_content['title']=$sometitle;
		
			$options=qa_post_html_defaults('Q');			
			if (isset($categorypathprefix))
				$options['categorypathprefix']=$categorypathprefix;
				
			foreach ($questions as $question)
				$qa_content['q_list']['qs'][]=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, null, $options);

		} else
			$qa_content['title']=$nonetitle;
			
		if (isset($count) && isset($pagesize))
			$qa_content['page_links']=qa_html_page_links($qa_request, $start, $pagesize, $count, qa_opt('pages_prev_next'), $pagelinkparams);
		
		if (empty($qa_content['page_links']))
			$qa_content['suggest_next']=$suggest;
			
		if (qa_using_categories() && count($categories) && isset($categorypathprefix))
			$qa_content['navigation']['cat']=qa_category_navigation($categories, $categoryid, $categorypathprefix, $categoryqcount);
		
		if (isset($feedpathprefix) && (qa_opt('feed_per_category') || !isset($categoryid)) )
			$qa_content['feed']=array(
				'url' => qa_path_html(qa_feed_request($feedpathprefix.(isset($categoryid) ? ('/'.qa_category_path_request($categories, $categoryid)) : ''))),
				'label' => strip_tags($sometitle),
			);
			
		return $qa_content;
	}
	
	
	function qa_qs_sub_navigation($sort)
/*
	Return the sub navigation structure common to question listing pages
*/
	{
		$navigation=array(
			'recent' => array(
				'label' => qa_lang('main/nav_most_recent'),
				'url' => qa_path_html('questions'),
			),
			
			'hot' => array(
				'label' => qa_lang('main/nav_hot'),
				'url' => qa_path_html('questions', array('sort' => 'hot')),
			),
			
			'votes' => array(
				'label' => qa_lang('main/nav_most_votes'),
				'url' => qa_path_html('questions', array('sort' => 'votes')),
			),

			'answers' => array(
				'label' => qa_lang('main/nav_most_answers'),
				'url' => qa_path_html('questions', array('sort' => 'answers')),
			),

			'views' => array(
				'label' => qa_lang('main/nav_most_views'),
				'url' => qa_path_html('questions', array('sort' => 'views')),
			),
		);
		
		if (isset($navigation[$sort]))
			$navigation[$sort]['selected']=true;
		else
			$navigation['recent']['selected']=true;
		
		if (!qa_opt('do_count_q_views'))
			unset($navigation['views']);
		
		return $navigation;
	}



/*
	Omit PHP closing tag to help avoid accidental output
*/