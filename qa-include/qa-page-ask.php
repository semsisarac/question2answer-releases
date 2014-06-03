<?php

/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-ask.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Controller for ask-a-question page


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

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	

//	Check whether this is a follow-on question and get some info we need from the database

	$infollow=qa_get('follow');
	
	@list($categories, $followanswer)=qa_db_select_with_pending(
		qa_db_categories_selectspec(),
		isset($infollow) ? qa_db_full_post_selectspec($qa_login_userid, $infollow) : null
	);
	
	if (@$followanswer['basetype']!='A')
		$followanswer=null;
		

//	Check if we have permission to ask and if should use a captcha

	$permiterror=qa_user_permit_error('permit_post_q', qa_is_http_post() ? 'Q' : null); // only check rate limit later on
	$usecaptcha=qa_user_use_captcha('captcha_on_anon_post');


//	Check for permission error, otherwise proceed to process input

	if ($permiterror) {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		switch ($permiterror) {
			case 'login':
				$pageerror=qa_insert_login_links(qa_lang_html('question/ask_must_login'), $qa_request, isset($infollow) ? array('follow' => $infollow) : null);
				break;
				
			case 'confirm':
				$pageerror=qa_insert_login_links(qa_lang_html('question/ask_must_confirm'), $qa_request, isset($infollow) ? array('follow' => $infollow) : null);
				break;
				
			case 'limit':
				$pageerror=qa_lang_html('question/ask_limit');
				break;
				
			default:
				$pageerror=qa_lang_html('users/no_permission');
				break;
		}

	} else {

	//	Stage 1: Enter question title only
	//	Stage 2: Check that the question is not a duplicate (stage may be skipped based on option or if there are any to show)
	//	Stage 3: Enter full question details

	//	Get user inputs and set some values to their defaults

		$stage=1;
		
		$incategoryid=qa_post_text('category');
		if (!isset($incategoryid))
			$incategoryid=qa_get('cat');
			
		$innotify=true; // show notify on by default
		
	
	//	Process incoming form
	
		if (qa_clicked('doask1') || qa_clicked('doask2') || qa_clicked('doask3')) {			
			$intitle=qa_post_text('title');
			$intags=qa_post_text('tags'); // here to allow tags to be posted by an external form

			if (qa_clicked('doask3')) { // process incoming formfor final stage (ready to create question)
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				
				$tagstring=qa_tags_to_tagstring(array_unique(qa_string_to_words(@$intags, true, false, false, false)));

				$innotify=qa_post_text('notify');
				$inemail=qa_post_text('email');

				qa_get_post_content('editor', 'content', $ineditor, $incontent, $informat, $intext);
				
				$errors=qa_question_validate($intitle, $incontent, $informat, $intext, $tagstring, $innotify, $inemail);
				
				if ($usecaptcha)
					qa_captcha_validate($_POST, $errors);
				
				if (empty($errors)) {
					if (!isset($qa_login_userid))
						$qa_cookieid=qa_cookie_get_create(); // create a new cookie if necessary
		
					$questionid=qa_question_create($followanswer, $qa_login_userid, qa_get_logged_in_handle(), $qa_cookieid,
						$intitle, $incontent, $informat, $intext, $tagstring, $innotify, $inemail,
						isset($categories[$incategoryid]) ? $incategoryid : null);
					
					qa_report_write_action($qa_login_userid, $qa_cookieid, 'q_post', $questionid, null, null);
					qa_redirect(qa_q_request($questionid, $intitle)); // our work is done here
				}
				
				$stage=3; // redisplay the final stage form

			} else
				$errors=qa_question_validate($intitle, null, null, null, null, null, null); // process an earlier form
			

			if (empty($errors) || ($stage>1)) { // we are ready to move to stage 2 or 3
				require_once QA_INCLUDE_DIR.'qa-app-format.php';
				
			//	Find out what operations are required (some of these will be ignored, depending on if we show stage 2 or 3)
				
				$doaskcheck=qa_clicked('doask1') && qa_opt('do_ask_check_qs');
				$doexampletags=qa_using_tags() && qa_opt('do_example_tags');
				$docompletetags=qa_using_tags() && qa_opt('do_complete_tags');
				$askchecksize=$doaskcheck ? qa_opt('page_size_ask_check_qs') : 0;
				$countqs=$doexampletags ? QA_DB_RETRIEVE_ASK_TAG_QS : $askchecksize;
				
			//	Find related questions based on the title - for stage 2 (ask check) and/or 3 (example tags)
			
				if ($countqs)
					$relatedquestions=qa_db_select_with_pending(
						qa_db_search_posts_selectspec($qa_login_userid, qa_string_to_words($intitle), null, null, null, null, 0, false, $countqs)
					);
					

				if ($doaskcheck) { // for ask check, find questions to suggest based on their score
					$suggestquestions=array_slice($relatedquestions, 0, $askchecksize);
					
					$minscore=qa_match_to_min_score(qa_opt('match_ask_check_qs'));
					
					foreach ($suggestquestions as $key => $question)
						if ($question['score']<$minscore)
							unset($suggestquestions[$key]);
				}
				
				if ($doaskcheck && count($suggestquestions)) { // we have something to display for checking duplicate questions
					$stage=2;
					$usershtml=qa_userids_handles_html($suggestquestions);
				
				} else { // move to the full question form
					$stage=3;
					
				//	Find the most popular tags, not related to question
				
					if ($docompletetags)
						$populartags=qa_db_select_with_pending(qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS));
					
				//	Find the example tags to suggest based on the question title, if appropriate
					
					if ($doexampletags) {
				
					//	Calculate score-adjusted frequency of each tag from related questions
				
						$tagweight=array();
						foreach ($relatedquestions as $question) {
							$tags=qa_tagstring_to_tags($question['tags']);
							foreach ($tags as $tag)
								@$tagweight[$tag]+=exp($question['score']);
						}
						
					//	If appropriate, add extra weight to tags in the auto-complete list based on what we learned from related questions
						
						if ($docompletetags) {
							foreach ($tagweight as $tag => $weight)
								@$populartags[$tag]+=$weight;
								
							arsort($populartags, SORT_NUMERIC); // re-sort required due to changed values
						}
					
					//	Create the list of example tags based on threshold and length
					
						arsort($tagweight, SORT_NUMERIC);
					
						$minweight=exp(qa_match_to_min_score(qa_opt('match_example_tags')));
						foreach ($tagweight as $tag => $weight)
							if ($weight<$minweight)
								unset($tagweight[$tag]);
								
						$exampletags=array_slice(array_keys($tagweight), 0, qa_opt('page_size_ask_tags'));

					} else
						$exampletags=array();
				
				//	Final step to create list of auto-complete tags
					
					if ($docompletetags)
						$completetags=array_keys($populartags);
					else
						$completetags=array();
				}
			}
		}
	}


