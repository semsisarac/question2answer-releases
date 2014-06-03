<?php

/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-q-list.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
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
		$categories, $categoryid, $categoryqcount, $categorypathprefix, $feedpathprefix, $suggest)
/*
	Returns the $qa_content structure for a question list page showing $questions retrieved from the database.
	If $pagesize is not null, it sets the max number of questions to display.
	If $count is not null, pagination is determined by $start and $count.
	The page title is $sometitle unless there are no questions shown, in which case it's $nonetitle.
	$categories should contain the category list from the database, and $categoryid the current category shown.
	For the category navigation menu, per-category question counts are shown if $categoryqcount is true, and the 
	menu links have $categorypathprefix as their prefix.
	If $feedpathprefix is set, the page has an RSS feed whose URL uses that prefix.
	If there are no links to other pages, $suggest is used to suggest what the user should do.
	
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
	
		global $qa_login_userid, $qa_cookieid, $qa_request; // get globals from qa-page.php

		
	//	Sort and remove any question referenced twice, chop down to size, get user information for display

		$questions=qa_any_sort_and_dedupe($questions);
		
		if (isset($pagesize))
			$questions=array_slice($questions, 0, $pagesize);
	
		$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));


	//	Prepare content for theme
		
		$qa_content=qa_content_prepare(true, $categoryid);
	
		$qa_content['q_list']['form']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		);
		
		$qa_content['q_list']['qs']=array();
		
		if (count($questions)) {
			$qa_content['title']=$sometitle;
		
			foreach ($questions as $question)
				$qa_content['q_list']['qs'][]=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
					(qa_using_categories() && !isset($categoryid)) ? $categories : array(), qa_post_html_defaults('Q'));

		} else
			$qa_content['title']=$nonetitle;
			
		if (isset($count) && isset($pagesize))
			$qa_content['page_links']=qa_html_page_links($qa_request, $start, $pagesize, $count, qa_opt('pages_prev_next'));
		
		if (empty($qa_content['page_links']))
			$qa_content['suggest_next']=$suggest;
			
		if (qa_using_categories() && count($categories))
			$qa_content['navigation']['cat']=qa_category_navigation($categories, $categoryid, $categorypathprefix, $categoryqcount);
		
		if (isset($feedpathprefix) && (qa_opt('feed_per_category') || !isset($categoryid)) )
			$qa_content['feed']=array(
				'url' => qa_path_html(qa_feed_request($feedpathprefix.(isset($categoryid) ? ('/'.$categories[$categoryid]['tags']) : ''))),
				'label' => strip_tags($sometitle),
			);
			
		return $qa_content;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/