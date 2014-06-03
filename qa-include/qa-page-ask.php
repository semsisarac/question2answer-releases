<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-ask.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Controller for ask-a-question page


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

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';	
	require_once QA_INCLUDE_DIR.'qa-util-string.php';	
	
//	Set some pending option requests

	qa_captcha_pending();

	qa_options_set_pending(array('permit_post_q', 'confirm_user_emails', 'min_len_q_title', 'max_len_q_title', 'allow_no_category',
		'min_len_q_content', 'max_num_q_tags', 'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs',
		'do_example_tags', 'match_example_tags', 'page_size_ask_tags', 'do_complete_tags', 'voting_on_qs', 'votes_separated',
		'captcha_on_anon_post', 'captcha_on_unconfirmed', 'show_when_created', 'show_user_created', 'show_user_points',
		'permit_anon_view_ips', 'block_ips_write', 'block_bad_words'));


//	Check whether this is a follow-on question and get some info we need from the database

	$infollow=qa_get('follow');
	
	@list($categories, $followanswer)=qa_db_select_with_pending($qa_db,
		qa_db_categories_selectspec(),
		isset($infollow) ? qa_db_full_post_selectspec($qa_login_userid, $infollow) : null
	);
	
	if (@$followanswer['basetype']!='A')
		$followanswer=null;
		

