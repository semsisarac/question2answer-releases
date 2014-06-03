<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question-post.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: More control for question page if it's submitted by HTTP POST


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
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-update.php';


//	Process incoming answer (or button)

	if ((qa_clicked('doansweradd') || qa_clicked('doanswerq')) && $question['answerbutton'])
		switch (qa_user_permit_error($qa_db, 'permit_post_a', 'A')) {
			case 'login':
				$pageerror=qa_insert_login_links(qa_lang_html('question/answer_must_login'), $qa_request);
				break;
				
			case 'confirm':
				$pageerror=qa_insert_login_links(qa_lang_html('question/answer_must_confirm'), $qa_request);
				break;
				
			case 'limit':
				$pageerror=qa_lang_html('question/answer_limit');
				break;
			
			default:
				$pageerror=qa_lang_html('users/no_permission');
				break;
				
			case false:
				if (qa_clicked('doansweradd')) {
					$incontent=qa_post_text('content');
					$innotify=qa_post_text('notify') ? true : false;
					$inemail=qa_post_text('email');
				
					$errors=qa_answer_validate($qa_db, $incontent, $innotify, $inemail);
					
					if ($usecaptcha)
						qa_captcha_validate($qa_db, $_POST, $errors);
					
					if (empty($errors)) {
						$isduplicate=false;
						foreach ($answers as $answer)
							if (!$answer['hidden'])
								if (implode(' ', qa_string_to_words($answer['content'])) == implode(' ', qa_string_to_words($incontent)))
									$isduplicate=true;
						
						if (!$isduplicate) {
							if (!isset($qa_login_userid))
								$qa_cookieid=qa_cookie_get_create($qa_db); // create a new cookie if necessary
				
							$answerid=qa_answer_create($qa_db, $qa_login_userid, $qa_cookieid, $incontent, $innotify, $inemail, $question);
							qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_post', $questionid, $answerid, null);
							$jumptoanchor=qa_anchor('A', $answerid);
							
						} else {
							$pageerror=qa_lang_html('question/duplicate_content');
						}

					} else {
						$formtype='a_add'; // show form again
					}

				} else {
					$formtype='a_add'; // show form as if first time
				}
				break;
		}


//	Process incoming selection of the best answer
	
	if ($question['aselectable']) {
		if (qa_clicked('select_'))
			$inselect=''; // i.e. unselect current selection
		
		foreach ($answers as $answerid => $answer)
			if (qa_clicked('select_'.$answerid)) {
				$inselect=$answerid;
				break;
			}
	
		if (isset($inselect)) {
			qa_question_set_selchildid($qa_db, $qa_login_userid, $qa_cookieid, $question, strlen($inselect) ? $inselect : null, $answers);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, strlen($inselect) ? 'a_select' : 'a_unselect',
				$questionid, strlen($inselect) ? $answerid : $question['selchildid'], null);
		}
	}


//	Process hiding or showing or claiming or comment on a question
		
	if (qa_clicked('dohideq') && $question['hideable']) {
		qa_question_set_hidden($qa_db, $question, true, $qa_login_userid, $answers, $commentsfollows);
		qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_hide', $questionid, null, null);
	}
	
	if (qa_clicked('doshowq') && $question['reshowable']) {
		qa_question_set_hidden($qa_db, $question, false, $qa_login_userid, $answers, $commentsfollows);
		qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_reshow', $questionid, null, null);
	}
	
	if (qa_clicked('dodeleteq') && $question['deleteable']) {
		qa_question_delete($qa_db, $question);
		qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_delete', $questionid, null, null);
		qa_redirect(''); // redirect since question has gone
	}
	
	if (qa_clicked('doclaimq') && $question['claimable']) {
		if (qa_limits_remaining($qa_db, $qa_login_userid, 'Q')) { // already checked 'permit_post_q'
			qa_question_set_userid($qa_db, $question, $qa_login_userid);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_claim', $questionid, null, null);

		} else
			$pageerror=qa_lang_html('question/ask_limit');
	}
	

