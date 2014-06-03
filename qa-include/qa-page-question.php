<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question.php
	Version: 1.0-beta-3
	Date: 2010-03-31 12:13:41 GMT


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

	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-sort.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	
	$questionid=$pass_questionid; // picked up from index.php

//	Get information about this question

	function qa_page_q_load_q()
	{
		global $qa_db, $qa_login_userid, $questionid, $question, $parentquestion, $answers, $commentsfollows,
			$relatedcount, $relatedquestions, $question, $useranswered, $checkanswerlogin, $checkcommentlogin;

		list($question, $childposts, $achildposts, $parentquestion, $relatedquestions)=qa_db_select_with_pending($qa_db,
			qa_db_full_post_selectspec($qa_login_userid, $questionid),
			qa_db_full_child_posts_selectspec($qa_login_userid, $questionid),
			qa_db_full_a_child_posts_selectspec($qa_login_userid, $questionid),
			qa_db_post_parent_q_selectspec($questionid),
			qa_db_related_qs_selectspec($qa_login_userid, $questionid)
		);
		
		if ($question['basetype']!='Q') // don't allow direct viewing of other types of post
			$question=null;

		$answers=array();
		$commentsfollows=array();
		
		foreach ($childposts as $postid => $post)
			switch ($post['type']) {
				case 'Q': // never show follow-on Qs which have been hidden, even to admins
				case 'C':
				case 'C_HIDDEN':
					$commentsfollows[$postid]=$post;
					break;
					
				case 'A':
				case 'A_HIDDEN':
					$answers[$postid]=$post;
					break;
			}
		
		foreach ($achildposts as $postid => $post)
			switch ($post['type']) {
				case 'Q': // never show follow-on Qs which have been hidden, even to admins
				case 'C':
				case 'C_HIDDEN':
					$commentsfollows[$postid]=$post;
					break;
			}
		
		if (isset($question)) {
			$relatedcount=qa_get_option($qa_db, 'do_related_qs') ? (1+qa_get_option($qa_db, 'page_size_related_qs')) : 0;
			$relatedquestions=array_slice($relatedquestions, 0, $relatedcount); // includes question itself at this point

			qa_page_q_post_rules($question);
	
			if ($question['selchildid'] && (@$answers[$question['selchildid']]['type']!='A'))
				$question['selchildid']=null; // if selected answer is hidden or somehow not there, consider it not selected

			$useranswered=false;
			
			foreach ($answers as $key => $answer) {
				qa_page_q_post_rules($answers[$key]);
				if ($answers[$key]['isbyuser'])
					$useranswered=true;
				
				$answers[$key]['isselected']=($answer['postid']==$question['selchildid']);
			}
	
			foreach ($commentsfollows as $key => $commentfollow)
				qa_page_q_post_rules($commentsfollows[$key]);
		}
	}
	
	function qa_page_q_post_rules(&$post)
	{
		global $qa_db, $qa_login_userid, $qa_cookieid, $qa_login_level;

		$post['isbyuser']=qa_post_is_by_user($post, $qa_login_userid, $qa_cookieid);
		$post['authorlast']=(($post['lastuserid']===$post['userid']) || !isset($post['lastuserid']));
		$post['viewable']=( ($qa_login_level>=QA_USER_LEVEL_EDITOR) || $post['isbyuser']) || !$post['hidden'];
		$post['editable']=( ($qa_login_level>=QA_USER_LEVEL_EDITOR) || $post['isbyuser']) && !$post['hidden'];
		$post['hideable']=$post['editable'];
		$post['showable']=$post['hidden'] && ( ($qa_login_level>=QA_USER_LEVEL_EDITOR) ||
			($post['isbyuser'] && $post['authorlast']) ); // can only reshow a question if you're the one who hid it
		$post['claimable']=(!isset($post['userid'])) && isset($qa_login_userid) && (@$post['cookieid']==$qa_cookieid);
		$post['answerable']=($post['type']=='Q');
		$post['commentable']=($post['type']=='Q') ? qa_get_option($qa_db, 'comment_on_qs') :
			(($post['type']=='A') ? qa_get_option($qa_db, 'comment_on_as') : false);
		$post['followable']=($post['type']=='A') ? qa_get_option($qa_db, 'follow_on_as') : false;
	}
	
	function qa_page_q_comment_follow_list($parent)
	{
		global $qa_db, $commentsfollows, $qa_login_userid, $qa_cookieid, $usershtml, $formtype, $formpostid, $formrequested;
		
		foreach ($commentsfollows as $commentfollowid => $commentfollow)
			if (($commentfollow['parentid']==$parent['postid']) && $commentfollow['viewable'] && ($commentfollowid!=$formpostid) ) {
				if ($commentfollow['basetype']=='C') {
					$c_view=qa_post_html_fields($commentfollow, $qa_login_userid, $qa_cookieid, $usershtml,
						false, qa_get_option($qa_db, 'show_user_points'), qa_get_option($qa_db, 'show_url_links'), true);
						
					if (!$formrequested) {
						$c_view['form']=array(
							'style' => 'light',
							'buttons' => array(),
						);
					
						if ($commentfollow['editable'])
							$c_view['form']['buttons']['edit']=array(
								'tags' => ' NAME="doeditc_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/edit_button'),
								'popup' => qa_lang_html('question/edit_c_popup'),
							);
							
						if ($commentfollow['hideable'])
							$c_view['form']['buttons']['hide']=array(
								'tags' => ' NAME="dohidec_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/hide_button'),
								'popup' => qa_lang_html('question/hide_c_popup'),
							);
							
						if ($commentfollow['showable'])
							$c_view['form']['buttons']['reshow']=array(
								'tags' => ' NAME="doshowc_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/reshow_button'),
								'popup' => qa_lang_html($commentfollow['authorlast'] ? 'question/c_hidden_author' : 'question/c_hidden_editor'),
							);
							
						if ($commentfollow['claimable'])
							$c_view['form']['buttons']['claim']=array(
								'tags' => ' NAME="doclaimc_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/claim_button'),
							);
							
						if ($parent['commentable'])
							$c_view['form']['buttons']['comment']=array(
								'tags' => ' NAME="'.(($parent['basetype']=='Q') ? 'docommentq' : ('docommenta_'.qa_html($parent['postid']))).'" ',
								'label' => qa_lang_html('question/comment_button'),
							);

					}

				} elseif ($commentfollow['basetype']=='Q') {
					$c_view=qa_post_html_fields($commentfollow, $qa_login_userid, $qa_cookieid, $usershtml);
				}

				$commentlist[]=$c_view;
			}
			
		return @$commentlist;
	}


