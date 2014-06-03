<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Controller for question page (only viewing functionality here)


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

	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-sort.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	
	$questionid=$pass_questionid; // picked up from index.php


//	Get information about this question

	function qa_page_q_load_q()
/*
	Load all the necessary content relating to the question from the database into the appropriate global variables
*/
	{
		global $qa_db, $qa_login_userid, $questionid, $question, $parentquestion, $answers, $commentsfollows,
			$relatedcount, $relatedquestions, $question, $categories;

		list($question, $childposts, $achildposts, $parentquestion, $relatedquestions, $categories)=qa_db_select_with_pending($qa_db,
			qa_db_full_post_selectspec($qa_login_userid, $questionid),
			qa_db_full_child_posts_selectspec($qa_login_userid, $questionid),
			qa_db_full_a_child_posts_selectspec($qa_login_userid, $questionid),
			qa_db_post_parent_q_selectspec($questionid),
			qa_db_related_qs_selectspec($qa_login_userid, $questionid),
			qa_db_categories_selectspec()
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

			foreach ($answers as $key => $answer) {
				$question['deleteable']=false;
				
				qa_page_q_post_rules($answers[$key]);
				if ($answers[$key]['isbyuser'] && !qa_get_option($qa_db, 'allow_multi_answers'))
					$question['answerbutton']=false;
				
				$answers[$key]['isselected']=($answer['postid']==$question['selchildid']);
			}
	
			foreach ($commentsfollows as $key => $commentfollow) {
				if ($commentfollow['parentid']==$questionid)
					$question['deleteable']=false;
				
				if (isset($answers[$commentfollow['parentid']]))
					$answers[$commentfollow['parentid']]['deleteable']=false;
					
				qa_page_q_post_rules($commentsfollows[$key]);
			}
		}
	}

	
	function qa_page_q_post_rules(&$post)
/*
	Add elements to the array $post which describe which operations this user may perform on that post
*/
	{
		global $qa_db, $qa_login_userid, $qa_cookieid;
		
		$post['isbyuser']=qa_post_is_by_user($post, $qa_login_userid, $qa_cookieid);

	//	Cache some responses to the user permission checks
	
		$permiterror_post_q=qa_user_permit_error($qa_db, 'permit_post_q');
		$permiterror_post_a=qa_user_permit_error($qa_db, 'permit_post_a');
		$permiterror_post_c=qa_user_permit_error($qa_db, 'permit_post_c');

		$permiterror_edit=qa_user_permit_error($qa_db, ($post['basetype']=='Q') ? 'permit_edit_q' :
			(($post['basetype']=='A') ? 'permit_edit_a' : 'permit_edit_c'));
		$permiterror_hide_show=qa_user_permit_error($qa_db, $post['isbyuser'] ? null : 'permit_hide_show');
	
	//	General permissions
	
		$post['authorlast']=(($post['lastuserid']===$post['userid']) || !isset($post['lastuserid']));
		$post['viewable']=(!$post['hidden']) || !$permiterror_hide_show;
		
	//	Answer, comment and edit might show the button even if the user still needs to do something (e.g. log in)
		
		$post['answerbutton']=($post['type']=='Q') && ($permiterror_post_a!='level');

		$post['commentbutton']=(($post['type']=='Q') || ($post['type']=='A')) &&
			($permiterror_post_c!='level') &&
			qa_get_option($qa_db, ($post['type']=='Q') ? 'comment_on_qs' : 'comment_on_as');
		$post['commentable']=$post['commentbutton'] && !$permiterror_post_c;

		$post['editbutton']=(!$post['hidden']) && ($post['isbyuser'] || ($permiterror_edit!='level'));
		$post['aselectable']=($post['type']=='Q') && !qa_user_permit_error($qa_db, $post['isbyuser'] ? null : 'permit_select_a');
		
	//	Other actions only show the button if it's immediately possible
		
		$post['hideable']=(!$post['hidden']) && !$permiterror_hide_show;
		$post['reshowable']=$post['hidden'] && (!$permiterror_hide_show) && ($post['authorlast'] || !$post['isbyuser']);
			// can only reshow a question if you're the one who hid it, or of course if you have general showing permissions
		$post['deleteable']=$post['hidden'] && !qa_user_permit_error($qa_db, 'permit_delete_hidden');
			// this does not check the post has no children - that check is performed in qa_page_q_load_q()
		$post['claimable']=(!isset($post['userid'])) && isset($qa_login_userid) &&(@$post['cookieid']==$qa_cookieid) &&
			!(($post['basetype']=='Q') ? $permiterror_post_q : (($post['basetype']=='A') ? $permiterror_post_a : $permiterror_post_c));
		$post['followable']=($post['type']=='A') ? qa_get_option($qa_db, 'follow_on_as') : false;
	}

	
	function qa_page_q_comment_follow_list($parent)
