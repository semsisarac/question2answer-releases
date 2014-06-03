<?php

/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-questions.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Controller for page listing recent questions


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
	require_once QA_INCLUDE_DIR.'qa-app-q-list.php';
	
	$categoryslug=$pass_subrequest;
	$hascategory=isset($categoryslug);


//	Get list of questions, plus category information

	@list($questions, $categories, $categoryid)=qa_db_select_with_pending(
		qa_db_recent_qs_selectspec($qa_login_userid, $qa_start, $categoryslug),
		qa_db_categories_selectspec(),
		$hascategory ? qa_db_slug_to_category_id_selectspec($categoryslug) : null
	);
	
	if ($hascategory) {
		if (!isset($categoryid))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';
	
		$categorytitlehtml=qa_category_html($categories[$categoryid]);
		$sometitle=qa_lang_html_sub('main/recent_qs_in_x', $categorytitlehtml);
		$nonetitle=qa_lang_html_sub('main/no_questions_found_in_x', $categorytitlehtml);

	} else {
		$sometitle=qa_lang_html('main/recent_qs_title');
		$nonetitle=qa_lang_html('main/no_questions_found');
	}

	
//	Prepare and return content for theme

	return qa_q_list_page_content(
		$questions, qa_opt('page_size_qs'), $qa_start, $hascategory ? $categories[$categoryid]['qcount'] : qa_opt('cache_qcount'), $sometitle, $nonetitle,
		$categories, $categoryid, true, 'questions', qa_opt('feed_for_questions') ? 'questions' : null,
		$hascategory ? qa_html_suggest_qs_tags(qa_using_tags()) : qa_html_suggest_ask($categoryid)
	);


/*
	Omit PHP closing tag to help avoid accidental output
*/