//	Get information about this question

	qa_options_set_pending(array('answer_needs_login', 'do_related_qs', 'page_size_related_qs', 'match_related_qs',
		'page_size_ask_tags', 'do_complete_tags', 'show_url_links', 'voting_on_qs', 'voting_on_as', 'votes_separated',
		'comment_on_qs', 'comment_on_as', 'follow_on_as', 'comment_needs_login', 'captcha_on_anon_post', 'show_user_points'));
	qa_captcha_pending();
	
	qa_page_q_load_q();

//	Deal with question not found or not viewable

	if ((!isset($question)) || !$question['viewable']) {
		qa_content_prepare();

		$qa_content['error']=qa_lang_html(isset($question)
			? ($question['authorlast'] ? 'question/q_hidden_author' : 'question/q_hidden_editor')
			: 'question/q_not_found'
		);

		$qa_content['suggest_next']=qa_html_suggest_qs_tags();

		return;
	}
		
//	Check permission for answering and commening

	$checkanswerlogin=isset($qa_login_userid) || !qa_get_option($qa_db, 'answer_needs_login');			
	$checkcommentlogin=isset($qa_login_userid) || !qa_get_option($qa_db, 'comment_needs_login');
	$usecaptcha=(!isset($qa_login_userid)) && qa_get_option($qa_db, 'captcha_on_anon_post');
	
//	If we're responding to an HTTP POST, include file that handles all posting/editing/etc... logic
//	This is in a separate file because it's a *lot* of logic, and will slow down ordinary page views

	$pageerror=null;
	$formtype=null;
	$formpostid=null;
	$jumptohash=null;
	$focusonid=null;
	
	if (qa_is_http_post()) {
		require QA_INCLUDE_DIR.'qa-page-question-post.php';
		qa_page_q_load_q(); // reload since we may have changed something
	}
	
	$formrequested=isset($formtype);
	
	if ((!$formrequested) && $question['answerable'] && (!$question['acount']) && (!$question['isbyuser']) )
		$formtype='a_add'; // show answer form by default under certain conditions
	
//	Get information on the users referenced

	$usershtml=qa_userids_handles_html($qa_db, array_merge(array($question), $answers, $commentsfollows, $relatedquestions), true);
	