/*
	Return a theme-ready structure with all the comments and follow-on questions to show for post $parent (question or answer)
*/
	{
		global $qa_db, $commentsfollows, $qa_login_userid, $qa_cookieid, $usershtml, $formtype, $formpostid, $formrequested, $categories;
		
		foreach ($commentsfollows as $commentfollowid => $commentfollow)
			if (($commentfollow['parentid']==$parent['postid']) && $commentfollow['viewable'] && ($commentfollowid!=$formpostid) ) {
				if ($commentfollow['basetype']=='C') {
					$c_view=qa_post_html_fields($commentfollow, $qa_login_userid, $qa_cookieid, $usershtml, false,
						null, false, qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
						qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db), qa_get_option($qa_db, 'show_url_links'), true);
						

				//	Buttons for operating on this comment
						
					if (!$formrequested) { // don't show if another form is currently being shown on page
						$c_view['form']=array(
							'style' => 'light',
							'buttons' => array(),
						);
					
						if ($commentfollow['editbutton'])
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
							
						if ($commentfollow['reshowable'])
							$c_view['form']['buttons']['reshow']=array(
								'tags' => ' NAME="doshowc_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/reshow_button'),
							);
							
						if ($commentfollow['deleteable'])
							$c_view['form']['buttons']['delete']=array(
								'tags' => ' NAME="dodeletec_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/delete_button'),
								'popup' => qa_lang_html('question/delete_c_popup'),
							);
							
						if ($commentfollow['claimable'])
							$c_view['form']['buttons']['claim']=array(
								'tags' => ' NAME="doclaimc_'.qa_html($commentfollowid).'" ',
								'label' => qa_lang_html('question/claim_button'),
							);
							
						if ($parent['commentbutton'] && qa_get_option($qa_db, 'show_c_reply_buttons') && !$commentfollow['hidden'])
							$c_view['form']['buttons']['comment']=array(
								'tags' => ' NAME="'.(($parent['basetype']=='Q') ? 'docommentq' : ('docommenta_'.qa_html($parent['postid']))).'" ',
								'label' => qa_lang_html('question/reply_button'),
								'popup' => qa_lang_html('question/reply_c_popup'),
							);

					}

				} elseif ($commentfollow['basetype']=='Q') {
					$c_view=qa_post_html_fields($commentfollow, $qa_login_userid, $qa_cookieid, $usershtml, false,
						qa_using_categories($qa_db) ? $categories : null, false, qa_get_option($qa_db, 'show_when_created'),
						!qa_user_permit_error($qa_db, 'permit_anon_view_ips'), qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
				}

				$commentlist[]=$c_view;
			}
			
		return @$commentlist;
	}


//	Get information about this question

	qa_options_set_pending(array('do_related_qs', 'page_size_related_qs', 'match_related_qs', 'allow_no_category', 'allow_multi_answers',
		'page_size_ask_tags', 'do_complete_tags', 'show_c_reply_buttons', 'show_url_links', 'voting_on_qs', 'voting_on_q_page_only',
		'voting_on_as', 'votes_separated', 'comment_on_qs', 'comment_on_as', 'follow_on_as', 'captcha_on_anon_post', 'captcha_on_unconfirmed',
		'sort_answers_by', 'show_selected_first', 'show_a_form_immediate', 'show_when_created', 'show_user_points', 'permit_post_q', 'permit_post_a',
		'permit_post_c', 'permit_edit_q', 'permit_edit_a', 'permit_edit_c', 'permit_select_a', 'permit_hide_show', 'permit_delete_hidden',
		'block_ips_write', 'permit_anon_view_ips', 'block_bad_words'));

	qa_captcha_pending();
	
	qa_page_q_load_q();
	
	$usecaptcha=qa_user_use_captcha($qa_db, 'captcha_on_anon_post');