//	Process edit or save button for question

	if ($question['editbutton']) {
		if (qa_clicked('docancel'))
			;
		
		elseif (qa_clicked('doeditq') && qa_page_q_permit_edit($question, 'permit_edit_q'))
			$formtype='q_edit';
			
		elseif (qa_clicked('dosaveq') && qa_page_q_permit_edit($question, 'permit_edit_q')) {
			$incategoryid=qa_post_text('category');
			$inqtitle=qa_post_text('qtitle');
			$inqcontent=qa_post_text('qcontent');
			$inqtags=qa_post_text('qtags');
			
			$tagstring=qa_using_tags($qa_db) ? qa_tags_to_tagstring(array_unique(qa_string_to_words($inqtags))) : $question['tags'];
			$innotify=qa_post_text('notify') ? true : false;
			$inemail=qa_post_text('email');
			
			$qerrors=qa_question_validate($qa_db, $inqtitle, $inqcontent, $tagstring, $innotify, $inemail);
			
			if (empty($qerrors)) {
				$setnotify=$question['isbyuser'] ? qa_combine_notify_email($question['userid'], $innotify, $inemail) : $question['notify'];
				
				if (qa_using_categories($qa_db) && ($incategoryid!=$question['categoryid']))
					qa_question_set_category($qa_db, $question, strlen($incategoryid) ? $incategoryid : null, $qa_login_userid, $answers, $commentsfollows);
				
				qa_question_set_text($qa_db, $question, $inqtitle, $inqcontent, $tagstring, $setnotify, $qa_login_userid);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_edit', $questionid, null, null);
				
				if (qa_q_request($questionid, $question['title']) != qa_q_request($questionid, $inqtitle))
					qa_redirect(qa_q_request($questionid, $inqtitle)); // redirect if URL changed
			
			} else
				$formtype='q_edit'; // keep editing if an error
		}
		
		if ($formtype=='q_edit') { // get tags for auto-completion
			if (qa_get_option($qa_db, 'do_complete_tags'))
				$completetags=array_keys(qa_db_select_with_pending($qa_db, qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)));
			else
				$completetags=array();
		}
	}
	

//	Process adding a comment to question (shows form or processes it)

	if (qa_clicked('docommentq'))
		qa_page_q_do_comment(null);


//	Process hide, show, delete, edit, save, comment or follow-on button for answers

	foreach ($answers as $answerid => $answer) {
		if (qa_clicked('dohidea_'.$answerid) && $answer['hideable']) {
			qa_answer_set_hidden($qa_db, $answer, true, $qa_login_userid, $question, $commentsfollows);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_hide', $questionid, $answerid, null);
			$jumptoanchor=qa_anchor('A', $answerid);
		}
		
		if (qa_clicked('doshowa_'.$answerid) && $answer['reshowable']) {
			qa_answer_set_hidden($qa_db, $answer, false, $qa_login_userid, $question, $commentsfollows);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_reshow', $questionid, $answerid, null);
			$jumptoanchor=qa_anchor('A', $answerid);
		}
		
		if (qa_clicked('dodeletea_'.$answerid) && $answer['deleteable']) {
			qa_answer_delete($qa_db, $answer, $question);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_delete', $questionid, $answerid, null);
		}
		
		if (qa_clicked('doclaima_'.$answerid) && $answer['claimable']) {
			if (qa_limits_remaining($qa_db, $qa_login_userid, 'A')) { // already checked 'permit_post_a'
				qa_answer_set_userid($qa_db, $answer, $qa_login_userid);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_claim', $questionid, $answerid, null);
				$jumptoanchor=qa_anchor('A', $answerid);
			
			} else
				$pageerror=qa_lang_html('question/answer_limit');
		}
		
		if ($answer['editbutton']) {
			if (qa_clicked('docancel'))
				;
			
			elseif (qa_clicked('doedita_'.$answerid) && qa_page_q_permit_edit($answer, 'permit_edit_a')) {
				$formtype='a_edit';
				$formpostid=$answerid;
				
			} elseif (qa_clicked('dosavea_'.$answerid) && qa_page_q_permit_edit($answer, 'permit_edit_a')) {
				$inacontent=qa_post_text('acontent');
				$innotify=qa_post_text('notify') ? true : false;;
				$inemail=qa_post_text('email');
				$intocomment=qa_post_text('tocomment');
				$incommenton=qa_post_text('commenton');
				
				$aerrors=qa_answer_validate($qa_db, $inacontent, $innotify, $inemail);
				
				if (empty($aerrors)) {
					$setnotify=$answer['isbyuser'] ? qa_combine_notify_email($answer['userid'], $innotify, $inemail) : $answer['notify'];
					
					if ($intocomment && (
						(($incommenton==$questionid) && $question['commentable']) ||
						(($incommenton!=$answerid) && @$answers[$incommenton]['commentable'])
					)) { // convert to a comment
						if (qa_limits_remaining($qa_db, $qa_login_userid, 'C')) { // already checked 'permit_post_c'
							qa_answer_to_comment($qa_db, $answer, $incommenton, $inacontent, $setnotify, $qa_login_userid, $question, $answers, $commentsfollows);
							qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_to_c', $questionid, $answerid, null);
							$jumptoanchor=qa_anchor('C', $answerid);

						} else {
							$pageerror=qa_lang_html('question/comment_limit');
						}
					
					} else {
						qa_answer_set_text($qa_db, $answer, $inacontent, $setnotify, $qa_login_userid, $question);
						qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_edit', $questionid, $answerid, null);
						$jumptoanchor=qa_anchor('A', $answerid);
					}

				} else {
					$formtype='a_edit';
					$formpostid=$answerid; // keep editing if an error
				}
			}
		}
		
		if (qa_clicked('docommenta_'.$answerid))
			qa_page_q_do_comment($answer);
			
		if (qa_clicked('dofollowa_'.$answerid))
			qa_redirect('ask', array('follow' => $answerid));
	}


