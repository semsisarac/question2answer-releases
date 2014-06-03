<?php

/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-votes.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Handling incoming votes (application level)


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


	function qa_user_vote_error($db, $userid, $postid, $vote, $topage)
/*
	Process an incoming $vote (-1/0/1) by $userid on $postid, on the page $topage.
	Return an error to display if there was a problem, or false if all went smoothly.
*/
	{
		if (isset($userid)) {
			require_once QA_INCLUDE_DIR.'qa-db.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			
			qa_options_set_pending(array('voting_on_qs', 'voting_on_as', 'max_rate_user_votes', 'max_rate_ip_votes'));
	
			$post=qa_db_select_with_pending($db,
				qa_db_full_post_selectspec($userid, $postid)
			);
			
			if (qa_limits_remaining($db, $userid, 'V')) {
				require_once QA_INCLUDE_DIR.'qa-db-votes.php';
				
				if (is_array($post)) {
					require_once QA_INCLUDE_DIR.'qa-app-options.php';
					
					switch ($post['basetype']) { // enforce voting options here
						case 'Q':
							$allow=qa_get_option($db, 'voting_on_qs');
							break;
							
						case 'A':
							$allow=qa_get_option($db, 'voting_on_as');
							break;
							
						default:
							$allow=true;
							break;
					}
						
					if ( (!$allow) || (isset($post['userid']) && ($post['userid']==$userid)) )
						return qa_lang_html('main/vote_not_allowed'); // can't vote on own question

					else {
						qa_set_user_vote($db, $post, $userid, $vote);
						return false;
					}
				
				} else
					return qa_lang_html('main/vote_not_allowed');

			} else
				return qa_lang_html('main/vote_limit');
		
		} else {
			require_once QA_INCLUDE_DIR.'qa-app-format.php';

			return qa_insert_login_links(qa_lang_html('main/vote_must_login'), $topage);
		}
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
		
		qa_db_uservote_set($db, $post['postid'], $userid, $vote);
		qa_db_post_recount_votes($db, $post['postid']);
		
		$postisanswer=($post['basetype']=='A');
		
		qa_db_points_update_ifuser($db, $userid, $postisanswer ? 'avotes' : 'qvotes');
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