//	Deal with question not found or not viewable

	if ((!isset($question)) || !$question['viewable']) {
		qa_content_prepare();

		$qa_content['error']=qa_lang_html(isset($question)
			? ($question['authorlast'] ? 'question/q_hidden_author' : 'question/q_hidden_other')
			: 'question/q_not_found'
		);

		$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags($qa_db));

		return;
	}

		
//	If we're responding to an HTTP POST, include file that handles all posting/editing/etc... logic
//	This is in a separate file because it's a *lot* of logic, and will slow down ordinary page views

	$pageerror=null;
	$formtype=null;
	$formpostid=null;
	$jumptoanchor=null;
	$focusonid=null;
	
	if (qa_is_http_post()) {
		require QA_INCLUDE_DIR.'qa-page-question-post.php';
		qa_page_q_load_q(); // reload since we may have changed something
	}
	
	$formrequested=isset($formtype);

	if ((!$formrequested) && $question['answerbutton']) {
		$immedoption=qa_get_option($qa_db, 'show_a_form_immediate');

		if ( ($immedoption=='always') || (($immedoption=='if_no_as') && (!$question['isbyuser']) && (!$question['acount'])) )
			$formtype='a_add'; // show answer form by default
	}
	
	
//	Get information on the users referenced

	$usershtml=qa_userids_handles_html($qa_db, array_merge(array($question), $answers, $commentsfollows, $relatedquestions), true);
	
	
//	Prepare content for theme
	
	qa_content_prepare(true, $question['categoryid']);
	
	$qa_content['form_tags']=' METHOD="POST" ACTION="'.qa_self_html().'" ';
	
	if (isset($pageerror))
		$qa_content['error']=$pageerror; // might also show voting error set in qa-index.php
	
	if ($question['hidden'])
		$qa_content['hidden']=true;
	
	qa_sort_by($commentsfollows, 'created');


//	Prepare content for the question...
	
	if ($formtype=='q_edit') { // ...in edit mode
		$qa_content['title']=qa_lang_html('question/edit_q_title');
		$qa_content['q_edit_form']=qa_page_q_edit_q_form();

	} else { // ...in view mode
		$qa_content['q_view']=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
			qa_using_tags($qa_db), qa_using_categories($qa_db) ? $categories : null,
			qa_get_vote_view($qa_db, 'Q', true), qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
			qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db), qa_get_option($qa_db, 'show_url_links'), true);
		
		$qa_content['title']=$qa_content['q_view']['title'];
		
		$qa_content['description']=qa_html(qa_shorten_string_line($question['content'], 150));
		
		$categorykeyword=@$categories[$question['categoryid']]['title'];
		
		$qa_content['keywords']=qa_html(implode(',', array_merge(
			(qa_using_categories($qa_db) && strlen($categorykeyword)) ? array($categorykeyword) : array(),
			qa_tagstring_to_tags($question['tags'])
		))); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
		
		unset($qa_content['q_view']['answers']); // answer count is displayed separately so don't show it here
		

	//	Buttons for operating on the question
		
		if (!$formrequested) { // don't show if another form is currently being shown on page
			$qa_content['q_view']['form']=array(
				'style' => 'light',
				'buttons' => array(),
			);
			
			if ($question['editbutton'])
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
				
			if ($question['reshowable'])
				$qa_content['q_view']['form']['buttons']['reshow']=array(
					'tags' => ' NAME="doshowq" ',
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ($question['deleteable'])
				$qa_content['q_view']['form']['buttons']['delete']=array(
					'tags' => ' NAME="dodeleteq" ',
					'label' => qa_lang_html('question/delete_button'),
					'popup' => qa_lang_html('question/delete_q_popup'),
				);
				
			if ($question['claimable'])
				$qa_content['q_view']['form']['buttons']['claim']=array(
					'tags' => ' NAME="doclaimq" ',
					'label' => qa_lang_html('question/claim_button'),
				);
			
			if ($question['answerbutton'] && ($formtype!='a_add')) // don't show if shown by default
				$qa_content['q_view']['form']['buttons']['answer']=array(
					'tags' => ' NAME="doanswerq" ',
					'label' => qa_lang_html('question/answer_button'),
					'popup' => qa_lang_html('question/answer_q_popup'),
				);
			
			if ($question['commentbutton'])
				$qa_content['q_view']['form']['buttons']['comment']=array(
					'tags' => ' NAME="docommentq" ',
					'label' => qa_lang_html('question/comment_button'),
					'popup' => qa_lang_html('question/comment_q_popup'),
				);
		}
		

	//	Information about the question of the answer that this question follows on from (or a question directly)
			
		if (isset($parentquestion)) {
			$parentquestion['title']=qa_block_words_replace($parentquestion['title'], qa_get_block_words_preg($qa_db));

			$qa_content['q_view']['follows']=array(
				'label' => qa_lang_html(($question['parentid']==$parentquestion['postid']) ? 'question/follows_q' : 'question/follows_a'),
				'title' => qa_html($parentquestion['title']),
				'url' => qa_path_html(qa_q_request($parentquestion['postid'], $parentquestion['title']),
					null, null, null, ($question['parentid']==$parentquestion['postid']) ? null : qa_anchor('A', $question['parentid'])),
			);
		}
			
	}
	