//	Process hide, show, delete, edit or save button for comments

	foreach ($commentsfollows as $commentid => $comment)
		if ($comment['basetype']=='C') {
			$commentanswer=@$answers[$comment['parentid']];

			if (isset($commentanswer)) {
				$commentparenttype='A';
				$commentanswerid=$commentanswer['postid'];
			
			} else {
				$commentparenttype='Q';
				$commentanswerid=null;
			}

			$commentanswer=@$answers[$comment['parentid']];
			$commentanswerid=$commentanswer['postid'];
			
			if (qa_clicked('dohidec_'.$commentid) && $comment['hideable']) {
				qa_comment_set_hidden($qa_db, $comment, true, $qa_login_userid, $question, $commentanswer);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_hide', $questionid, $commentanswerid, $commentid);
				$jumptoanchor=qa_anchor($commentparenttype, $comment['parentid']);
			}
			
			if (qa_clicked('doshowc_'.$commentid) && $comment['reshowable']) {
				qa_comment_set_hidden($qa_db, $comment, false, $qa_login_userid, $question, $commentanswer);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_reshow', $questionid, $commentanswerid, $commentid);
				$jumptoanchor=qa_anchor($commentparenttype, $comment['parentid']);
			}
			
			if (qa_clicked('dodeletec_'.$commentid) && $comment['deleteable']) {
				qa_comment_delete($qa_db, $comment);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_delete', $questionid, $commentanswerid, $commentid);
				$jumptoanchor=qa_anchor($commentparenttype, $comment['parentid']);
			}
			
			if (qa_clicked('doclaimc_'.$commentid) && $comment['claimable']) {
				if (qa_limits_remaining($qa_db, $qa_login_userid, 'C')) {
					qa_comment_set_userid($qa_db, $comment, $qa_login_userid);
					qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_claim', $questionid, $commentanswerid, $commentid);
					$jumptoanchor=qa_anchor($commentparenttype, $comment['parentid']);
					
				} else
					$pageerror=qa_lang_html('question/comment_limit');
			}
			
			if ($comment['editbutton']) {
				if (qa_clicked('docancel'))
					;
					
				elseif (qa_clicked('doeditc_'.$commentid) && qa_page_q_permit_edit($comment, 'permit_edit_c')) {
					$formtype='c_edit';
					$formpostid=$commentid;
				
				} elseif (qa_clicked('dosavec_'.$commentid) && qa_page_q_permit_edit($comment, 'permit_edit_c')) {
					$incomment=qa_post_text('comment');
					$innotify=qa_post_text('notify') ? true : false;
					$inemail=qa_post_text('email');
					
					$errors=qa_comment_validate($qa_db, $incomment, $innotify, $inemail);
					
					if (empty($errors)) {
						$setnotify=$comment['isbyuser'] ? qa_combine_notify_email($comment['userid'], $innotify, $inemail) : $comment['notify'];
						
						qa_comment_set_text($qa_db, $comment, $incomment, $setnotify, $qa_login_userid, $question, $commentanswer);
						qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_edit', $questionid, $commentanswerid, $commentid);
						
						$jumptoanchor=qa_anchor($commentparenttype, $comment['parentid']);
					
					} else {
						$formtype='c_edit';
						$formpostid=$commentid; // keep editing if an error
					}
				}
			}
		}


	function qa_page_q_permit_edit($post, $permitoption)
