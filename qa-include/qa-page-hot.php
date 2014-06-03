<?php

/*
	Question2Answer 1.4.3 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-hot.php
	Version: 1.4.3
	Date: 2011-09-27 18:06:46 GMT
	Description: Controller for page listing hot questions


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
	require_once QA_INCLUDE_DIR.'qa-app-q-list.php';
	

//	Get list of questions

	$questions=qa_db_select_with_pending(
		qa_db_qs_selectspec($qa_login_userid, 'hotness', $qa_start)
	);


//	Prepare and return content for theme

	$qa_content=qa_q_list_page_content(
		$questions, // questions
		qa_opt('page_size_hot_qs'), // questions per page
		$qa_start, // start offset
		qa_opt('cache_qcount'), // total count (null to hide page links)
		qa_lang_html('main/hot_qs_title'), // title if some questions
		qa_lang_html('main/no_questions_found'), // title if no questions
		null, // categories for navigation (null since not shown on this page)
		null, // selected category id (null since not relevant)
		null, // show question counts in category navigation (null since not relevant)
		null, // prefix for links in category navigation (null since no navigation)
		qa_opt('feed_for_hot') ? 'hot' : null, // prefix for RSS feed paths (null to hide)
		qa_html_suggest_ask() // suggest what to do next
	);


	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/