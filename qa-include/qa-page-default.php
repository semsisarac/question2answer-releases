<?php

/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-default.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Controller for home page, Q&A listing page, custom pages and plugin pages


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


//	Determine whether path begins with qa or not (question and answer listing can be accessed either way)

	$explicitqa=($qa_request_lc_parts[0]=='qa');
	
	if ($explicitqa) {
		$slug=@$qa_request_parts[1];
		$hasslug=isset($slug);

	} else {
		$hasslug=strlen($qa_request_parts[0]); // $qa_request_parts[0] always present so we need to check its length
		$slug=$hasslug ? $qa_request_parts[0] : null;
	}

	
//	Get list of questions, other bits of information that might be
	
	@list($questions1, $questions2, $categories, $categoryid, $custompage)=qa_db_select_with_pending(
		qa_db_recent_qs_selectspec($qa_login_userid, 0, $slug),
		qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $slug),
		qa_db_categories_selectspec(),
		$hasslug ? qa_db_slug_to_category_id_selectspec($slug) : null,
		($hasslug && !$explicitqa) ? qa_db_page_full_selectspec($slug, false) : null
	);


//	First, if this matches a custom page, return immediately with that page's content
	
	if ( isset($custompage) && !($custompage['flags']&QA_PAGE_FLAGS_EXTERNAL) ) {
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
		
		return $qa_content;
	}


//	Then, if there's a slug that matches no category, check page modules provided by plugins

	if ( (!$explicitqa) && $hasslug && !isset($categoryid) ) {
		$modulenames=qa_list_modules('page');
		
		foreach ($modulenames as $tryname) {
			$trypage=qa_load_module('page', $tryname);
			
			if (method_exists($trypage, 'match_request') && $trypage->match_request($qa_request)) {
				$qa_template='plugin';
				return $trypage->process_request($qa_request);
			}
		}
	}
	
	
//	Then, check whether we are showing a custom home page

	if ( (!$explicitqa) && (!$hasslug) && qa_opt('show_custom_home') ) {
		$qa_template='custom';
		$qa_content=qa_content_prepare();
		$qa_content['title']=qa_html(qa_opt('custom_home_heading'));
		if (qa_opt('show_home_description'))
			$qa_content['description']=qa_html(qa_opt('home_description'));
		$qa_content['custom']=qa_opt('custom_home_content');
		return $qa_content;
	}


//	If we got this far, it's a good old-fashioned Q&A listing page
	
	require_once QA_INCLUDE_DIR.'qa-app-q-list.php';

	$qa_template='qa';
	$questions=array_merge($questions1, $questions2);
	$pagesize=qa_opt('page_size_home');
	
	if ($hasslug) {
		if (!isset($categoryid))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';

		$categorytitlehtml=qa_category_html($categories[$categoryid]);
		$sometitle=qa_lang_html_sub('main/recent_qs_as_in_x', $categorytitlehtml);
		$nonetitle=qa_lang_html_sub('main/no_questions_found_in_x', $categorytitlehtml);

	} else {
		$sometitle=qa_lang_html('main/recent_qs_as_title');
		$nonetitle=qa_lang_html('main/no_questions_found');
	}
	
	
//	Prepare and return content for theme

	$qa_content=qa_q_list_page_content(
		$questions, $pagesize, 0, null, $sometitle, $nonetitle,
		$categories, $categoryid, true, $explicitqa ? 'qa' : '', qa_opt('feed_for_qa') ? 'qa' : null,
		(count($questions)<$pagesize)
			? qa_html_suggest_ask($categoryid)
			: qa_html_suggest_qs_tags(qa_using_tags(), $hasslug ? $categories[$categoryid]['tags'] : null)
	);
	
	if ( (!$explicitqa) && (!$hasslug) && qa_opt('show_home_description') )
		$qa_content['description']=qa_html(qa_opt('home_description'));
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/