//	Prepare content for theme

	$qa_content=qa_content_prepare(false, @$incategoryid);
	
	$qa_content['title']=qa_lang_html(isset($followanswer) ? 'question/ask_follow_title' : 'question/ask_title');

	$qa_content['error']=@$pageerror;
	
	if (!$permiterror) {
		$categoryoptions=qa_category_options($categories);

		if ($stage==1) { // see stages in comment above
			$qa_content['form']=array(
				'tags' => 'NAME="ask" METHOD="POST" ACTION="'.qa_self_html().'"',
				
				'style' => 'tall',
				
				'fields' => array(
					'title' => array(
						'label' => qa_lang_html('question/q_title_label'),
						'tags' => 'NAME="title" ID="title"',
						'value' => qa_html(@$intitle),
						'error' => qa_html(@$errors['title']),
						'note' => qa_lang_html('question/q_title_note'),
					),

					'category' => array(
						'label' => qa_lang_html('question/q_category_label'),
						'tags' => 'NAME="category"',
						'value' => @$categoryoptions[$incategoryid],
						'type' => 'select',
						'options' => $categoryoptions,
					),
				),
				
				'buttons' => array(
					'ask' => array(
						'label' => qa_lang_html('question/continue_button'),
					),
				),
				
				'hidden' => array(
					'doask1' => '1', // for IE
				),
			);
			
			if (!isset($categoryoptions))
				unset($qa_content['form']['fields']['category']);
			
			$qa_content['focusid']='title';
			
			if (isset($followanswer)) {
				$viewer=qa_load_viewer($followanswer['content'], $followanswer['format']);
				
				$qa_content['form']['fields']['follows']=array(
					'type' => 'static',
					'label' => qa_lang_html('question/ask_follow_from_a'),
					'value' => $viewer->get_html($followanswer['content'], $followanswer['format'], array('blockwordspreg' => qa_get_block_words_preg())),
				);
			}
				
		} elseif ($stage==2) {
			$qa_content['title']=qa_html(@$intitle);
			
			$qa_content['q_list']['title']=qa_lang_html('question/ask_same_q');
			
			$qa_content['q_list']['qs']=array();
			
			$htmloptions=qa_post_html_defaults('Q');
			$htmloptions['voteview']=qa_get_vote_view('Q', false, false);

			foreach ($suggestquestions as $question)
				$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, 
					qa_using_categories() ? $categories : null, $htmloptions);

		
			$qa_content['q_list']['form']=array(
				'tags' => 'NAME="ask" METHOD="POST" ACTION="'.qa_self_html().'"',
				
				'style' => 'basic',
				
				'buttons' => array(
					'proceed' => array(
						'tags' => 'NAME="doask2"',
						'label' => qa_lang_html('question/different_button'),
					),
				),
				
				'hidden' => array(
					'title' => qa_html(@$intitle),
					'category' => @$incategoryid,
				),
			);

		} else {
			$editorname=isset($ineditor) ? $ineditor : qa_opt('editor_for_qs');
			$editor=qa_load_editor(@$incontent, @$informat, $editorname);

			$qa_content['form']=array(
				'tags' => 'NAME="ask" METHOD="POST" ACTION="'.qa_self_html().'"',
				
				'style' => 'tall',
				
				'fields' => array(
					'title' => array(
						'label' => qa_lang_html('question/q_title_label'),
						'tags' => 'NAME="title"',
						'value' => qa_html(@$intitle),
						'error' => qa_html(@$errors['title']),
					),
					
					'category' => array(
						'label' => qa_lang_html('question/q_category_label'),
						'tags' => 'NAME="category"',
						'value' => @$categoryoptions[$incategoryid],
						'type' => 'select',
						'options' => $categoryoptions,
					),
					
					'content' => array_merge(
						$editor->get_field($qa_content, @$incontent, @$informat, 'content', 12, true),
						array(
							'label' => qa_lang_html('question/q_content_label'),
							'error' => qa_html(@$errors['content']),
						)
					),
					
					'tags' => array(
						'label' => qa_lang_html('question/q_tags_label'),
						'value' => qa_html(@$intags),
						'error' => qa_html(@$errors['tags']),
					),
					
				),
				
				'buttons' => array(
					'ask' => array(
						'label' => qa_lang_html('question/ask_button'),
					),
				),
				
				'hidden' => array(
					'editor' => qa_html($editorname),
					'doask3' => '1',
				),
			);
			
			if (!isset($categoryoptions))
				unset($qa_content['form']['fields']['category']);
				
			if (qa_using_tags())
				qa_set_up_tag_field($qa_content, $qa_content['form']['fields']['tags'], 'tags', $exampletags, $completetags, qa_opt('page_size_ask_tags'));
			else
				unset($qa_content['form']['fields']['tags']);
			
			qa_set_up_notify_fields($qa_content, $qa_content['form']['fields'], 'Q', qa_get_logged_in_email(),
				@$innotify, @$inemail, @$errors['email']);
			
			if ($usecaptcha)
				qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors,
					qa_insert_login_links(qa_lang_html(isset($qa_login_userid) ? 'misc/captcha_confirm_fix' : 'misc/captcha_login_fix')));
		}
	}

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/