/*
	Return whether the editing operation (as specified by $permitoption) on $post is permitted.
	If not, set the $pageerror variable appropriately
*/
	{
		global $qa_db, $pageerror, $qa_request;
		
		$permiterror=qa_user_permit_error($qa_db, $post['isbyuser'] ? null : $permitoption);
			// if it's by the user, this will only check whether they are blocked
		
		switch ($permiterror) {
			case 'login':
				$pageerror=qa_insert_login_links(qa_lang_html('question/edit_must_login'), $qa_request);
				break;
				
			case 'confirm':
				$pageerror=qa_insert_login_links(qa_lang_html('question/edit_must_confirm'), $qa_request);
				break;
				
			default:
				$pageerror=qa_lang_html('users/no_permission');
				break;
				
			case false:
				break;
		}
		
		return !$permiterror;
	}


//	Question and answer editing forms

	function qa_page_q_edit_q_form()
/*
	Return form for editing the question and set up $qa_content accordingly
*/
	{
		global $qa_content, $qa_db, $question, $inqtitle, $inqcontent, $inqtags, $qerrors, $innotify, $inemail, $completetags, $categories;
		
		$categoryoptions=qa_category_options($qa_db, $categories);

		$form=array(
			'style' => 'tall',
			
			'fields' => array(
				'title' => array(
					'label' => qa_lang_html('question/q_title_label'),
					'tags' => ' NAME="qtitle" ',
					'value' => qa_html(isset($inqtitle) ? $inqtitle : $question['title']),
					'error' => qa_html(@$qerrors['title']),
				),
				
				'category' => array(
					'label' => qa_lang_html('question/q_category_label'),
					'tags' => ' NAME="category" ',
					'value' => @$categoryoptions[isset($incategoryid) ? $incategoryid : $question['categoryid']],
					'type' => 'select',
					'options' => $categoryoptions,
				),
				
				'content' => array(
					'label' => qa_lang_html('question/q_content_label'),
					'tags' => ' NAME="qcontent" ',
					'value' => qa_html(isset($inqcontent) ? $inqcontent : $question['content']),
					'error' => qa_html(@$qerrors['content']),
					'rows' => 12,
				),
				
				'tags' => array(
					'label' => qa_lang_html('question/q_tags_label'),
					'value' => qa_html(isset($inqtags) ? $inqtags : str_replace(',', ' ', @$question['tags'])),
					'error' => qa_html(@$qerrors['tags']),
				),

			),
			
			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html('main/save_button'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'dosaveq' => '1',
			),
		);
		
		if (!isset($categoryoptions))
			unset($form['fields']['category']);
			
		if (qa_using_tags($qa_db))
			qa_set_up_tag_field($qa_content, $form['fields']['tags'], 'qtags', array(),
				$completetags, qa_get_option($qa_db, 'page_size_ask_tags'));
		else
			unset($form['fields']['tags']);
				
		if ($question['isbyuser'])
			qa_set_up_notify_fields($qa_content, $form['fields'], 'Q', qa_get_logged_in_email($qa_db),
				isset($innotify) ? $innotify : !empty($question['notify']),
				isset($inemail) ? $inemail : @$question['notify'], @$qerrors['email']);
		
		return $form;
	}
	

	function qa_page_q_edit_a_form($answerid)
