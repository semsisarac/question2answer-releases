<?php
	
/*
	Question2Answer 1.3.2 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-update.php
	Version: 1.3.2
	Date: 2011-03-14 09:01:08 GMT
	Description: Changing questions, answer and comments (application level)


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

	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-update.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';

	
	function qa_question_set_content($oldquestion, $title, $content, $format, $text, $tagstring, $notify, $lastuserid)
/*
	Change the fields of a question (application level) to $title, $content, $format, $tagstring and $notify, and reindex based on $text.
	Pass the question's database record before changes in $oldquestion and the user doing this in $lastuserid.
*/
	{
		qa_post_unindex($oldquestion['postid']);
		
		qa_db_post_set_content($oldquestion['postid'], $title, $content, $format, $tagstring, $notify, $lastuserid, @$_SERVER['REMOTE_ADDR']);
		
		if (!$oldquestion['hidden'])
			qa_post_index($oldquestion['postid'], 'Q', $oldquestion['postid'], $title, $text, $tagstring);
	}

	
	function qa_question_set_selchildid($userid, $handle, $cookieid, $oldquestion, $selchildid, $answers)
/*
	Set the selected answer (application level) of $oldquestion to $selchildid. Pass the user currently viewing
	the page in $userid and $cookieid, and the database records for all answers to the question in $answers.
	Handles user points values and notifications.
*/
	{
		$oldselchildid=$oldquestion['selchildid'];
		
		qa_db_post_set_selchildid($oldquestion['postid'], isset($selchildid) ? $selchildid : null);
		qa_db_points_update_ifuser($oldquestion['userid'], 'aselects');
		
		if (isset($oldselchildid))
			if (isset($answers[$oldselchildid]))
				qa_db_points_update_ifuser($answers[$oldselchildid]['userid'], 'aselecteds');
			
		if (isset($selchildid)) {
			$answer=$answers[$selchildid];
			
			qa_db_points_update_ifuser($answer['userid'], 'aselecteds');

			if (isset($answer['notify']) && !qa_post_is_by_user($answer, $userid, $cookieid)) {
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';
				require_once QA_INCLUDE_DIR.'qa-app-options.php';
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				require_once QA_INCLUDE_DIR.'qa-app-format.php';
				
				$blockwordspreg=qa_get_block_words_preg();
				$sendtitle=qa_block_words_replace($oldquestion['title'], $blockwordspreg);
				$sendcontent=qa_viewer_text($answer['content'], $answer['format'], array('blockwordspreg' => $blockwordspreg));

				qa_send_notification($answer['userid'], $answer['notify'], @$answer['handle'], qa_lang('emails/a_selected_subject'), qa_lang('emails/a_selected_body'), array(
					'^s_handle' => isset($handle) ? $handle : qa_lang('main/anonymous'),
					'^q_title' => $sendtitle,
					'^a_content' => $sendcontent,
					'^url' => qa_path(qa_q_request($oldquestion['postid'], $sendtitle), null, qa_opt('site_url'), null, qa_anchor('A', $selchildid)),
				));
			}
		}
	}

	
	function qa_question_set_hidden($oldquestion, $hidden, $lastuserid, $answers, $commentsfollows)
/*
	Set the hidden status (application level) of $oldquestion to $hidden. Pass the user doing this in $lastuserid,
	the database records for all answers to the question in $answers, and the database records for all comments on
	the question or the question's answers in $commentsfollows ($commentsfollows can also contain records for
	follow-on questions which are ignored). Handles indexing, user points and cached counts.
*/
	{
		qa_post_unindex($oldquestion['postid']);
		
		foreach ($answers as $answer)
			qa_post_unindex($answer['postid']);
		
		foreach ($commentsfollows as $comment)
			if ($comment['basetype']=='C')
				qa_post_unindex($comment['postid']);
			
		qa_db_post_set_type($oldquestion['postid'], $hidden ? 'Q_HIDDEN' : 'Q', $lastuserid, @$_SERVER['REMOTE_ADDR']);
		qa_db_ifcategory_qcount_update($oldquestion['categoryid']);
		qa_db_points_update_ifuser($oldquestion['userid'], array('qposts', 'aselects'));
		qa_db_qcount_update();
		qa_db_unaqcount_update();
		
		if (!$hidden) {
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			qa_post_index($oldquestion['postid'], 'Q', $oldquestion['postid'], $oldquestion['title'],
				qa_viewer_text($oldquestion['content'], $oldquestion['format']), $oldquestion['tags']);

			foreach ($answers as $answer)
				if (!$answer['hidden']) // even if question visible, don't index hidden answers
					qa_post_index($answer['postid'], $answer['type'], $oldquestion['postid'], null,
						qa_viewer_text($answer['content'], $answer['format']), null);
					
			foreach ($commentsfollows as $comment)
				if ($comment['basetype']=='C')
					if (!($comment['hidden'] || @$answers[$comment['parentid']]['hidden'])) // don't index comment if it or its parent is hidden
						qa_post_index($comment['postid'], $comment['type'], $oldquestion['postid'], null,
							qa_viewer_text($comment['content'], $comment['format']), null);
		}
	}

	
	function qa_question_set_category($oldquestion, $categoryid, $lastuserid, $answers, $commentsfollows)
