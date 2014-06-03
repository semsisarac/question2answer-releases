<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question.php
	Version: 1.0-beta-1
	Date: 2010-02-04 14:10:15 GMT


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

	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-sort.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
	$questionid=$pass_questionid; // picked up from index.php

//	Get information about this question

	qa_options_set_pending(array('answer_needs_login', 'do_related_qs', 'page_size_related_qs', 'match_related_qs',
		'page_size_ask_tags', 'do_complete_tags', 'show_url_links'));
	
	list($questions, $answers, $relatedquestions)=qa_db_select_with_pending($qa_db,
		qa_db_full_q_selectspec($qa_login_userid, $questionid),
		qa_db_full_as_selectspec($qa_login_userid, $questionid),
		qa_db_related_qs_selectspec($qa_login_userid, $questionid)
	);

	if (!count($questions)) {
		qa_content_prepare();
		$qa_content['title']=$qa_content['error']=qa_lang_html('question/q_not_found');
		return;
	}
	
	$relatedcount=qa_get_option($qa_db, 'do_related_qs') ? (1+qa_get_option($qa_db, 'page_size_related_qs')) : 0;
	$relatedquestions=array_slice($relatedquestions, 0, $relatedcount); // includes question itself at this point
	
	$question=$questions[$questionid];
	$questionbyuser=qa_post_is_by_user($question, $qa_login_userid, $qa_cookieid);
	$questionadmin=($qa_login_level>=QA_USER_LEVEL_EDITOR) || $questionbyuser;
	$questionhidden=$question['type']=='Q_HIDDEN';
	$editpostid=null;
	$reloadquestion=false;
	$useranswered=false;
				
//	Process an incoming answer

	if (qa_clicked('doanswer')) {
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		if (qa_get_option($qa_db, 'answer_needs_login') && !isset($qa_login_userid)) {
			; // do nothing
			
		} elseif (qa_limits_remaining($qa_db, $qa_login_userid, 'A')) {
			require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	
			$incontent=qa_post_text('content');
			
			$errors=qa_answer_validate($qa_db, $incontent);
			
			if (empty($errors)) {
				require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
				require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	
				if (!isset($qa_login_userid))
					$qa_cookieid=qa_cookie_get_create($qa_db); // create a new cookie if necessary
	
				$answerid=qa_answer_create($qa_db, $questionid, $qa_login_userid, $qa_cookieid, $incontent,
					$question['title'], $question['userid'], $question['notify'], $questionhidden);

				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_post', $questionid, $answerid);

				$reloadquestion=true;
				$useranswered=true;
			}

		} else {
			$qa_content['error']=qa_lang_html('main/answer_limit');
		}
	}
		
//	Process incoming selection of the best answer
	
	if ($questionadmin) {
		if (qa_clicked('select_'))
			$inselect='';
		
		foreach ($answers as $answerid => $answer)
			if (qa_clicked('select_'.$answerid)) {
				$inselect=$answerid;
				break;
			}
	
		if (isset($inselect)) {
			require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';

			qa_question_set_selchildid($qa_db, $questionid, $question, strlen($inselect) ? $inselect : null, $answers);
			
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, strlen($inselect) ? 'a_select' : 'a_unselect',
				$questionid, strlen($inselect) ? $answerid : $question['selchildid']);
			
			$reloadquestion=true;
		}
	}

//	Process hiding or re-showing question
		
	if ($questionadmin) {
		if (qa_clicked('dohide')) {
			require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
			qa_question_set_hidden($qa_db, $questionid, true, $question['userid'], $question['title'], $question['content'], $question['tags'], $answers);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_hide', $questionid, null);
			
			$reloadquestion=true;
		}
		
		if (qa_clicked('doshow')) {
			require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
			qa_question_set_hidden($qa_db, $questionid, false, $question['userid'], $question['title'], $question['content'], $question['tags'], $answers);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_reshow', $questionid, null);

			$reloadquestion=true;
		}
	}

