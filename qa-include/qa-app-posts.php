<?php
	
/*
	Question2Answer 1.4-beta-1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-posts.php
	Version: 1.4-beta-1
	Date: 2011-05-25 07:38:57 GMT
	Description: Higher-level functions to create and manipulate posts


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
	

	require_once QA_INCLUDE_DIR.'qa-db.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';


	function qa_post_create($type, $parentpostid, $title, $content, $format='', $categoryid=null, $tags=null, $userid=null, $notify=null, $email=null)
	{
		$handle=qa_post_userid_to_handle($userid);
		$text=qa_post_content_to_text($content, $format);

		switch ($type) {
			case 'Q':
				$followanswer=isset($parentpostid) ? qa_post_get_full($parentpostid, 'A') : null;
				$tagstring=qa_post_tags_to_tagstring($tags);
				$postid=qa_question_create($followanswer, $userid, $handle, null, $title, $content, $format, $text, $tagstring, $notify, $email, $categoryid);
				break;
				
			case 'A':
				$question=qa_post_get_full($parentpostid, 'Q');
				$postid=qa_answer_create($userid, $handle, null, $content, $format, $text, $notify, $email, $question);
				break;
				
			case 'C':
				$parentpost=qa_post_get_full($parentpostid, 'QA');
				$commentsfollows=qa_db_single_select(qa_db_full_child_posts_selectspec(null, $parentpostid));
				$question=qa_post_parent_to_question($parentpost);
				$answer=qa_post_parent_to_answer($parentpost);
				$postid=qa_comment_create($userid, $handle, null, $content, $format, $text, $notify, $email, $question, $answer, $commentsfollows);
				break;
				
			default:
				qa_fatal_error('Post type not recognized: '.$type);
				break;
		}
		
		return $postid;
	}
	
	
	function qa_post_set_content($postid, $title, $content, $format=null, $tags=null, $notify=null, $email=null, $byuserid=null)
	{
		$oldpost=qa_post_get_full($postid, 'QAC');
		
		if (!isset($title))
			$title=$oldpost['title'];
		
		if (!isset($content))
			$content=$oldpost['content'];
			
		if (!isset($format))
			$format=$oldpost['format'];
			
		if (!isset($tags))
			$tags=qa_tagstring_to_tags($oldpost['tags']);
			
		if (isset($notify) || isset($email))
			$setnotify=qa_combine_notify_email($oldpost['userid'], isset($notify) ? $notify : isset($oldpost['notify']),
				isset($email) ? $email : $oldpost['notify']);
		else
			$setnotify=$oldpost['notify'];
	
		$byhandle=qa_post_userid_to_handle($byuserid);
		$text=qa_post_content_to_text($content, $format);
		
		switch ($oldpost['basetype']) {
			case 'Q':
				$tagstring=qa_post_tags_to_tagstring($tags);
				qa_question_set_content($oldpost, $title, $content, $format, $text, $tagstring, $setnotify, $byuserid, $byhandle, null);
				break;
				
			case 'A':
				$question=qa_post_get_full($oldpost['parentid'], 'Q');
				qa_answer_set_content($oldpost, $content, $format, $text, $setnotify, $byuserid, $byhandle, null, $question);
				break;
				
			case 'C':
				$parentpost=qa_post_get_full($oldpost['parentid'], 'QA');
				$question=qa_post_parent_to_question($parentpost);
				$answer=qa_post_parent_to_answer($parentpost);		
				qa_comment_set_content($oldpost, $content, $format, $text, $setnotify, $byuserid, $byhandle, null, $question, $answer);
				break;
		}
	}

	
	function qa_post_set_category($postid, $categoryid, $byuserid=null)
	{
		$oldpost=qa_post_get_full($postid, 'QAC');
		
		if ($oldpost['basetype']=='Q') {
			$byhandle=qa_post_userid_to_handle($byuserid);
			$answers=qa_post_get_question_answers($postid);
			$commentsfollows=qa_post_get_question_commentsfollows($postid);
			qa_question_set_category($oldpost, $categoryid, $byuserid, $byhandle, null, $answers, $commentsfollows);

		} else
			qa_post_set_category($oldpost['parentid'], $categoryid, $byuserid); // keep looking until we find the parent question
	}

	
	function qa_post_set_selchildid($questionid, $selchildid, $byuserid=null)
	{
		$oldquestion=qa_post_get_full($questionid, 'Q');
		$byhandle=qa_post_userid_to_handle($byuserid);
		$answers=qa_post_get_question_answers($questionid);
		
		if (isset($selchildid) && !isset($answers[$selchildid]))
			qa_fatal_error('Answer ID could not be found: '.$selchildid);
		
		qa_question_set_selchildid($byuserid, $byuserid, null, $oldquestion, $selchildid, $answers);
	}

	
	function qa_post_set_hidden($postid, $hidden=true, $byuserid=null)
	{
		$oldpost=qa_post_get_full($postid, 'QAC');
		$byhandle=qa_post_userid_to_handle($byuserid);
		
		switch ($oldpost['basetype']) {
			case 'Q':
				$answers=qa_post_get_question_answers($postid);
				$commentsfollows=qa_post_get_question_commentsfollows($postid);
				qa_question_set_hidden($oldpost, $hidden, $byuserid, $byhandle, null, $answers, $commentsfollows);
				break;
				
			case 'A':
				$question=qa_post_get_full($oldpost['parentid'], 'Q');
				$commentsfollows=qa_post_get_answer_commentsfollows($postid);
				qa_answer_set_hidden($oldpost, $hidden, $byuserid, $byhandle, null, $question, $commentsfollows);
				break;
				
			case 'C':
				$parentpost=qa_post_get_full($oldpost['parentid'], 'QA');
				$question=qa_post_parent_to_question($parentpost);
				$answer=qa_post_parent_to_answer($parentpost);		
				qa_comment_set_hidden($oldpost, $hidden, $byuserid, $byhandle, null, $question, $answer);
				break;
		}
	}

	
	function qa_post_delete($postid)
	{
		$oldpost=qa_post_get_full($postid, 'QAC');
		
		if (!$oldpost['hidden']) {
			qa_post_set_hidden($postid, true, null);
			$oldpost=qa_post_get_full($postid, 'QAC');
		}
		
		switch ($oldpost['basetype']) {
			case 'Q':
				$answers=qa_post_get_question_answers($postid);
				$commentsfollows=qa_post_get_question_commentsfollows($postid);
				
				if (count($answers) || count($commentsfollows))
					qa_fatal_error('Could not delete question ID due to dependents: '.$postid);
					
				qa_question_delete($oldpost, null, null, null);
				break;
				
			case 'A':
				$question=qa_post_get_full($oldpost['parentid'], 'Q');
				$commentsfollows=qa_post_get_answer_commentsfollows($postid);

				if (count($commentsfollows))
					qa_fatal_error('Could not delete answer ID due to dependents: '.$postid);

				qa_answer_delete($oldpost, $question, null, null, null);
				break;
				
			case 'C':
				$parentpost=qa_post_get_full($oldpost['parentid'], 'QA');
				$question=qa_post_parent_to_question($parentpost);
				$answer=qa_post_parent_to_answer($parentpost);		
				qa_comment_delete($oldpost, $question, $answer, null, null, null);
				break;
		}
	}


	function qa_post_get_full($postid, $requiredbasetypes=null)
	{
		$post=qa_db_single_select(qa_db_full_post_selectspec(null, $postid));
			
		if (!is_array($post))
			qa_fatal_error('Post ID could not be found: '.$postid);
		
		if (isset($requiredbasetypes) && !is_numeric(strpos($requiredbasetypes, $post['basetype'])))
			qa_fatal_error('Post of wrong type: '.$post['basetype']);
		
		return $post;
	}

	
	function qa_post_userid_to_handle($userid)
	{
		if (isset($userid)) {
			$user=qa_db_single_select(qa_db_user_account_selectspec($userid, true));
			
			if (!is_array($user))
				qa_fatal_error('User ID could not be found: '.$userid);
			
			return $user['handle'];
		}
		
		return null;
	}


	function qa_post_content_to_text($content, $format)
	{
		$viewer=qa_load_viewer($content, $format);
		
		if (!isset($viewer))
			qa_fatal_error('Content could not be parsed in format: '.$format);
			
		return $viewer->get_text($content, $format, array());
	}

	
	function qa_post_tags_to_tagstring($tags)
	{
		if (is_array($tags))
			$tags=implode(',', $tags);
			
		return qa_tags_to_tagstring(array_unique(qa_string_to_words($tags, true, false, false, false)));
	}

	
	function qa_post_get_question_answers($questionid)
	{
		$answers=array();
		
		$childposts=qa_db_single_select(qa_db_full_child_posts_selectspec(null, $questionid));
		
		foreach ($childposts as $postid => $post)
			if ($post['basetype']=='A')
				$answers[$postid]=$post;
		
		return $answers;
	}

	
	function qa_post_get_question_commentsfollows($questionid)
	{
		$commentsfollows=array();
		
		list($childposts, $achildposts)=qa_db_multi_select(array(
			qa_db_full_child_posts_selectspec(null, $questionid),
			qa_db_full_a_child_posts_selectspec(null, $questionid),
		));

		foreach ($childposts as $postid => $post)
			if ($post['basetype']=='C')
				$commentsfollows[$postid]=$post;
		
		foreach ($achildposts as $postid => $post)
			if ( ($post['basetype']=='Q') || ($post['basetype']=='C') )
				$commentsfollows[$postid]=$post;
		
		return $commentsfollows;
	}

	
	function qa_post_get_answer_commentsfollows($answerid)
	{
		$commentsfollows=array();
		
		$childposts=qa_db_single_select(qa_db_full_child_posts_selectspec(null, $answerid));

		foreach ($childposts as $postid => $post)
			if ( ($post['basetype']=='Q') || ($post['basetype']=='C') )
				$commentsfollows[$postid]=$post;
				
		return $commentsfollows;
	}
	

	function qa_post_parent_to_question($parentpost)
	{
		if ($parentpost['basetype']=='Q')
			$question=$parentpost;
		else
			$question=qa_post_get_full($parentpost['parentid'], 'Q');
		
		return $question;
	}

	
	function qa_post_parent_to_answer($parentpost)
	{
		if ($parentpost['basetype']=='A')
			$answer=$parentpost;
		else
			$answer=null;
			
		return $answer;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/