/*
	Sets the category (application level) of $oldquestion to $categoryid. Pass the user doing this in $lastuserid,
	the database records for all answers to the question in $answers, and the database records for all comments on
	the question or the question's answers in $commentsfollows ($commentsfollows can also contain records for
	follow-on questions which are ignored). Handles cached counts and will reset categories for all As and Cs.
*/
	{
		qa_db_post_set_category($oldquestion['postid'], $categoryid, $lastuserid, @$_SERVER['REMOTE_ADDR']);
		
		qa_db_ifcategory_qcount_update($oldquestion['categoryid']);
		qa_db_ifcategory_qcount_update($categoryid);
		
		$otherpostids=array();
		foreach ($answers as $answer)
			$otherpostids[]=$answer['postid'];
			
		foreach ($commentsfollows as $comment)
			if ($comment['basetype']=='C')
				$otherpostids[]=$comment['postid'];
				
		qa_db_post_set_category_multi($otherpostids, $categoryid);
	}
	
	
	function qa_question_delete($oldquestion)
/*
	Permanently delete a question (application level) from the database. The question must not have any
	answers or comments on it. Handles unindexing, votes, points and cached counts.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		
		if (!$oldquestion['hidden'])
			qa_fatal_error('Tried to delete a non-hidden question');
		
		$useridvotes=qa_db_uservote_post_get($oldquestion['postid']);
		
		qa_post_unindex($oldquestion['postid']);
		qa_db_post_delete($oldquestion['postid']); // also deletes any related voteds due to cascading
		
		qa_db_ifcategory_qcount_update($oldquestion['categoryid']);
		qa_db_points_update_ifuser($oldquestion['userid'], array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
		
		foreach ($useridvotes as $userid => $vote)
			qa_db_points_update_ifuser($userid, ($vote>0) ? 'qupvotes' : 'qdownvotes');
				// could do this in one query like in qa_db_users_recalc_points() but this will do for now - unlikely to be many votes
		
		qa_db_qcount_update();
		qa_db_unaqcount_update();
	}


	function qa_question_set_userid($oldquestion, $userid)
/*
	Set the author (application level) of $oldquestion to $userid. Updates points as appropriate.
*/
	{
		qa_db_post_set_userid($oldquestion['postid'], $userid);

		qa_db_points_update_ifuser($oldquestion['userid'], array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
		qa_db_points_update_ifuser($userid, array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
	}

	
	function qa_post_unindex($postid)
/*
	Remove post $postid from our index and update appropriate word counts
*/
	{
		$titlewordids=qa_db_titlewords_get_post_wordids($postid);
		qa_db_titlewords_delete_post($postid);
		qa_db_word_titlecount_update($titlewordids);

		$contentwordids=qa_db_contentwords_get_post_wordids($postid);
		qa_db_contentwords_delete_post($postid);
		qa_db_word_contentcount_update($contentwordids);

		$tagwordids=qa_db_posttags_get_post_wordids($postid);
		qa_db_posttags_delete_post($postid);
		qa_db_word_tagcount_update($tagwordids);
	}

	
	function qa_answer_set_content($oldanswer, $content, $format, $text, $notify, $lastuserid, $question)
/*
	Change the fields of an answer (application level) to $content, $format and $notify, and reindex based on $text.
	Pass the answer's database record before changes in $oldanswer, the question's in $question, and the user doing this in $lastuserid.
*/
	{
		qa_post_unindex($oldanswer['postid']);
		
		qa_db_post_set_content($oldanswer['postid'], $oldanswer['title'], $content, $format, $oldanswer['tags'], $notify, $lastuserid, @$_SERVER['REMOTE_ADDR']);
		
		if (!($oldanswer['hidden'] || $question['hidden'])) // don't index if answer or its question is hidden
			qa_post_index($oldanswer['postid'], 'A', $question['postid'], null, $text, null);
	}

	
	function qa_answer_set_hidden($oldanswer, $hidden, $lastuserid, $question, $commentsfollows)
/*
	Set the hidden status (application level) of $oldanswer to $hidden. Pass the user doing this in $lastuserid,
	the database record for the question in $question, and the database records for all comments on
	the answer in $commentsfollows ($commentsfollows can also contain other records which are ignored).
	Handles indexing, user points and cached counts.
*/
	{
		qa_post_unindex($oldanswer['postid']);
		
		foreach ($commentsfollows as $comment)
			if ( ($comment['basetype']=='C') && ($comment['parentid']==$oldanswer['postid']) )
				qa_post_unindex($comment['postid']);
		
		qa_db_post_set_type($oldanswer['postid'], $hidden ? 'A_HIDDEN' : 'A', $lastuserid, @$_SERVER['REMOTE_ADDR']);
		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds'));
		qa_db_post_acount_update($question['postid']);
		qa_db_acount_update();
		qa_db_unaqcount_update();
		
		if (!($hidden || $question['hidden'])) { // even if answer visible, don't index if question is hidden
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			qa_post_index($oldanswer['postid'], 'A', $question['postid'], null,
				qa_viewer_text($oldanswer['content'], $oldanswer['format']), null);
			
			foreach ($commentsfollows as $comment)
				if ( ($comment['basetype']=='C') && ($comment['parentid']==$oldanswer['postid']) )
					if (!$comment['hidden']) // and don't index hidden comments
						qa_post_index($comment['postid'], $comment['type'], $question['postid'], null,
							qa_viewer_text($comment['content'], $comment['format']), null);
		}
	}

	
	function qa_answer_delete($oldanswer, $question)
/*
	Permanently delete an answer (application level) from the database. The answer must not have any
	comments or follow-on questions. Pass the database record for the question in $question.
	Handles unindexing, votes, points and cached counts.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		
		if (!$oldanswer['hidden'])
			qa_fatal_error('Tried to delete a non-hidden question');
		
		$useridvotes=qa_db_uservote_post_get($oldanswer['postid']);
		
		qa_post_unindex($oldanswer['postid']);
		qa_db_post_delete($oldanswer['postid']); // also deletes any related voteds due to cascading
		
		if ($question['selchildid']==$oldanswer['postid']) {
			qa_db_post_set_selchildid($question['postid'], null);
			qa_db_points_update_ifuser($question['userid'], 'aselects');
		}
		
		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
		
		foreach ($useridvotes as $userid => $vote)
			qa_db_points_update_ifuser($userid, ($vote>0) ? 'aupvotes' : 'adownvotes');
				// could do this in one query like in qa_db_users_recalc_points() but this will do for now - unlikely to be many votes
		
		qa_db_post_acount_update($question['postid']);
		qa_db_acount_update();
		qa_db_unaqcount_update();
	}
	
	
	function qa_answer_set_userid($oldanswer, $userid)
/*
	Set the author (application level) of $oldanswer to $userid. Updates points as appropriate.
*/
	{
		qa_db_post_set_userid($oldanswer['postid'], $userid);

		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
		qa_db_points_update_ifuser($userid, array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
	}

	
	function qa_comment_set_content($oldcomment, $content, $format, $text, $notify, $lastuserid, $question, $answer)
/*
	Change the fields of a comment (application level) to $content, $format and $notify, and reindex based on $text.
	Pass the comment's database record before changes in $oldcomment, the antecedent question's in $question, the user doing
	this in $lastuserid, and the answer's database record in $answer if this is a comment on an answer, otherwise null.
*/
	{
		qa_post_unindex($oldcomment['postid']);
		
		qa_db_post_set_content($oldcomment['postid'], $oldcomment['title'], $content, $format, $oldcomment['tags'], $notify, $lastuserid, @$_SERVER['REMOTE_ADDR']);

		if (!($oldcomment['hidden'] || $question['hidden'] || @$answer['hidden']))
			qa_post_index($oldcomment['postid'], 'C', $question['postid'], null, $text, null);
	}

	
	function qa_answer_to_comment($oldanswer, $parentid, $content, $format, $text, $notify, $lastuserid, $question, $answers, $commentsfollows)
/*
	Convert an answer to a comment (application level) and set its fields to $content, $format and $notify.
	Pass the answer's database record before changes in $oldanswer, the new comment's $parentid to be, the
	user doing this in $lastuserid, the antecedent question's record in $question, the records for all answers
	to that question in $answers, and the records for all comments on the (old) answer and questions following
	from the (old) answer in $commentsfollows ($commentsfollows can also contain other records which are ignored).
	Handles indexing (based on $text), user points and cached counts.
*/
	{
		$parent=isset($answers[$parentid]) ? $answers[$parentid] : $question;
			
		qa_post_unindex($oldanswer['postid']);
		
		qa_db_post_set_type($oldanswer['postid'], $oldanswer['hidden'] ? 'C_HIDDEN' : 'C', $lastuserid, @$_SERVER['REMOTE_ADDR']);
		qa_db_post_set_parent($oldanswer['postid'], $parentid, $lastuserid, @$_SERVER['REMOTE_ADDR']);
		qa_db_post_set_content($oldanswer['postid'], $oldanswer['title'], $content, $format, $oldanswer['tags'], $notify, $lastuserid, @$_SERVER['REMOTE_ADDR']);
		
		foreach ($commentsfollows as $commentfollow)
			if ($commentfollow['parentid']==$oldanswer['postid']) // do same thing for comments and follows
				qa_db_post_set_parent($commentfollow['postid'], $parentid, $commentfollow['lastuserid'], @$_SERVER['REMOTE_ADDR']);

		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds', 'cposts'));

		qa_db_post_acount_update($question['postid']);
		qa_db_acount_update();
		qa_db_ccount_update();
		qa_db_unaqcount_update();
	
		if (!($oldanswer['hidden'] || $question['hidden'] || $parent['hidden'])) { // only index if none of the things it depends on are hidden
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			qa_post_index($oldanswer['postid'], 'C', $question['postid'], null, $text, null);
		}
	}

	
	function qa_comment_set_hidden($oldcomment, $hidden, $lastuserid, $question, $answer)
/*
	Set the hidden status (application level) of $oldcomment to $hidden. Pass the antecedent question's record
	in $question, the user doing this in $lastuserid, and the answer's database record in $answer if this is a
	comment on an answer, otherwise null. Handles indexing, user points and cached counts.
*/
	{
		qa_post_unindex($oldcomment['postid']);
		
		qa_db_post_set_type($oldcomment['postid'], $hidden ? 'C_HIDDEN' : 'C', $lastuserid, @$_SERVER['REMOTE_ADDR']);
		qa_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
		qa_db_ccount_update();
		
		if (!($hidden || $question['hidden'] || @$answer['hidden'])) { // only index if none of the things it depends on are hidden
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			qa_post_index($oldcomment['postid'], 'C', $question['postid'], null,
				qa_viewer_text($oldcomment['content'], $oldcomment['format']), null);
		}
	}

	
	function qa_comment_delete($oldcomment)
/*
	Permanently delete a comment (application level) from the database.
	Handles unindexing, points and cached counts.
*/
	{
		if (!$oldcomment['hidden'])
			qa_fatal_error('Tried to delete a non-hidden comment');
		
		qa_post_unindex($oldcomment['postid']);
		qa_db_post_delete($oldcomment['postid']);
		qa_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
		qa_db_ccount_update();
	}

	
	function qa_comment_set_userid($oldcomment, $userid)
/*
	Set the author (application level) of $oldcomment to $userid. Updates points as appropriate.
*/
	{
		qa_db_post_set_userid($oldcomment['postid'], $userid);
		
		qa_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
		qa_db_points_update_ifuser($userid, array('cposts'));
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/