//	Process edit or save button for question

	if ($questionadmin) {
		if (qa_clicked('docancel'))
			;
		
		elseif (qa_clicked('doeditq'))
			$editpostid=$questionid;
			
		elseif (qa_clicked('dosaveq')) {
			require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
			
			$inqtitle=qa_post_text('qtitle');
			$inqcontent=qa_post_text('qcontent');
			$inqtags=qa_post_text('qtags');
			
			$tagstring=qa_tags_to_tagstring(array_unique(qa_string_to_words($inqtags)));
			$innotify=qa_post_text('notify');
			$inemail=qa_post_text('email');
			
			$qerrors=qa_question_validate($qa_db, $inqtitle, $inqcontent, $tagstring);
			
			if ($innotify && !empty($inemail)) {
				if (!qa_email_validate($inemail))
					$qerrors['email']=qa_lang('users/email_invalid');
				elseif (qa_strlen($inemail)>QA_DB_MAX_EMAIL_LENGTH)
					$qerrors['email']=qa_lang_sub('main/max_length_x', QA_DB_MAX_EMAIL_LENGTH);
			}

			if (empty($qerrors)) {
				require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
				require_once QA_INCLUDE_DIR.'qa-app-limits.php';
				
				$setnotify=$questionbyuser ? ($innotify ? (empty($inemail) ? '@' : $inemail) : null) : $question['notify'];
				
				qa_question_set_text($qa_db, $questionid, $questionhidden, $inqtitle, $inqcontent, $tagstring, $setnotify);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_edit', $questionid, null);
				
				if (qa_q_request($questionid, $question['title']) != qa_q_request($questionid, $inqtitle))
					qa_redirect(qa_q_request($questionid, $inqtitle)); // redirect if URL changed
				else
					$reloadquestion=true; // otherwise just reload question
			
			} else 
				$editpostid=$questionid; // keep editing if an error
		}
		
		if ($editpostid==$questionid) { // get tags for auto-completion
			if (qa_get_option($qa_db, 'do_complete_tags'))
				$completetags=array_keys(qa_db_select_with_pending($qa_db, qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)));
			else
				$completetags=array();
		}
	}

//	Process edit or save button for any appropriate answer

	foreach ($answers as $answerid => $answer)
		if (($qa_login_level>=QA_USER_LEVEL_EDITOR) || qa_post_is_by_user($answer, $qa_login_userid, $qa_cookieid)) {

			if (qa_clicked('docancel'))
				;
			
			elseif (qa_clicked('doedita_'.$answerid)) {
				$editpostid=$answerid;
				
			} elseif (qa_clicked('dosavea_'.$answerid)) {
				require_once QA_INCLUDE_DIR.'qa-app-post-create.php';

				$inacontent=qa_post_text('acontent');
				
				$aerrors=qa_answer_validate($qa_db, $inacontent);
				
				if (empty($aerrors)) {
					require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
					require_once QA_INCLUDE_DIR.'qa-app-limits.php';
					
					qa_answer_set_text($qa_db, $answerid, $answer['type']=='A_HIDDEN', $inacontent, $questionhidden);
					qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_edit', $questionid, $answerid);
					
					$reloadquestion=true;

				} else
					$editpostid=$answerid; // keep editing if an error
				
			} elseif (qa_clicked('dohidea_'.$answerid)) {
				require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
				require_once QA_INCLUDE_DIR.'qa-app-limits.php';

				qa_answer_set_hidden($qa_db, $questionid, $answerid, true, $answer['userid'], $answer['content'], $questionhidden);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_hide', $questionid, $answerid);
				
				$reloadquestion=true;
				
			} elseif (qa_clicked('doshowa_'.$answerid)) {
				require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
				require_once QA_INCLUDE_DIR.'qa-app-limits.php';

				qa_answer_set_hidden($qa_db, $questionid, $answerid, false, $answer['userid'], $answer['content'], $questionhidden);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_reshow', $questionid, $answerid);
				
				$reloadquestion=true;
			}
			
		}

//	Reload the question if need be, and get further information

	if ($reloadquestion) {
		list($questions, $answers, $relatedquestions)=qa_db_select_with_pending($qa_db,
			qa_db_full_q_selectspec($qa_login_userid, $questionid),
			qa_db_full_as_selectspec($qa_login_userid, $questionid),
			qa_db_related_qs_selectspec($qa_login_userid, $questionid) // could've used $relatedcount here but kept it simple
		);
		
		$relatedquestions=array_slice($relatedquestions, 0, $relatedcount); // includes question itself at this point

		$question=$questions[$questionid];
		$questionhidden=$question['type']=='Q_HIDDEN';
	}
	
	if ($question['selchildid'] && (@$answers[$question['selchildid']]['type']!='A'))
		$question['selchildid']=null; // if selected answer is hidden, consider it not selected
	
	$usershtml=qa_userids_handles_html($qa_db, array_merge($questions, $answers, $relatedquestions), true);
	
