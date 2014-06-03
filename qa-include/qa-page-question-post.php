<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question-post.php
	Version: 1.0-beta-2
	Date: 2010-03-08 13:08:01 GMT


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

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-update.php';

//	Process incoming answer (or button)

	if ((qa_clicked('doansweradd') || qa_clicked('doanswerq')) && $question['answerable']) {
		if ($checkanswerlogin) {
			if (qa_limits_remaining($qa_db, $qa_login_userid, 'A')) {
				
				if (qa_clicked('doansweradd')) {
					$incontent=qa_post_text('content');
					$innotify=qa_post_text('notify') ? true : false;
					$inemail=qa_post_text('email');
				
					$errors=qa_answer_validate($qa_db, $incontent, $innotify, $inemail);
					
					if (empty($errors)) {				
						if (!isset($qa_login_userid))
							$qa_cookieid=qa_cookie_get_create($qa_db); // create a new cookie if necessary
			
						$answerid=qa_answer_create($qa_db, $qa_login_userid, $qa_cookieid, $incontent, $innotify, $inemail, $question);
		
						qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_post', $questionid, $answerid, null);	

					} else {
						$formtype='a_add'; // show form again
					}

				} else {
					$formtype='a_add'; // show form as if first time
				}
				
			} else {
				$pageerror=qa_lang_html('main/answer_limit');
			}

		} else {
			$pageerror=qa_insert_login_links(qa_lang_html('question/answer_must_login'), $qa_request);
		}
	}


//	Process incoming selection of the best answer
	
	if ($question['editable']) {
		if (qa_clicked('select_'))
			$inselect='';
		
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
	
	if (qa_clicked('doshowq') && $question['showable']) {
		qa_question_set_hidden($qa_db, $question, false, $qa_login_userid, $answers, $commentsfollows);
		qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_reshow', $questionid, null, null);
	}
	
	if (qa_clicked('doclaimq') && $question['claimable']) {
		qa_question_set_userid($qa_db, $question, $qa_login_userid);
		qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'q_claim', $questionid, null, null);
	}
	

