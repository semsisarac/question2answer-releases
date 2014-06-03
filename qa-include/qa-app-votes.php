<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-votes.php
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

	function qa_user_vote_error($db, $userid, $postid, $vote, $topage)
	{
		if (isset($userid)) {
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
			if (qa_limits_remaining($db, $userid, 'V')) {
				require_once QA_INCLUDE_DIR.'qa-db-votes.php';
				
				$voteinfo=qa_db_post_get_vote_info($db, $postid);
				if (is_array($voteinfo)) {
					if ( isset($voteinfo['userid']) && ($voteinfo['userid']==$userid) )
						return qa_lang_html('main/vote_not_found'); // can't vote on own question

					else {
						qa_set_user_vote($db, $postid, $userid, $vote, $voteinfo['userid'], $voteinfo['type']);
						return false;
					}
				
				} else
					return qa_lang_html('main/vote_not_found');

			} else
				return qa_lang_html('main/vote_limit');
		
		} else {
			require_once QA_INCLUDE_DIR.'qa-app-format.php';

			return qa_insert_login_links(qa_lang_html('main/vote_must_login'), $topage);
		}
	}
	
	function qa_set_user_vote($db, $postid, $voteuserid, $vote, $postuserid, $posttype)
	{
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		$vote=(int)min(1, max(-1, $vote));
		
		qa_db_uservote_set($db, $postid, $voteuserid, $vote);
		qa_db_post_recount_votes($db, $postid);
		
		$postisanswer=($posttype=='A') || ($posttype=='A_HIDDEN');
		
		qa_db_points_update_ifuser($db, $voteuserid, $postisanswer ? 'avotes' : 'qvotes');
		qa_db_points_update_ifuser($db, $postuserid, $postisanswer ? 'avoteds' : 'qvoteds');
		
		if ($vote<0)
			$action=$postisanswer ? 'a_vote_down' : 'q_vote_down';
		elseif ($vote>0)
			$action=$postisanswer ? 'a_vote_up' : 'q_vote_up';
		else
			$action=$postisanswer ? 'a_vote_nil' : 'q_vote_nil';
		
		qa_report_write_action($db, $voteuserid, null, $action, $postisanswer ? null : $postid, $postisanswer ? $postid : null);
	}
	
?>