/*
	Return form for editing an answer and set up $qa_content accordingly
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';

		global $qa_db, $questionid, $question, $answers, $inacontent, $aerrors, $qa_content, $innotify, $inemail, $jumptoanchor, $commentsfollows;
		
		$answer=$answers[$answerid];
		
		$hascomments=false;
		foreach ($commentsfollows as $commentfollow)
			if ($commentfollow['parentid']==$answerid)
				$hascomments=true;
		
		$form=array(
			'title' => '<A NAME="a_edit">'.qa_lang_html('question/edit_a_title').'</A>',
			
			'style' => 'tall',
			
			'fields' => array(
				'content' => array(
					'tags' => ' NAME="acontent" ',
					'value' => qa_html(isset($inacontent) ? $inacontent : $answer['content']),
					'error' => qa_html(@$aerrors['content']),
					'rows' => 12,
				),
			),
			
			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html('main/save_button'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'dosavea_'.qa_html($answerid) => '1',
			),
		);
		
	//	Show option to convert this answer to a comment, if appropriate
		
		$commentonoptions=array();

		$lastbeforeid=$questionid; // used to find last post created before this answer - this is default given
		$lastbeforetime=$question['created'];
		
		if ($question['commentable'])
			$commentonoptions[$questionid]=
				qa_lang_html('question/comment_on_q').qa_html(qa_shorten_string_line($question['title'], 80));
		
		foreach ($answers as $otheranswer)
			if (($otheranswer['postid']!=$answerid) && ($otheranswer['created']<$answer['created']) && $otheranswer['commentable'] && !$otheranswer['hidden']) {
				$commentonoptions[$otheranswer['postid']]=
					qa_lang_html('question/comment_on_a').qa_html(qa_shorten_string_line($otheranswer['content'], 80));
				
				if ($otheranswer['created']>$lastbeforetime) {
					$lastbeforeid=$otheranswer['postid'];
					$lastebeforetime=$otheranswer['created'];
				}
			}
				
		if (count($commentonoptions)) {
			$form['fields']['tocomment']=array(
				'tags' => ' NAME="tocomment" ID="tocomment" ',
				'label' => '<SPAN ID="tocomment_shown">'.qa_lang_html('question/a_convert_to_c_on').'</SPAN>'.
								'<SPAN ID="tocomment_hidden" STYLE="display:none;">'.qa_lang_html('question/a_convert_to_c').'</SPAN>',
				'type' => 'checkbox',
				'tight' => true,
			);
			
			$form['fields']['commenton']=array(
				'tags' => ' NAME="commenton" ',
				'id' => 'commenton',
				'type' => 'select',
				'note' => qa_lang_html($hascomments ? 'question/a_convert_warn_cs' : 'question/a_convert_warn'),
				'options' => $commentonoptions,
				'value' => @$commentonoptions[$lastbeforeid],
			);
			
			qa_checkbox_to_display($qa_content, array(
				'commenton' => 'tocomment',
				'tocomment_shown' => 'tocomment',
				'tocomment_hidden' => '!tocomment',
			));
		}
		
	//	Show notification field if appropriate
		
		if ($answer['isbyuser'])
			qa_set_up_notify_fields($qa_content, $form['fields'], 'A', qa_get_logged_in_email($qa_db),
				isset($innotify) ? $innotify : !empty($answer['notify']),
				isset($inemail) ? $inemail : @$answer['notify'], @$aerrors['email']);
		
		$form['c_list']=qa_page_q_comment_follow_list($answer);
		
		$jumptoanchor='a_edit';
		
		return $form;
	}


//	Comment-related functions

	function qa_page_q_do_comment($answer)
/*
	Process an incoming new comment form for $answer, or question if it is null
*/
	{
		global $qa_db, $qa_login_userid, $qa_cookieid, $question, $questionid, $formtype, $formpostid,
			$errors, $reloadquestion, $pageerror, $qa_request, $incomment, $innotify, $inemail, $commentsfollows, $jumptoanchor, $usecaptcha;
		
		$parent=isset($answer) ? $answer : $question;
		
		if ($parent['commentbutton'])
			switch (qa_user_permit_error($qa_db, 'permit_post_c', 'C')) {
				case 'login':
					$pageerror=qa_insert_login_links(qa_lang_html('question/comment_must_login'), $qa_request);
					break;
					
				case 'confirm':
					$pageerror=qa_insert_login_links(qa_lang_html('question/comment_must_confirm'), $qa_request);
					break;
					
				case 'limit':
					$pageerror=qa_lang_html('question/comment_limit');
					break;
					
				default:
					$pageerror=qa_lang_html('users/no_permission');
					break;
					
				case false:
					$incomment=qa_post_text('comment');
		
					if (!isset($incomment)) {
						$formtype='c_add';
						$formpostid=$parent['postid']; // show form first time
					
					} else {
						$innotify=qa_post_text('notify') ? true : false;
						$inemail=qa_post_text('email');

						$errors=qa_comment_validate($qa_db, $incomment, $innotify, $inemail);
						
						if ($usecaptcha)
							qa_captcha_validate($qa_db, $_POST, $errors);

						if (empty($errors)) {
							$isduplicate=false;
							foreach ($commentsfollows as $comment)
								if (($comment['basetype']=='C') && ($comment['parentid']==$parent['postid']) && (!$comment['hidden']))
									if (implode(' ', qa_string_to_words($comment['content'])) == implode(' ', qa_string_to_words($incomment)))
										$isduplicate=true;
										
							if (!$isduplicate) {
								if (!isset($qa_login_userid))
									$qa_cookieid=qa_cookie_get_create($qa_db); // create a new cookie if necessary
								
								$commentid=qa_comment_create($qa_db, $qa_login_userid, $qa_cookieid, $incomment, $innotify, $inemail, $question, $answer, $commentsfollows);
								qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_post', $questionid, @$answer['postid'], $commentid);
	
								$jumptoanchor=qa_anchor(isset($answer) ? 'A' : 'Q', $parent['postid']);
							
							} else {
								$pageerror=qa_lang_html('question/duplicate_content');
							}
						
						} else {
							$formtype='c_add';
							$formpostid=$parent['postid']; // show form again
						}
					}
					break;
			}
	}

	
	function qa_page_q_add_c_form($answerid)