//	Prepare content for theme
	
	qa_content_prepare(true);
	
	$qa_content['form_tags']=' METHOD="POST" ACTION="'.qa_self_html().'" ';
	
	if (isset($pageerror))
		$qa_content['error']=$pageerror; // might also show voting error set in qa-index.php
	
	if ($question['hidden'])
		$qa_content['hidden']=true;
	
	qa_sort_by($commentsfollows, 'created');

	{//	The question...
		
		if ($formtype=='q_edit') { // ...in edit mode
			$qa_content['title']=qa_lang_html('question/edit_q_title');			
			$qa_content['q_edit_form']=qa_page_q_edit_q_form();

		} else { // ...in view mode
			$qa_content['q_view']=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_get_vote_view($qa_db, 'Q'), qa_get_option($qa_db, 'show_user_points'), qa_get_option($qa_db, 'show_url_links'), true);
			
			$qa_content['title']=$qa_content['q_view']['title'];
			
			unset($qa_content['q_view']['answers']); // displayed separately
			
			if (!$formrequested) {
				$qa_content['q_view']['form']=array(
					'style' => 'light',
					'buttons' => array(),
				);
				
				if ($question['editable'])
					$qa_content['q_view']['form']['buttons']['edit']=array(
						'tags' => ' NAME="doeditq" ',
						'label' => qa_lang_html('question/edit_button'),
						'popup' => qa_lang_html('question/edit_q_popup'),
					);
					
				if ($question['hideable'])
					$qa_content['q_view']['form']['buttons']['hide']=array(
						'tags' => ' NAME="dohideq" ',
						'label' => qa_lang_html('question/hide_button'),
						'popup' => qa_lang_html('question/hide_q_popup'),
					);
					
				if ($question['showable'])
					$qa_content['q_view']['form']['buttons']['reshow']=array(
						'tags' => ' NAME="doshowq" ',
						'label' => qa_lang_html('question/reshow_button'),
						'popup' => qa_lang_html($question['authorlast'] ? 'question/q_hidden_author' : 'question/q_hidden_editor'),
					);
					
				if ($question['claimable'])
					$qa_content['q_view']['form']['buttons']['claim']=array(
						'tags' => ' NAME="doclaimq" ',
						'label' => qa_lang_html('question/claim_button'),
					);
				
				if ($question['answerable'] && ($formtype!='a_add')) // don't show if shown by default
					$qa_content['q_view']['form']['buttons']['answer']=array(
						'tags' => ' NAME="doanswerq" ',
						'label' => qa_lang_html('question/answer_button'),
						'popup' => qa_lang_html('question/answer_q_popup'),
					);
				
				if ($question['commentable'])
					$qa_content['q_view']['form']['buttons']['comment']=array(
						'tags' => ' NAME="docommentq" ',
						'label' => qa_lang_html('question/comment_button'),
						'popup' => qa_lang_html('question/comment_q_popup'),
					);
			}
				
			if (isset($parentquestion)) {
				$request=qa_q_request($parentquestion['postid'], $parentquestion['title']);
				if ($question['parentid']!=$parentquestion['postid'])
					$request.='#'.$question['parentid'];
					
				$qa_content['q_view']['follows']=array(
					'label' => qa_lang_html(($question['parentid']==$parentquestion['postid']) ? 'question/follows_q' : 'question/follows_a'),
					'title' => qa_html($parentquestion['title']),
					'url' => qa_path_html($request),
				);
			}
				
		}
	}
	
	
	{// Answer being edited
		if ($formtype=='a_edit')
			$qa_content['q_view']['a_form']=qa_page_q_edit_a_form($formpostid);
	}


	{// Comments on question...
		$qa_content['q_view']['c_list']=qa_page_q_comment_follow_list($question); // ...for viewing
		
		if (($formtype=='c_add') && ($formpostid==$questionid)) // ...to be added
			$qa_content['q_view']['c_form']=qa_page_q_add_c_form(null);
		
		elseif (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$questionid)) // ...being edited
			$qa_content['q_view']['c_form']=qa_page_q_edit_c_form($formpostid, null);
	}
	

	{// Existing answers

		$qa_content['a_list']['as']=array();
		
		qa_sort_by($answers, 'created');
		$priority=0;
	
		foreach ($answers as $answerid => $answer)
			if ($answer['viewable'] && !(($formtype=='a_edit') && ($formpostid==$answerid))) {
				$a_view=qa_post_html_fields($answer, $qa_login_userid, $qa_cookieid, $usershtml, qa_get_vote_view($qa_db, 'A'),
					qa_get_option($qa_db, 'show_user_points'), qa_get_option($qa_db, 'show_url_links'), true, $answer['isselected']);
				
				if (!$formrequested) {
					if ($question['editable'] && !$answer['hidden']) {
						if ($answer['isselected'])
							$a_view['unselect_tags']=' TITLE="'.qa_lang_html('question/unselect_popup').'" NAME="select_" ';					
						elseif (!isset($question['selchildid'])) 
							$a_view['select_tags']=' TITLE="'.qa_lang_html('question/select_popup').'" NAME="select_'.qa_html($answerid).'" ';
					}
					
					$a_view['form']=array(
						'style' => 'light',
						'buttons' => array(),
					);
					
					if ($answer['editable'])
						$a_view['form']['buttons']['edit']=array(
							'tags' => ' NAME="doedita_'.qa_html($answerid).'" ',
							'label' => qa_lang_html('question/edit_button'),
							'popup' => qa_lang_html('question/edit_a_popup'),
						);
						
					if ($answer['hideable'])
						$a_view['form']['buttons']['hide']=array(
							'tags' => ' NAME="dohidea_'.qa_html($answerid).'" ',
							'label' => qa_lang_html('question/hide_button'),
							'popup' => qa_lang_html('question/hide_a_popup'),
						);
						
					if ($answer['showable'])
						$a_view['form']['buttons']['reshow']=array(
							'tags' => ' NAME="doshowa_'.qa_html($answerid).'" ',
							'label' => qa_lang_html('question/reshow_button'),
							'popup' => qa_lang_html($answer['authorlast'] ? 'question/a_hidden_author' : 'question/a_hidden_editor'),
						);
						
					if ($answer['claimable'])
						$a_view['form']['buttons']['claim']=array(
							'tags' => ' NAME="doclaima_'.qa_html($answerid).'" ',
							'label' => qa_lang_html('question/claim_button'),
						);

					if ($answer['followable'])
						$a_view['form']['buttons']['follow']=array(
							'tags' => ' NAME="dofollowa_'.qa_html($answerid).'" ',
							'label' => qa_lang_html('question/follow_button'),
							'popup' => qa_lang_html('question/follow_a_popup'),
						);

					if ($answer['commentable'])
						$a_view['form']['buttons']['comment']=array(
							'tags' => ' NAME="docommenta_'.qa_html($answerid).'" ',
							'label' => qa_lang_html('question/comment_button'),
							'popup' => qa_lang_html('question/comment_a_popup'),
						);

				}
				
				{// Comments on answer...
					$a_view['c_list']=qa_page_q_comment_follow_list($answer); // ...for viewing
	
					if (($formtype=='c_add') && ($formpostid==$answerid)) // ...to be added
						$a_view['c_form']=qa_page_q_add_c_form($answerid);

					else if (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$answerid)) // ...being edited
						$a_view['c_form']=qa_page_q_edit_c_form($formpostid, $answerid);
				}

				if ($answer['hidden'])
					$a_view['priority']=10000+($priority++);
				elseif ($answer['isselected'])
					$a_view['priority']=0;
				else
					$a_view['priority']=5000+($priority++);
					
				$qa_content['a_list']['as'][]=$a_view;
			}
			
		qa_sort_by($qa_content['a_list']['as'], 'priority');
		
		$countanswers=$question['acount'];
		
		if ($countanswers==1)
			$qa_content['a_list']['title']=qa_lang_html('question/1_answer_title');
		else
			$qa_content['a_list']['title']=qa_lang_sub_html('question/x_answers_title', $countanswers);
	}


	if ($formtype=='a_add') { // Form for adding answers
		if ($checkanswerlogin) {
			$qa_content['q_view']['a_form']=array(
				'title' => qa_lang_html('question/your_answer_title'),
				
				'style' => 'tall',
				
				'fields' => array(
					'content' => array(
						'tags' => ' NAME="content" ID="content" ',
						'value' => qa_html(@$incontent),
						'error' => qa_html(@$errors['content']),
						'rows' => 12,
					),
				),
				
				'buttons' => array(
					'answer' => array(
						'tags' => ' NAME="doansweradd" ',
						'label' => qa_lang_html('question/add_answer_button'),
					),
				),
			);
			
			if ($formrequested) {
				$focusonid='content';
				
				$qa_content['q_view']['a_form']['buttons']['cancel']=array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('question/cancel_button'),
				);
			}
			
			qa_set_up_notify_fields($qa_content, $qa_content['q_view']['a_form']['fields'], 'A', $qa_login_email,
				isset($innotify) ? $innotify : true, @$inemail, @$errors['email']);
				
			if ($usecaptcha)
				qa_set_up_captcha_field($qa_db, $qa_content, $qa_content['q_view']['a_form']['fields'], @$errors);

		} else {
			$qa_content['q_view']['a_form']=array(
				'style' => 'tall',
				'title' => qa_insert_login_links(qa_lang_html('question/answer_must_login'), $qa_request)
			);
		}
	}

	
	if (($relatedcount>1) && !$question['hidden']) {// Related questions
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
			$qa_content['related_q_list']['qs'][]=qa_post_html_fields($related, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_get_vote_view($qa_db, 'Q'), qa_get_option($qa_db, 'show_user_points'));
	}
	
	
	if (isset($jumptohash))
		$qa_content['script_onloads'][]=array(
			"window.location.hash=".qa_js($jumptohash).";",
		);
		
	if (isset($focusonid))
		$qa_content['script_onloads'][]=array(
			"document.getElementById(".qa_js($focusonid).").focus();"
		);

?>