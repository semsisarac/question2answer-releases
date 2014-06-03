<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-votes.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Handling incoming votes (application level)


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


	function qa_user_vote_error($db, $userid, $postid, $vote, $topage)
/*
	Process an incoming $vote (-1/0/1) by $userid on $postid, on the page $topage.
	Return an error to display if there was a problem, or false if all went smoothly.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		qa_options_set_pending(array('voting_on_qs', 'voting_on_as', 'max_rate_user_votes', 'max_rate_ip_votes', 'permit_vote_q', 'permit_vote_a', 'block_ips_write'));

		$post=qa_db_select_with_pending($db, qa_db_full_post_selectspec($userid, $postid));
		
		if (
			is_array($post) &&
			( ($post['basetype']=='Q') || ($post['basetype']=='A') ) &&
			qa_get_option($db, ($post['basetype']=='Q') ? 'voting_on_qs' : 'voting_on_as') &&
			( (!isset($post['userid'])) || (!isset($userid)) || ($post['userid']!=$userid) )
		) {
			
			switch (qa_user_permit_error($db, ($post['basetype']=='Q') ? 'permit_vote_q' : 'permit_vote_a', 'V')) {
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
					qa_set_user_vote($db, $post, $userid, $vote);
					return false;
			}
		
		} else
			return qa_lang_html('main/vote_not_allowed'); // voting option should not have been presented (but could happen due to options change)
	}

	
	function qa_set_user_vote($db, $post, $userid, $vote)
/*
	Actually set (application level) the $vote (-1/0/1) by $userid on $postid.
	Handles user points and recounting as appropriate.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		$vote=(int)min(1, max(-1, $vote));
		$oldvote=(int)qa_db_uservote_get($db, $post['postid'], $userid);

		qa_db_uservote_set($db, $post['postid'], $userid, $vote);
		qa_db_post_recount_votes($db, $post['postid']);
		
		$postisanswer=($post['basetype']=='A');
		
		$columns=array();
		
		if ( ($vote>0) || ($oldvote>0) )
			$columns[]=$postisanswer ? 'aupvotes' : 'qupvotes';

		if ( ($vote<0) || ($oldvote<0) )
			$columns[]=$postisanswer ? 'adownvotes' : 'qdownvotes';
			
		qa_db_points_update_ifuser($db, $userid, $columns);
		
		qa_db_points_update_ifuser($db, $post['userid'], array($postisanswer ? 'avoteds' : 'qvoteds', 'upvoteds', 'downvoteds'));
		
		if ($vote<0)
			$action=$postisanswer ? 'a_vote_down' : 'q_vote_down';
		elseif ($vote>0)
			$action=$postisanswer ? 'a_vote_up' : 'q_vote_up';
		else
			$action=$postisanswer ? 'a_vote_nil' : 'q_vote_nil';
		
		qa_report_write_action($db, $userid, null, $action, $postisanswer ? null : $post['postid'], $postisanswer ? $post['postid'] : null, null);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/