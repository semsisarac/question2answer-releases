<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-categories.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Controller for page listing categories


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


//	Get information about categories
	
	$categories=qa_db_select_with_pending(
		qa_db_categories_selectspec()
	);
	
	
//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('main/all_categories');
	
	$qa_content['ranking']=array('items' => array(), 'rows' => count($categories));
	
	if (count($categories)) {
		foreach ($categories as $category)
			$qa_content['ranking']['items'][]=array(
				'label' => qa_category_html($category),
				'count' => number_format($category['qcount']),
			);
			
	} else {
		$qa_content['title']=qa_lang_html('main/no_categories_found');
		$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags());
	}

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/