/*
	Return form for adding a comment on $answerid (or the question if $answerid is null), and set up $qa_content accordingly
*/
	{
		global $qa_content, $qa_db, $incomment, $errors, $questionid, $innotify, $inemail, $jumptoanchor, $focusonid, $usecaptcha, $qa_login_userid;
		
		$jumptoanchor=isset($answerid) ? qa_anchor('A', $answerid) : qa_anchor('Q', $questionid);
		$focusonid='comment';

		$form=array(
			'title' => qa_lang_html(isset($answerid) ? 'question/your_comment_a' : 'question/your_comment_q'),

			'style' => 'tall',
			
			'fields' => array(
				'content' => array(
					'tags' => ' NAME="comment" ID="comment" ',
					'value' => qa_html(@$incomment),
					'error' => qa_html(@$errors['content']),
					'rows' => 4,
				),
			),
			
			'buttons' => array(
				'comment' => array(
					'tags' => ' NAME="'.(isset($answerid) ? ('docommenta_'.$answerid) : 'docommentq').'" ',
					'label' => qa_lang_html('question/add_comment_button'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
		);

		qa_set_up_notify_fields($qa_content, $form['fields'], 'C', qa_get_logged_in_email($qa_db),
			isset($innotify) ? $innotify : true, @$inemail, @$errors['email']);
		
		if ($usecaptcha)
			qa_set_up_captcha_field($qa_db, $qa_content, $form['fields'], @$errors,
				qa_insert_login_links(qa_lang_html(isset($qa_login_userid) ? 'misc/captcha_confirm_fix' : 'misc/captcha_login_fix')));
				
		return $form;
	}

	
	function qa_page_q_edit_c_form($commentid, $answerid)
/*
	Return form for editing $commentid on $answerid (or the question if $answerid is null), and set up $qa_content accordingly
*/
	{
		global $qa_db, $commentsfollows, $qa_content, $errors, $incomment, $questionid, $jumptoanchor, $focusonid, $innotify, $inemail;
		
		$comment=$commentsfollows[$commentid];
		
		$jumptoanchor=isset($answerid) ? qa_anchor('A', $answerid) : qa_anchor('Q', $questionid);
		$focusonid='comment';
		
		$form=array(
			'title' => '<A NAME="edit">'.qa_lang_html('question/edit_c_title').'</A>',
			
			'style' => 'tall',
			
			'fields' => array(
				'content' => array(
					'tags' => ' NAME="comment" ID="comment" ',
					'value' => qa_html(isset($incomment) ? $incomment : $comment['content']),
					'error' => qa_html($errors['content']),
					'rows' => 4,
				),
			),
			
			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html('main/save_button'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'dosavec_'.qa_html($commentid) => '1',
			),
		);
		
		if ($comment['isbyuser'])
			qa_set_up_notify_fields($qa_content, $form['fields'], 'C', qa_get_logged_in_email($qa_db),
				isset($innotify) ? $innotify : !empty($comment['notify']),
				isset($inemail) ? $inemail : @$comment['notify'], @$errors['email']);

		return $form;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/