//	Check if we have permission to ask and if should use a captcha

	$permiterror=qa_user_permit_error($qa_db, 'permit_post_q', qa_is_http_post() ? 'Q' : null); // only check rate limit later on
	$usecaptcha=qa_user_use_captcha($qa_db, 'captcha_on_anon_post');


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
			
		$intitle=qa_post_text('title');
		$incontent=qa_post_text('content');
		$intags=qa_post_text('tags');
		$innotify=true; // show notify on by default
	
	//	Process incoming form
	
		if (qa_clicked('doask1') || qa_clicked('doask2') || qa_clicked('doask3')) {			
			
			if (qa_clicked('doask3')) { // process incoming formfor final stage (ready to create question)
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				
				$tagstring=qa_tags_to_tagstring(array_unique(qa_string_to_words(@$intags)));
				$innotify=qa_post_text('notify');
				$inemail=qa_post_text('email');

				$errors=qa_question_validate($qa_db, $intitle, $incontent, $tagstring, $innotify, $inemail);
				
				if ($usecaptcha)
					qa_captcha_validate($qa_db, $_POST, $errors);
				
				if (empty($errors)) {
					if (!isset($qa_login_userid))
						$qa_cookieid=qa_cookie_get_create($qa_db); // create a new cookie if necessary
		
					$questionid=qa_question_create($qa_db, $followanswer, $qa_login_userid, $qa_cookieid, 
						$intitle, $incontent, $tagstring, $innotify, $inemail, isset($categories[$incategoryid]) ? $incategoryid : null);
					
					qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_post', $questionid, null, null);
					qa_redirect(qa_q_request($questionid, $intitle)); // our work is done here
				}
				
				$stage=3; // redisplay the final stage form

			} else
				$errors=qa_question_validate($qa_db, $intitle, null, null, null, null); // process an earlier form
			

			if (empty($errors) || ($stage>1)) { // we are ready to move to stage 2 or 3
				require_once QA_INCLUDE_DIR.'qa-app-format.php';
				
			//	Find out what operations are required (some of these will be ignored, depending on if we show stage 2 or 3)
				
				$doaskcheck=qa_clicked('doask1') && qa_get_option($qa_db, 'do_ask_check_qs');
				$doexampletags=qa_using_tags($qa_db) && qa_get_option($qa_db, 'do_example_tags');
				$docompletetags=qa_using_tags($qa_db) && qa_get_option($qa_db, 'do_complete_tags');
				$askchecksize=$doaskcheck ? qa_get_option($qa_db, 'page_size_ask_check_qs') : 0;
				$countqs=$doexampletags ? QA_DB_RETRIEVE_ASK_TAG_QS : $askchecksize;
				
			//	Find related questions based on the title - for stage 2 (ask check) and/or 3 (example tags)
			
				if ($countqs)
					$relatedquestions=qa_db_select_with_pending($qa_db,
						qa_db_search_posts_selectspec($qa_db, null, qa_string_to_words($intitle), null, null, null, 0, false, $countqs)
					);
					

				if ($doaskcheck) { // for ask check, find questions to suggest based on their score
					$suggestquestions=array_slice($relatedquestions, 0, $askchecksize);
					
					$minscore=qa_match_to_min_score(qa_get_option($qa_db, 'match_ask_check_qs'));
					
					foreach ($suggestquestions as $key => $question)
						if ($question['score']<$minscore)
							unset($suggestquestions[$key]);
				}
				
				if ($doaskcheck && count($suggestquestions)) { // we have something to display for checking duplicate questions
					$stage=2;
					$usershtml=qa_userids_handles_html($qa_db, $suggestquestions);
				
				} else { // move to the full question form
					$stage=3;
					
				//	Find the most popular tags, not related to question
				
					if ($docompletetags)
						$populartags=qa_db_select_with_pending($qa_db, qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS));
					
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
					
						$minweight=exp(qa_match_to_min_score(qa_get_option($qa_db, 'match_example_tags')));
						foreach ($tagweight as $tag => $weight)
							if ($weight<$minweight)
								unset($tagweight[$tag]);
								
						$exampletags=array_slice(array_keys($tagweight), 0, qa_get_option($qa_db, 'page_size_ask_tags'));

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

	qa_content_prepare(false, @$incategoryid);
	
	$qa_content['title']=qa_lang_html(isset($followanswer) ? 'question/ask_follow_title' : 'question/ask_title');

	$qa_content['error']=@$pageerror;
	
	if (!$permiterror) {
		$categoryoptions=qa_category_options($qa_db, $categories);

		if ($stage==1) { // see stages in comment above
			$qa_content['form']=array(
				'tags' => ' NAME="ask" METHOD="POST" ACTION="'.qa_self_html().'" ',
				
				'style' => 'tall',
				
				'fields' => array(
					'title' => array(
						'label' => qa_lang_html('question/q_title_label'),
						'tags' => ' NAME="title" ID="title" ',
						'value' => qa_html(@$intitle),
						'error' => qa_html(@$errors['title']),
						'note' => qa_lang_html('question/q_title_note'),
					),

					'category' => array(
						'label' => qa_lang_html('question/q_category_label'),
						'tags' => ' NAME="category" ',
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
			
			if (isset($followanswer))
				$qa_content['form']['fields']['follows']=array(
					'type' => 'static',
					'label' => qa_lang_html('question/ask_follow_from_a'),
					'value' => qa_html(qa_block_words_replace($followanswer['content'], qa_get_block_words_preg($qa_db)), true),
				);
				
		} elseif ($stage==2) {
			$qa_content['title']=qa_html(@$intitle);
			
			$qa_content['q_list']['title']=qa_lang_html('question/ask_same_q');
			
			$qa_content['q_list']['qs']=array();

			foreach ($suggestquestions as $question)
				$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
					qa_using_tags($qa_db), qa_using_categories($qa_db) ? $categories : null, false,
					qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
					qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
		
			$qa_content['q_list']['form']=array(
				'tags' => ' NAME="ask" METHOD="POST" ACTION="'.qa_self_html().'" ',
				
				'style' => 'basic',
				
				'buttons' => array(
					'proceed' => array(
						'tags' => ' NAME="doask2" ',
						'label' => qa_lang_html('question/different_button'),
					),
				),
				
				'hidden' => array(
					'title' => qa_html(@$intitle),
					'category' => @$incategoryid,
				),
			);

		} else {
			$qa_content['form']=array(
				'tags' => ' NAME="ask" METHOD="POST" ACTION="'.qa_self_html().'" ',
				
				'style' => 'tall',
				
				'fields' => array(
					'title' => array(
						'label' => qa_lang_html('question/q_title_label'),
						'tags' => ' NAME="title" ',
						'value' => qa_html(@$intitle),
						'error' => qa_html(@$errors['title']),
					),
					
					'category' => array(
						'label' => qa_lang_html('question/q_category_label'),
						'tags' => ' NAME="category" ',
						'value' => @$categoryoptions[$incategoryid],
						'type' => 'select',
						'options' => $categoryoptions,
					),
					
					'content' => array(
						'label' => qa_lang_html('question/q_content_label'),
						'tags' => ' NAME="content" ID="content" ',
						'value' => qa_html(@$incontent),
						'error' => qa_html(@$errors['content']),
						'rows' => 12,
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
					'doask3' => '1',
				),
			);
			
			if (!isset($categoryoptions))
				unset($qa_content['form']['fields']['category']);
				
			if (qa_using_tags($qa_db))
				qa_set_up_tag_field($qa_content, $qa_content['form']['fields']['tags'], 'tags', $exampletags, $completetags, qa_get_option($qa_db, 'page_size_ask_tags'));			
			else
				unset($qa_content['form']['fields']['tags']);
			
			$qa_content['focusid']='content';
			
			qa_set_up_notify_fields($qa_content, $qa_content['form']['fields'], 'Q', qa_get_logged_in_email($qa_db),
				@$innotify, @$inemail, @$errors['email']);
			
			if ($usecaptcha)
				qa_set_up_captcha_field($qa_db, $qa_content, $qa_content['form']['fields'], @$errors,
					qa_insert_login_links(qa_lang_html(isset($qa_login_userid) ? 'misc/captcha_confirm_fix' : 'misc/captcha_login_fix')));
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/