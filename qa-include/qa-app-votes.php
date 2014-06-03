<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-votes.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Handling incoming votes (application level)


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


	function qa_user_vote_error($userid, $postid, $vote, $topage)
/*
	Process an incoming $vote (-1/0/1) by $userid on $postid, on the page $topage.
	Return an error to display if there was a problem, or false if all went smoothly.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		$post=qa_db_select_with_pending(qa_db_full_post_selectspec($userid, $postid));
		
		if (
			is_array($post) &&
			( ($post['basetype']=='Q') || ($post['basetype']=='A') ) &&
			qa_opt(($post['basetype']=='Q') ? 'voting_on_qs' : 'voting_on_as') &&
			( (!isset($post['userid'])) || (!isset($userid)) || ($post['userid']!=$userid) )
		) {
			
			switch (qa_user_permit_error(($post['basetype']=='Q') ? 'permit_vote_q' : 'permit_vote_a', 'V')) {
				case 'login':
					return qa_insert_login_links(qa_lang_html('main/vote_must_login'), $topage);
					break;
					
				case 'confirm':
					return qa_insert_login_links(qa_lang_html('main/vote_must_confirm'), $topage);
					break;
					
				case 'limit':
					return qa_lang_html('main/vote_limit');
					break;
					
				default:
					return qa_lang_html('users/no_permission');
					break;
					
				case false:
					require_once QA_INCLUDE_DIR.'qa-db-votes.php';
					qa_set_user_vote($post, $userid, $vote);
					return false;
			}
		
		} else
			return qa_lang_html('main/vote_not_allowed'); // voting option should not have been presented (but could happen due to options change)
	}

	
	function qa_set_user_vote($post, $userid, $vote)
/*
	Actually set (application level) the $vote (-1/0/1) by $userid on $postid.
	Handles user points and recounting as appropriate.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		$vote=(int)min(1, max(-1, $vote));
		$oldvote=(int)qa_db_uservote_get($post['postid'], $userid);

		qa_db_uservote_set($post['postid'], $userid, $vote);
		qa_db_post_recount_votes($post['postid']);
		
		$postisanswer=($post['basetype']=='A');
		
		$columns=array();
		
		if ( ($vote>0) || ($oldvote>0) )
			$columns[]=$postisanswer ? 'aupvotes' : 'qupvotes';

		if ( ($vote<0) || ($oldvote<0) )
			$columns[]=$postisanswer ? 'adownvotes' : 'qdownvotes';
			
		qa_db_points_update_ifuser($userid, $columns);
		
		qa_db_points_update_ifuser($post['userid'], array($postisanswer ? 'avoteds' : 'qvoteds', 'upvoteds', 'downvoteds'));
		
		if ($vote<0)
			$action=$postisanswer ? 'a_vote_down' : 'q_vote_down';
		elseif ($vote>0)
			$action=$postisanswer ? 'a_vote_up' : 'q_vote_up';
		else
			$action=$postisanswer ? 'a_vote_nil' : 'q_vote_nil';
		
		qa_report_write_action($userid, null, $action, $postisanswer ? null : $post['postid'], $postisanswer ? $post['postid'] : null, null);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/