//	Prepare content for an answer being edited (if any)

	if ($formtype=='a_edit')
		$qa_content['q_view']['a_form']=qa_page_q_edit_a_form($formpostid);


//	Prepare content for comments on the question, plus add or edit comment forms

	$qa_content['q_view']['c_list']=qa_page_q_comment_follow_list($question); // ...for viewing
	
	if (($formtype=='c_add') && ($formpostid==$questionid)) // ...to be added
		$qa_content['q_view']['c_form']=qa_page_q_add_c_form(null);
	
	elseif (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$questionid)) // ...being edited
		$qa_content['q_view']['c_form']=qa_page_q_edit_c_form($formpostid, null);
	

//	Prepare content for existing answers

	$qa_content['a_list']['as']=array();
	
	if (qa_get_option($qa_db, 'sort_answers_by')=='votes') {
		foreach ($answers as $answerid => $answer)
			$answers[$answerid]['sortvotes']=$answer['downvotes']-$answer['upvotes'];

		qa_sort_by($answers, 'sortvotes', 'created');

	} else
		qa_sort_by($answers, 'created');

	$priority=0;

	foreach ($answers as $answerid => $answer)
		if ($answer['viewable'] && !(($formtype=='a_edit') && ($formpostid==$answerid))) {
			$a_view=qa_post_html_fields($answer, $qa_login_userid, $qa_cookieid, $usershtml, false, null,
				qa_get_vote_view($qa_db, 'A', true), qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
				qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db), qa_get_option($qa_db, 'show_url_links'), true, $answer['isselected']);
			

		//	Selection/unselect buttons and others for operating on the answer

			if (!$formrequested) { // don't show if another form is currently being shown on page
				if ($question['aselectable'] && !$answer['hidden']) {
					if ($answer['isselected'])
						$a_view['unselect_tags']=' TITLE="'.qa_lang_html('question/unselect_popup').'" NAME="select_" ';
					elseif (!isset($question['selchildid']))
						$a_view['select_tags']=' TITLE="'.qa_lang_html('question/select_popup').'" NAME="select_'.qa_html($answerid).'" ';
				}
				
				$a_view['form']=array(
					'style' => 'light',
					'buttons' => array(),
				);
				
				if ($answer['editbutton'])
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
					
				if ($answer['reshowable'])
					$a_view['form']['buttons']['reshow']=array(
						'tags' => ' NAME="doshowa_'.qa_html($answerid).'" ',
						'label' => qa_lang_html('question/reshow_button'),
					);
					
				if ($answer['deleteable'])
					$a_view['form']['buttons']['delete']=array(
						'tags' => ' NAME="dodeletea_'.qa_html($answerid).'" ',
						'label' => qa_lang_html('question/delete_button'),
						'popup' => qa_lang_html('question/delete_a_popup'),
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

				if ($answer['commentbutton'])
					$a_view['form']['buttons']['comment']=array(
						'tags' => ' NAME="docommenta_'.qa_html($answerid).'" ',
						'label' => qa_lang_html('question/comment_button'),
						'popup' => qa_lang_html('question/comment_a_popup'),
					);

			}
			

		//	Prepare content for comments on this answer, plus add or edit comment forms
			
			$a_view['c_list']=qa_page_q_comment_follow_list($answer); // ...for viewing

			if (($formtype=='c_add') && ($formpostid==$answerid)) // ...to be added
				$a_view['c_form']=qa_page_q_add_c_form($answerid);

			else if (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$answerid)) // ...being edited
				$a_view['c_form']=qa_page_q_edit_c_form($formpostid, $answerid);


		//	Determine this answer's place in the order on the page

			if ($answer['hidden'])
				$a_view['priority']=10000+($priority++);
			elseif ($answer['isselected'] && qa_get_option($qa_db, 'show_selected_first'))
				$a_view['priority']=0;
			else
				$a_view['priority']=5000+($priority++);
				

		//	Add the answer to the list
				
			$qa_content['a_list']['as'][]=$a_view;
		}
		
	qa_sort_by($qa_content['a_list']['as'], 'priority');
	
	$countanswers=$question['acount'];
	
	if ($countanswers==1)
		$qa_content['a_list']['title']=qa_lang_html('question/1_answer_title');
	else
		$qa_content['a_list']['title']=qa_lang_html_sub('question/x_answers_title', $countanswers);