//	Prepare content for theme
	
	qa_content_prepare(true);
	
	$qa_content['form_tags']=' METHOD="POST" ACTION="'.qa_self_html().'" ';
	
	if ($questionhidden)
		$qa_content['hidden']=true;
	
	{//	The question...
		
		if ($editpostid==$questionid) { // ...in edit mode

			$qa_content['title']=qa_lang_html('question/edit_q_title');
			
			$qa_content['q_edit_form']=array(
				'style' => 'tall',
				
				'fields' => array(
					'title' => array(
						'label' => qa_lang_html('question/q_title_label'),
						'tags' => ' NAME="qtitle" ',
						'value' => qa_html(isset($inqtitle) ? $inqtitle : $question['title']),
						'error' => qa_html(@$qerrors['title']),
					),
					
					'content' => array(
						'label' => qa_lang_html('question/q_content_label'),
						'tags' => ' NAME="qcontent" ',
						'value' => qa_html(isset($inqcontent) ? $inqcontent : $question['content']),
						'error' => qa_html(@$qerrors['content']),
						'rows' => 20,
					),
					
					'tags' => array(
						'label' => qa_lang_html('question/q_tags_label'),
						'value' => qa_html(isset($inqtags) ? $inqtags : str_replace(',', ' ', @$question['tags'])),
						'error' => qa_html(@$qerrors['tags']),
					),

				),
				
				'buttons' => array(
					'save' => array(
						'label' => qa_lang_html('question/save_button'),
					),
					
					'cancel' => array(
						'tags' => ' NAME="docancel" ',
						'label' => qa_lang_html('question/cancel_button'),
					),
				),
				
				'hidden' => array(
					'dosaveq' => '1',
				),
			);
			
			if ($questionbyuser)
				qa_set_up_notify_fields($qa_content, $qa_content['q_edit_form']['fields'],
					QA_EXTERNAL_USERS ? qa_get_user_email($qa_db, $qa_login_userid) : $qa_login_email,
					isset($innotify) ? $innotify : !empty($question['notify']),
					isset($inemail) ? $inemail : @$question['notify'], @$qerrors['email']);
			
			qa_set_up_tag_field($qa_content, $qa_content['q_edit_form']['fields']['tags'], 'qtags', array(),
				$completetags, qa_get_option($qa_db, 'page_size_ask_tags'));
		
		} else { // ...in view mode

			$qa_content['q_view']=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, !$questionhidden, 
				qa_get_option($qa_db, 'show_url_links'), true);
			
			$qa_content['title']=$qa_content['q_view']['title'];
			
			unset($qa_content['q_view']['answers']); // displayed separately

			if ($questionadmin) {

				if ($questionhidden) {
					$qa_content['q_view']['form']=array(
						'style' => 'basic',
						
						'buttons' => array(
							'reshow' => array(
								'tags' => ' NAME="doshow" ',
								'label' => qa_lang_html('question/reshow_question'),
								'note' => qa_lang_html('question/q_hidden'),
							),
						),
					);
					
				} else {
					$qa_content['q_view']['form']=array(
						'style' => 'basic',

						'buttons' => array(
							'edit' => array(
								'tags' => ' NAME="doeditq" ',
								'label' => qa_lang_html('question/edit_q_button'),
							),
							
							'hide' => array(
								'tags' => ' NAME="dohide" ',
								'label' => qa_lang_html('question/hide_question'),
							),
						),
					);
				}
			}
			
		}
	}
	

	if ( isset($editpostid) && ($editpostid!=$questionid) )

	{// Answer being edited

		$answer=$answers[$editpostid];
		
		$qa_content['a_edit_form']=array(
			'title' => qa_lang_html('question/edit_a_title'),
			
			'style' => 'tall',
			
			'fields' => array(
				'content' => array(
					'tags' => ' NAME="acontent" ',
					'value' => qa_html(isset($inacontent) ? $inacontent : $answer['content']),
					'error' => qa_html(@$aerrors['content']),
					'rows' => 20,
				),
			),
			
			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html('question/save_button'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('question/cancel_button'),
				),
			),
			
			'hidden' => array(
				'dosavea_'.qa_html($editpostid) => '1',
			),
		);
	}


	{// Existing answers

		$qa_content['a_list']['as']=array();
		
		qa_sort_by($answers, 'created');
		$answers=array_reverse($answers, true);
		$priority=0;
	
		foreach ($answers as $answerid => $answer)
			if (
				($answerid!=$editpostid) &&
				( ($answer['type']=='A') || ($qa_login_level>=QA_USER_LEVEL_EDITOR) || qa_post_is_by_user($answer, $qa_login_userid, $qa_cookieid))
			) {
				$answerhidden=$answer['type']=='A_HIDDEN';
				$isselected=($question['selchildid']==$answerid);
				
				$a_view=qa_post_html_fields($answer, $qa_login_userid, $qa_cookieid, $usershtml, !$answerhidden,
					qa_get_option($qa_db, 'show_url_links'), true, $isselected);
				
				$a_view['selected']=$isselected;
				
				if (!isset($editpostid)) {

					if ($questionadmin && (!$answerhidden) && ($a_view['selected'] || !isset($question['selchildid'])) ) {
						$a_view['unselect_tags']=' TITLE="'.qa_lang_html('question/unselect_popup').'" NAME="select_" ';
						$a_view['select_tags']=' TITLE="'.qa_lang_html('question/select_popup').'" NAME="select_'.qa_html($answerid).'" ';
						$a_view[$a_view['selected'] ? 'select_tags' : 'unselect_tags'].='STYLE="display:none;" ';
					}
					
					if (($qa_login_level>=QA_USER_LEVEL_EDITOR) || qa_post_is_by_user($answer, $qa_login_userid, $qa_cookieid)) {
						if ($answerhidden) {
							$a_view['form']=array(
								'style' => 'basic',

								'buttons' => array(
									'reshow' => array(
										'tags' => ' NAME="doshowa_'.qa_html($answerid).'" ',
										'label' => qa_lang_html('question/reshow_answer'),
										'note' => qa_lang_html('question/a_hidden'),
									),
								),
							);
						
						} else {
							$a_view['form']=array(
								'style' => 'basic',

								'buttons' => array(
									'edit' => array(
										'tags' => ' NAME="doedita_'.qa_html($answerid).'" ',
										'label' => qa_lang_html('question/edit_a_button'),
									),

									'hide' => array(
										'tags' => ' NAME="dohidea_'.qa_html($answerid).'" ',
										'label' => qa_lang_html('question/hide_answer'),
									),
								),
							);
						}
					}
				}
				
				if ($answerhidden)
					$a_view['priority']=10000+($priority++);
				elseif ($a_view['selected'])
					$a_view['priority']=0;
				else
					$a_view['priority']=5000+($priority++);
					
				$qa_content['a_list']['as'][]=$a_view;
	
				if (qa_post_is_by_user($answer, $qa_login_userid, $qa_cookieid))
					$useranswered=true;
			}
			
		qa_sort_by($qa_content['a_list']['as'], 'priority');
		
		$countanswers=$question['acount'];
		
		if ($countanswers==1)
			$qa_content['a_list']['title']=qa_lang_html('question/1_answer_title');
		else
			$qa_content['a_list']['title']=qa_lang_sub_html('question/x_answers_title', $countanswers);
	}


	{// Form for adding answers

		if ($questionhidden) {
			$qa_content['a_add_form']=array(
				'style' => 'tall',
				'title' => qa_lang_html('question/q_hidden'),
			);
			
		} elseif ( (!$useranswered) && !isset($editpostid) ) {
			if (qa_get_option($qa_db, 'answer_needs_login') && !isset($qa_login_userid)) {
				$qa_content['a_add_form']=array(
					'style' => 'tall',
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_login'), $qa_request)
				);
			
			} else {				
				$qa_content['a_add_form']=array(
					'title' => qa_lang_html('question/your_answer_title'),
					
					'style' => 'tall',
					
					'fields' => array(
						'content' => array(
							'tags' => ' NAME="content" ',
							'value' => qa_html(@$incontent),
							'error' => qa_html(@$errors['content']),
							'rows' => 12,
						),
					),
					
					'buttons' => array(
						'answer' => array(
							'tags' => ' NAME="doanswer" ',
							'label' => qa_lang_html('question/answer_button'),
						),
					),
				);
			}
		}
	}
	
	
	if (($relatedcount>1) && !$questionhidden) {// Related questions
		$minscore=qa_match_to_min_score(qa_get_option($qa_db, 'match_related_qs'));
		
		foreach ($relatedquestions as $key => $related)
			if ( ($related['postid']==$questionid) || ($related['score']<$minscore) )
				unset($relatedquestions[$key]);
		
		if (count($relatedquestions))
			$qa_content['related_q_list']['title']=qa_lang('main/related_qs_title');
		else
			$qa_content['related_q_list']['title']=qa_lang('main/no_related_qs_title');
			
		$qa_content['related_q_list']['qs']=array();
		foreach ($relatedquestions as $related)
			$qa_content['related_q_list']['qs'][]=qa_post_html_fields($related, $qa_login_userid, $qa_cookieid, $usershtml, true);
	}

?>