<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-ask.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Controller for ask-a-question page


	This software is licensed for use in websites which are connected to the
	public world wide web and which offer unrestricted access worldwide. It
	may also be freely modified for use on such websites, so long as a
	link to http://www.question2answer.org/ is displayed on each page.


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
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';

	
//	Check if we need to be logged in or use captcha

	qa_captcha_pending();

	qa_options_set_pending(array('min_len_q_title', 'min_len_q_content', 'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs',
		'do_example_tags', 'match_example_tags', 'page_size_ask_tags', 'do_complete_tags', 'voting_on_qs', 'votes_separated', 'captcha_on_anon_post', 'show_user_points'));
	
	$loginerror=(!isset($qa_login_userid)) && qa_get_option($qa_db, 'ask_needs_login');
	$usecaptcha=(!isset($qa_login_userid)) && qa_get_option($qa_db, 'captcha_on_anon_post');


//	Unless we have a need to log in, process the appropriate stage of the ask page:
//	Stage 1: Enter question title only
//	Stage 2: Check that the question is not a duplicate (stage may be skipped based on option or if there are any to show)
//	Stage 3: Enter full question details

	if ($loginerror) {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		$pageerror=qa_insert_login_links(qa_lang_html('question/ask_must_login'), $qa_request);

	} else {
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		
	//	Get user inputs and set some values to their defaults

		$stage=1;
		
		$infollow=qa_get('follow');
		$intitle=qa_post_text('title');
		$incontent=qa_post_text('content');
		$intags=qa_post_text('tags');
		$innotify=true; // show notify on by default
	
	//	Retrieve information about the answer we are following on from
	
		if (isset($infollow)) {
			$followanswer=qa_db_select_with_pending($qa_db,
				qa_db_full_post_selectspec($qa_login_userid, $infollow)
			);
			
			if ($followanswer['basetype']!='A')
				$followanswer=null;
				
		} else
			$followanswer=null;
	
	//	Process incoming form
	
		if (qa_clicked('doask1') || qa_clicked('doask2') || qa_clicked('doask3')) {
			if (qa_limits_remaining($qa_db, $qa_login_userid, 'Q')) {
				require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
				
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
			
						$questionid=qa_question_create($qa_db, $followanswer,
							$qa_login_userid, $qa_cookieid, $intitle, $incontent, $tagstring, $innotify, $inemail);
						
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
					$doexampletags=qa_get_option($qa_db, 'do_example_tags');
					$docompletetags=qa_get_option($qa_db, 'do_complete_tags');
					$askchecksize=$doaskcheck ? qa_get_option($qa_db, 'page_size_ask_check_qs') : 0;
					$countqs=$doexampletags ? QA_DB_RETRIEVE_ASK_TAG_QS : $askchecksize;
					
				//	Find related questions based on the title - for stage 2 (ask check) and/or 3 (example tags)
				
					if ($countqs)
						$relatedquestions=qa_db_select_with_pending($qa_db,
							qa_db_search_posts_selectspec($qa_db, null, qa_string_to_words($intitle), null, null, null, 0, $countqs)
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
				
			} else {
				$pageerror=qa_lang_html('main/ask_limit');
			}
		}
	}


//	Prepare content for theme

	qa_content_prepare();
	
	$qa_content['title']=qa_lang_html(isset($followanswer) ? 'question/ask_follow_title' : 'question/ask_title');

	$qa_content['error']=@$pageerror;
	
	if (!$loginerror) {
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
			
			$qa_content['focusid']='title';
			
			if (isset($followanswer))
				$qa_content['form']['fields']['follows']=array(
					'type' => 'static',
					'label' => qa_lang_html('question/ask_follow_from_a'),
					'value' => qa_html($followanswer['content'], true),
				);
				
		} elseif ($stage==2) {
			$qa_content['title']=qa_html(@$intitle);
			
			$qa_content['q_list']['title']=qa_lang_html('question/ask_same_q');
			
			$qa_content['q_list']['qs']=array();

			foreach ($suggestquestions as $question)
				$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
					false, qa_get_option($qa_db, 'show_user_points'));
		
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
			
			$qa_content['focusid']='content';
			
			qa_set_up_notify_fields($qa_content, $qa_content['form']['fields'], 'Q', $qa_login_email,
				@$innotify, @$inemail, @$errors['email']);
			
			qa_set_up_tag_field($qa_content, $qa_content['form']['fields']['tags'], 'tags', $exampletags, $completetags, qa_get_option($qa_db, 'page_size_ask_tags'));
			
			if ($usecaptcha)
				qa_set_up_captcha_field($qa_db, $qa_content, $qa_content['form']['fields'], @$errors);
		}
	}

?>