//	Prepare content for form to add an answer

	if ($formtype=='a_add') { // Form for adding answers
		$answerform=null;
		
		switch (qa_user_permit_error($qa_db, 'permit_post_a')) {
			case 'login':
				$answerform=array(
					'style' => 'tall',
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_login'), $qa_request)
				);
				break;
				
			case 'confirm':
				$answerform=array(
					'style' => 'tall',
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_confirm'), $qa_request)
				);
				break;
			
			case false:
				$answerform=array(
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
				
				if ($formrequested) { // only show cancel button if user explicitly requested the form
					$focusonid='content';
					
					$answerform['buttons']['cancel']=array(
						'tags' => ' NAME="docancel" ',
						'label' => qa_lang_html('main/cancel_button'),
					);
				}
				
				qa_set_up_notify_fields($qa_content, $answerform['fields'], 'A', qa_get_logged_in_email($qa_db),
					isset($innotify) ? $innotify : true, @$inemail, @$errors['email']);
					
				if ($usecaptcha)
					qa_set_up_captcha_field($qa_db, $qa_content, $answerform['fields'], @$errors,
						qa_insert_login_links(qa_lang_html(isset($qa_login_userid) ? 'misc/captcha_confirm_fix' : 'misc/captcha_login_fix')));
				break;
		}
		
		if ($formrequested || empty($qa_content['a_list']['as']))
			$qa_content['q_view']['a_form']=$answerform; // show directly under question
		else {
			$answerkeys=array_keys($qa_content['a_list']['as']);
			$qa_content['a_list']['as'][$answerkeys[count($answerkeys)-1]]['c_form']=$answerform; // under last answer
		}
	}


//	List of related questions
	
	if (($relatedcount>1) && !$question['hidden']) {
		$minscore=qa_match_to_min_score(qa_get_option($qa_db, 'match_related_qs'));
		
		foreach ($relatedquestions as $key => $related)
			if ( ($related['postid']==$questionid) || ($related['score']<$minscore) ) // related questions will include itself so remove that
				unset($relatedquestions[$key]);
		
		if (count($relatedquestions))
			$qa_content['related_q_list']['title']=qa_lang('main/related_qs_title');
		else
			$qa_content['related_q_list']['title']=qa_lang('main/no_related_qs_title');
			
		$qa_content['related_q_list']['qs']=array();
		foreach ($relatedquestions as $related)
			$qa_content['related_q_list']['qs'][]=qa_post_html_fields($related, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_using_tags($qa_db), qa_using_categories($qa_db) ? $categories : null, qa_get_vote_view($qa_db, 'Q'),
				qa_get_option($qa_db, 'show_when_created'), !qa_user_permit_error($qa_db, 'permit_anon_view_ips'),
				qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
	}
	

//	Some final generally useful stuff
	
	if (qa_using_categories($qa_db) && count($categories))
		$qa_content['navigation']['cat']=qa_category_navigation($categories, $question['categoryid']);

	if (isset($jumptoanchor))
		$qa_content['script_onloads'][]=array(
			"window.location.hash=".qa_js($jumptoanchor).";",
		);
		
	if (isset($focusonid))
		$qa_content['script_onloads'][]=array(
			"document.getElementById(".qa_js($focusonid).").focus();"
		);


/*
	Omit PHP closing tag to help avoid accidental output
*/