//	Process edit or save button for question

	if ($question['editable']) {
		if (qa_clicked('docancel'))
			;
		
		elseif (qa_clicked('doeditq'))
			$formtype='q_edit';
			
		elseif (qa_clicked('dosaveq')) {
			$inqtitle=qa_post_text('qtitle');
			$inqcontent=qa_post_text('qcontent');
			$inqtags=qa_post_text('qtags');
			
			$tagstring=qa_tags_to_tagstring(array_unique(qa_string_to_words($inqtags)));
			$innotify=qa_post_text('notify');
			$inemail=qa_post_text('email');
			
			$qerrors=qa_question_validate($qa_db, $inqtitle, $inqcontent, $tagstring, $innotify, $inemail);
			
			if (empty($qerrors)) {
				$setnotify=$question['isbyuser'] ? qa_combine_notify_email($question['userid'], $innotify, $inemail) : $question['notify'];
				
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


//	Process hide, show, edit, save, comment or follow-on button for answers

	foreach ($answers as $answerid => $answer) {
		if (qa_clicked('dohidea_'.$answerid) && $answer['hideable']) {
			qa_answer_set_hidden($qa_db, $answer, true, $qa_login_userid, $question, $commentsfollows);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_hide', $questionid, $answerid, null);
		}
		
		if (qa_clicked('doshowa_'.$answerid) && $answer['showable']) {
			qa_answer_set_hidden($qa_db, $answer, false, $qa_login_userid, $question, $commentsfollows);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_reshow', $questionid, $answerid, null);
		}
		
		if (qa_clicked('doclaima_'.$answerid) && $answer['claimable']) {
			qa_answer_set_userid($qa_db, $answer, $qa_login_userid);
			qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_claim', $questionid, $answerid, null);
		}
		
		if ($answer['editable']) {
			if (qa_clicked('docancel'))
				;
			
			elseif (qa_clicked('doedita_'.$answerid)) {
				$formtype='a_edit';
				$formpostid=$answerid;
				
			} elseif (qa_clicked('dosavea_'.$answerid)) {
				$inacontent=qa_post_text('acontent');
				$innotify=qa_post_text('notify');
				$inemail=qa_post_text('email');
				
				$aerrors=qa_answer_validate($qa_db, $inacontent, $innotify, $inemail);
				
				if (empty($aerrors)) {
					$setnotify=$answer['isbyuser'] ? qa_combine_notify_email($answer['userid'], $innotify, $inemail) : $answer['notify'];
					
					qa_answer_set_text($qa_db, $answer, $inacontent, $setnotify, $qa_login_userid, $question);
					qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'a_edit', $questionid, $answerid, null);

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


//	Process hide, show, edit or save button for comments

	foreach ($commentsfollows as $commentid => $comment)
		if ($comment['basetype']=='C') {
			$commentanswer=@$answers[$comment['parentid']];
			$commentanswerid=$commentanswer['postid'];
			
			if (qa_clicked('dohidec_'.$commentid) && $comment['hideable']) {
				qa_comment_set_hidden($qa_db, $comment, true, $qa_login_userid, $question, $commentanswer);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_hide', $questionid, $commentanswerid, $commentid);
			}
			
			if (qa_clicked('doshowc_'.$commentid) && $comment['showable']) {
				qa_comment_set_hidden($qa_db, $comment, false, $qa_login_userid, $question, $commentanswer);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_reshow', $questionid, $commentanswerid, $commentid);
			}
			
			if (qa_clicked('doclaimc_'.$commentid) && $comment['claimable']) {
				qa_comment_set_userid($qa_db, $comment, $qa_login_userid);
				qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_claim', $questionid, $commentanswerid, $commentid);
			}
			
			if ($comment['editable']) {
				if (qa_clicked('docancel'))
					;
					
				elseif (qa_clicked('doeditc_'.$commentid)) {
					$formtype='c_edit';
					$formpostid=$commentid;
				
				} elseif (qa_clicked('dosavec_'.$commentid)) {
					$incomment=qa_post_text('comment');
					$innotify=qa_post_text('notify') ? true : false;
					$inemail=qa_post_text('email');
					
					$errors=qa_comment_validate($qa_db, $incomment, $innotify, $inemail);
					
					if (empty($errors)) {
						$setnotify=$comment['isbyuser'] ? qa_combine_notify_email($comment['userid'], $innotify, $inemail) : $comment['notify'];
						
						qa_comment_set_text($qa_db, $comment, $incomment, $setnotify, $qa_login_userid, $question, $commentanswer);
						qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_edit', $questionid, $commentanswerid, $commentid);
					
					} else {
						$formtype='c_edit';
						$formpostid=$commentid; // keep editing if an error
					}
				}
			}
		}


//	Comment-related functions

	function qa_page_q_do_comment($answer=null)
	{
		global $qa_db, $qa_login_userid, $qa_cookieid, $question, $questionid, $checkcommentlogin, $formtype, $formpostid,
			$errors, $reloadquestion, $pageerror, $qa_request, $incomment, $innotify, $inemail, $commentsfollows;
		
		$parent=isset($answer) ? $answer : $question;
		
		if ($parent['commentable']) {
			if ($checkcommentlogin) {
				if (qa_limits_remaining($qa_db, $qa_login_userid, 'C')) {
					$incomment=qa_post_text('comment');
					$innotify=qa_post_text('notify') ? true : false;
					$inemail=qa_post_text('email');
		
					if (!isset($incomment)) {
						$formtype='c_add';
						$formpostid=$parent['postid']; // show form first time
					
					} else {
						$errors=qa_comment_validate($qa_db, $incomment, $innotify, $inemail);
						
						if (empty($errors)) {
							if (!isset($qa_login_userid))
								$qa_cookieid=qa_cookie_get_create($qa_db); // create a new cookie if necessary
							
							$commentid=qa_comment_create($qa_db, $qa_login_userid, $qa_cookieid, $incomment, $innotify, $inemail, $question, $answer, $commentsfollows);
							qa_report_write_action($qa_db, $qa_login_userid, $qa_cookieid, 'c_post', $questionid, @$answer['postid'], $commentid);
						
						} else {
							$formtype='c_add';
							$formpostid=$parent['postid']; // show form again
						}
					}
				
				} else {
					$pageerror=qa_lang_html('main/comment_limit');
				}
	
			} else {
				$pageerror=qa_insert_login_links(qa_lang_html('question/comment_must_login'), $qa_request);
			}
		}
	}
	
	function qa_page_q_add_c_form($answerid)
	{
		global $qa_content, $incomment, $errors, $questionid, $qa_login_email, $innotify, $inemail;
		
		$qa_content['script_onloads'][]=array(
			"window.location.hash=".qa_js(isset($answerid) ? $answerid : $questionid).";",
			"document.getElementById('comment').focus();"
		);

		$form=array(
			'title' => qa_lang_html(isset($answerid) ? 'question/your_comment_a' : 'question/your_comment_q'),

			'style' => 'tall',
			
			'fields' => array(
				'content' => array(
					'tags' => ' NAME="comment" ID="comment" ',
					'value' => qa_html(@$incomment),
					'error' => qa_html(@$errors['content']),
					'rows' => 2,
				),
			),
			
			'buttons' => array(
				'comment' => array(
					'tags' => ' NAME="'.(isset($answerid) ? ('docommenta_'.$answerid) : 'docommentq').'" ',
					'label' => qa_lang_html('question/add_comment_button'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('question/cancel_button'),
				),
			),
		);		

		qa_set_up_notify_fields($qa_content, $form['fields'], 'C', $qa_login_email,
			isset($innotify) ? $innotify : true, @$inemail, @$errors['email']);
				
		return $form;
	}
	
	function qa_page_q_edit_c_form($commentid, $answerid)
	{
		global $commentsfollows, $qa_content, $errors, $incomment, $questionid, $qa_login_email;
		
		$comment=$commentsfollows[$commentid];
		
		$qa_content['script_onloads'][]=array(
			"window.location.hash=".qa_js(isset($answerid) ? $answerid : $questionid).";",
			"document.getElementById('comment').focus();"
		);
		
		$form=array(
			'title' => '<A NAME="edit">'.qa_lang_html('question/edit_c_title').'</A>',
			
			'style' => 'tall',
			
			'fields' => array(
				'content' => array(
					'tags' => ' NAME="comment" ID="comment" ',
					'value' => qa_html(isset($incomment) ? $incomment : $comment['content']),
					'error' => qa_html($errors['content']),
					'rows' => 2,
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
				'dosavec_'.qa_html($commentid) => '1',
			),
		);
		
		if ($comment['isbyuser'])
			qa_set_up_notify_fields($qa_content, $form['fields'], 'C', $qa_login_email,
				isset($innotify) ? $innotify : !empty($comment['notify']),
				isset($inemail) ? $inemail : @$comment['notify'], @$errors['email']);

		return $form;
	}
	
?>