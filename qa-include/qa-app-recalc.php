<?php
	
/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-recalc.php
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
	
/*
	A full list of redundant (non-normal) information in the database that can be recalculated:
	
	Recalculated in doreindexposts:
	===============================
	^titlewords (all): index of words in titles of posts
	^contentwords (all): index of words in content of posts
	^posttags (all): index of words in tags of posts
	^words (all): list of words used for indexes
	^options (title=cache_qcount|cache_acount|cache_ccount|cache_tagcount): total Qs, As, Cs, tags
	
	Recalculated in dorecalcposts:
	==============================
	^posts (upvotes, downvotes, acount): number of votes and answers received by questions
	
	Recalculated in dorecalcpoints:
	===============================
	^userpoints (all): points calculation for all users
	^options (title=cache_userpointscount):
	
*/

	require_once QA_INCLUDE_DIR.'qa-db-recalc.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';			
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';

	function qa_recalc_perform_step($db, &$state)
	{
		$continue=false;
		
		@list($operation, $length, $next, $done)=explode(',', $state);
		
		switch ($operation) {
			case 'doreindexposts':
				qa_recalc_transition($db, $state, 'doreindexposts_postcount');
				break;
				
			case 'doreindexposts_postcount':
				qa_db_qcount_update($db);
				qa_db_acount_update($db);
				qa_db_ccount_update($db);

				qa_recalc_transition($db, $state, 'doreindexposts_reindex');
				break;
				
			case 'doreindexposts_reindex':
				$posts=qa_db_posts_get_for_reindexing($db, $next, 100);
				
				if (count($posts)) {
					$lastpostid=max(array_keys($posts));
					
					qa_db_prepare_for_reindexing($db, $next, $lastpostid);
		
					foreach ($posts as $postid => $post)
						qa_post_index($db, $postid, $post['type'], $post['questionid'], $post['title'], $post['content'], $post['tags'], true);
					
					$next=1+$lastpostid;
					$done+=count($posts);
					$continue=true;

				} else {
					qa_db_truncate_indexes($db, $next);
					qa_recalc_transition($db, $state, 'doreindexposts_wordcount');
				}
				break;
				
			case 'doreindexposts_wordcount':
				$wordids=qa_db_words_prepare_for_recounting($db, $next, 1000);
				
				if (count($wordids)) {
					$lastwordid=max($wordids);
					
					qa_db_words_recount($db, $next, $lastwordid);
					
					$next=1+$lastwordid;
					$done+=count($wordids);
					$continue=true;
			
				} else {
					qa_db_tagcount_update($db); // this is quick so just do it here
					qa_recalc_transition($db, $state, 'doreindexposts_complete');
				}
				break;
				
			case 'dorecountposts':
				qa_recalc_transition($db, $state, 'dorecountposts_postcount');
				break;
				
			case 'dorecountposts_postcount':
				qa_db_qcount_update($db);
				qa_db_acount_update($db);
				qa_db_ccount_update($db);

				qa_recalc_transition($db, $state, 'dorecountposts_recount');
				break;
				
			case 'dorecountposts_recount':
				$postids=qa_db_posts_get_for_recounting($db, $next, 1000);
				
				if (count($postids)) {
					$lastpostid=max($postids);
					
					qa_db_posts_recount($db, $next, $lastpostid);
					
					$next=1+$lastpostid;
					$done+=count($postids);
					$continue=true;

				} else {
					qa_recalc_transition($db, $state, 'dorecountposts_complete');
				}
				break;
			
			case 'dorecalcpoints':
				qa_recalc_transition($db, $state, 'dorecalcpoints_usercount');
				break;
				
			case 'dorecalcpoints_usercount':
				qa_db_userpointscount_update($db); // for progress update - not necessarily accurate
				qa_recalc_transition($db, $state, 'dorecalcpoints_recalc');
				break;
				
			case 'dorecalcpoints_recalc':
				$userids=qa_db_users_get_for_recalc_points($db, $next, 100);
				
				if (count($userids)) {
					$lastuserid=max($userids);
					
					qa_db_users_recalc_points($db, $next, $lastuserid);
					
					$next=1+$lastuserid;
					$done+=count($userids);
					$continue=true;
				
				} else {
					qa_db_truncate_userpoints($db, $next);
					qa_db_userpointscount_update($db); // quick so just do it here					
					qa_recalc_transition($db, $state, 'dorecalcpoints_complete');
				}
				break;
				
			default:
				$state='';
				break;
		}
		
		if ($continue)
			$state=$operation.','.$length.','.$next.','.$done;
		
		return $continue;
	}
	
	function qa_recalc_transition($db, &$state, $operation)
	{
		$state=$operation.','.qa_recalc_stage_length($db, $operation).',0,0';
	}
		
	function qa_recalc_stage_length($db, $operation)
	{
		switch ($operation) {
			case 'doreindexposts_reindex':
			case 'dorecountposts_recount':
				$length=qa_get_option($db, 'cache_qcount')+qa_get_option($db, 'cache_acount')+qa_get_option($db, 'cache_ccount');
				break;
			
			case 'doreindexposts_wordcount':
				$length=qa_db_count_words($db);
				break;
				
			case 'dorecalcpoints_recalc':
				$length=qa_get_option($db, 'cache_userpointscount');
				break;
			
			default:
				$length=0;
				break;
		}
		
		return $length;
	}
	
	function qa_recalc_get_message($state)
	{
		@list($operation, $length, $next, $done)=explode(',', $state);
		
		switch ($operation) {
			case 'doreindexposts_postcount':
			case 'dorecountposts_postcount':
				$message=qa_lang('admin/recalc_posts_count');
				break;
				
			case 'doreindexposts_reindex':
				$message=strtr(qa_lang('admin/reindex_posts_reindexed'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'doreindexposts_wordcount':
				$message=strtr(qa_lang('admin/reindex_posts_wordcounted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecountposts_recount':
				$message=strtr(qa_lang('admin/recount_posts_recounted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'doreindexposts_complete':
				$message=qa_lang('admin/reindex_posts_complete');
				break;
				
			case 'dorecountposts_complete':
				$message=qa_lang('admin/recount_posts_complete');
				break;
				
			case 'dorecalcpoints_usercount':
				$message=qa_lang('admin/recalc_points_usercount');
				break;
				
			case 'dorecalcpoints_recalc':
				$message=strtr(qa_lang('admin/recalc_points_recalced'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecalcpoints_complete':
				$message=qa_lang('admin/recalc_points_complete');
				break;
				
			default:
				$message='';
				break;
		}
		
		return $message;
	}

?>