<?php
	
/*
	Question2Answer 1.0.1-beta (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-update.php
	Version: 1.0.1-beta
	Date: 2010-05-11 12:36:30 GMT
	Description: Changing questions, answer and comments (application level)


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

	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-update.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';

	
	function qa_question_set_text($db, $oldquestion, $title, $content, $tagstring, $notify, $lastuserid)
/*
	Change the text of a question (application level) to $title, $content, $tagstring and $notify, and reindex.
	Pass the question's database record before changes in $oldquestion and the user doing this in $lastuserid.
*/
	{
		qa_post_unindex($db, $oldquestion['postid']);
		
		qa_db_post_set_text($db, $oldquestion['postid'], $title, $content, $tagstring, $notify, $lastuserid);
		
		if (!$oldquestion['hidden'])
			qa_post_index($db, $oldquestion['postid'], 'Q', $oldquestion['postid'], $title, $content, $tagstring);
	}

	
	function qa_question_set_selchildid($db, $userid, $cookieid, $oldquestion, $selchildid, $answers)
/*
	Set the selected answer (application level) of $oldquestion to $selchildid. Pass the user currently viewing
	the page in $userid and $cookieid, and the database records for all answers to the question in $answers.
	Handles user points values and notifications.
*/
	{
		$oldselchildid=$oldquestion['selchildid'];
		
		qa_db_post_set_selchildid($db, $oldquestion['postid'], isset($selchildid) ? $selchildid : null);
		qa_db_points_update_ifuser($db, $oldquestion['userid'], 'aselects');
		
		if (isset($oldselchildid))
			if (isset($answers[$oldselchildid]))
				qa_db_points_update_ifuser($db, $answers[$oldselchildid]['userid'], 'aselecteds');
			
		if (isset($selchildid)) {
			$answer=$answers[$selchildid];
			
			qa_db_points_update_ifuser($db, $answer['userid'], 'aselecteds');

			if (isset($answer['notify']) && !qa_post_is_by_user($answer, $userid, $cookieid)) {
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';
				require_once QA_INCLUDE_DIR.'qa-app-options.php';
				
				qa_notification_pending();
				qa_options_set_pending(array('site_url'));
				
				qa_send_notification($db, $answer['userid'], $answer['notify'], @$answer['handle'], qa_lang('emails/a_selected_subject'), qa_lang('emails/a_selected_body'), array(
					'^q_title' => $oldquestion['title'],
					'^a_content' => $answer['content'],
					'^url' => qa_path(qa_q_request($oldquestion['postid'], $oldquestion['title']), null, qa_get_option($db, 'site_url'), null, $selchildid),
				));
			}
		}
	}

	
	function qa_question_set_hidden($db, $oldquestion, $hidden, $lastuserid, $answers, $commentsfollows)
/*
	Set the hidden status (application level) of $oldquestion to $hidden. Pass the user doing this in $lastuserid,
	the database records for all answers to the question in $answers, and the database records for all comments on
	the question or the question's answers in $commentsfollows ($commentsfollows can also contain records for
	follow-on questions which are ignored). Handles indexing, user points and cached counts.
*/
	{
		qa_post_unindex($db, $oldquestion['postid']);
		
		foreach ($answers as $answer)
			qa_post_unindex($db, $answer['postid']);
		
		foreach ($commentsfollows as $comment)
			if ($comment['basetype']=='C')
				qa_post_unindex($db, $comment['postid']);
			
		qa_db_post_set_type($db, $oldquestion['postid'], $hidden ? 'Q_HIDDEN' : 'Q', $lastuserid);
		qa_db_points_update_ifuser($db, $oldquestion['userid'], array('qposts', 'aselects'));
		qa_db_qcount_update($db);
		qa_db_unaqcount_update($db);
		
		if (!$hidden) {
			qa_post_index($db, $oldquestion['postid'], 'Q', $oldquestion['postid'], $oldquestion['title'], $oldquestion['content'], $oldquestion['tags']);

			foreach ($answers as $answer)
				if (!$answer['hidden']) // even if question visible, don't index hidden answers
					qa_post_index($db, $answer['postid'], $answer['type'], $oldquestion['postid'], null, $answer['content'], null);
					
			foreach ($commentsfollows as $comment)
				if ($comment['basetype']=='C')
					if (!($comment['hidden'] || @$answers[$comment['parentid']]['hidden'])) // don't index comment if it or its parent is hidden
						qa_post_index($db, $comment['postid'], $comment['type'], $oldquestion['postid'], null, $comment['content'], null);
		}
	}

	
	function qa_question_set_userid($db, $oldquestion, $userid)
/*
	Set the author (application level) of $oldquestion to $userid. Updates points as appropriate.
*/
	{
		qa_db_post_set_userid($db, $oldquestion['postid'], $userid);

		qa_db_points_update_ifuser($db, $oldquestion['userid'], array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
		qa_db_points_update_ifuser($db, $userid, array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
	}

	
	function qa_post_unindex($db, $postid)
/*
	Remove post $postid from our index and update appropriate word counts
*/
	{
		$titlewordids=qa_db_titlewords_get_post_wordids($db, $postid);
		qa_db_titlewords_delete_post($db, $postid);
		qa_db_word_titlecount_update($db, $titlewordids);

		$contentwordids=qa_db_contentwords_get_post_wordids($db, $postid);
		qa_db_contentwords_delete_post($db, $postid);
		qa_db_word_contentcount_update($db, $contentwordids);

		$tagwordids=qa_db_posttags_get_post_wordids($db, $postid);
		qa_db_posttags_delete_post($db, $postid);
		qa_db_word_tagcount_update($db, $tagwordids);
	}

	
	function qa_answer_set_text($db, $oldanswer, $content, $notify, $lastuserid, $question)
/*
	Change the text of an answer (application level) to $content and $notify, and reindex. Pass the answer's database
	record before changes in $oldanswer, the question's record in $question, and the user doing this in $lastuserid.
*/
	{
		qa_post_unindex($db, $oldanswer['postid']);
		
		qa_db_post_set_text($db, $oldanswer['postid'], $oldanswer['title'], $content, $oldanswer['tags'], $notify, $lastuserid);
		
		if (!($oldanswer['hidden'] || $question['hidden'])) // don't index if answer or its question is hidden
			qa_post_index($db, $oldanswer['postid'], 'A', $question['postid'], null, $content, null);
	}

	
	function qa_answer_set_hidden($db, $oldanswer, $hidden, $lastuserid, $question, $commentsfollows)
/*
	Set the hidden status (application level) of $oldanswer to $hidden. Pass the user doing this in $lastuserid,
	the database record for the question in $question, and the database records for all comments on
	the answer in $commentsfollows ($commentsfollows can also contain other records which are ignored).
	Handles indexing, user points and cached counts.
*/
	{
		qa_post_unindex($db, $oldanswer['postid']);
		
		foreach ($commentsfollows as $comment)
			if ( ($comment['basetype']=='C') && ($comment['parentid']==$oldanswer['postid']) )
				qa_post_unindex($db, $comment['postid']);
		
		qa_db_post_set_type($db, $oldanswer['postid'], $hidden ? 'A_HIDDEN' : 'A', $lastuserid);
		qa_db_points_update_ifuser($db, $oldanswer['userid'], array('aposts', 'aselecteds'));
		qa_db_post_acount_update($db, $question['postid']);
		qa_db_acount_update($db);
		qa_db_unaqcount_update($db);
		
		if (!($hidden || $question['hidden'])) { // even if answer visible, don't index if question is hidden
			qa_post_index($db, $oldanswer['postid'], 'A', $question['postid'], null, $oldanswer['content'], null);
			
			foreach ($commentsfollows as $comment)
				if ( ($comment['basetype']=='C') && ($comment['parentid']==$oldanswer['postid']) )
					if (!$comment['hidden']) // and don't index hidden comments
						qa_post_index($db, $comment['postid'], $comment['type'], $question['postid'], null, $comment['content'], null);
		}
	}

	
	function qa_answer_set_userid($db, $oldanswer, $userid)
/*
	Set the author (application level) of $oldanswer to $userid. Updates points as appropriate.
*/
	{
		qa_db_post_set_userid($db, $oldanswer['postid'], $userid);

		qa_db_points_update_ifuser($db, $oldanswer['userid'], array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
		qa_db_points_update_ifuser($db, $userid, array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
	}

	
	function qa_comment_set_text($db, $oldcomment, $content, $notify, $lastuserid, $question, $answer)
/*
	Change the text of a comment (application level) to $content and $notify, and reindex. Pass the comment's database
	record before changes in $oldcomment, the antecedent question's record in $question, the user doing this in $lastuserid,
	and the answer's database record in $answer if this is a comment on an answer, otherwise null.
*/
	{
		qa_post_unindex($db, $oldcomment['postid']);
		
		qa_db_post_set_text($db, $oldcomment['postid'], $oldcomment['title'], $content, $oldcomment['tags'], $notify, $lastuserid);

		if (!($oldcomment['hidden'] || $question['hidden'] || @$answer['hidden']))
			qa_post_index($db, $oldcomment['postid'], 'C', $question['postid'], null, $content, null);
	}

	
	function qa_answer_to_comment($db, $oldanswer, $parentid, $content, $notify, $lastuserid, $question, $answers, $commentsfollows)
/*
	Convert an answer to a comment (application level) and set its text to $content and $notify.
	Pass the answer's database record before changes in $oldanswer, the new comment's $parentid to be, the
	user doing this in $lastuserid, the antecedent question's record in $question, the records for all answers
	to that question in $answers, and the records for all comments on the (old) answer and questions following
	from the (old) answer in $commentsfollows ($commentsfollows can also contain other records which are ignored).
	Handles indexing, user points and cached counts.
*/
	{
		$parent=isset($answers[$parentid]) ? $answers[$parentid] : $question;
			
		qa_post_unindex($db, $oldanswer['postid']);
		
		qa_db_post_set_type($db, $oldanswer['postid'], $oldanswer['hidden'] ? 'C_HIDDEN' : 'C', $lastuserid);
		qa_db_post_set_parent($db, $oldanswer['postid'], $parentid, $lastuserid);
		qa_db_post_set_text($db, $oldanswer['postid'], $oldanswer['title'], $content, $oldanswer['tags'], $notify, $lastuserid);
		
		foreach ($commentsfollows as $commentfollow)
			if ($commentfollow['parentid']==$oldanswer['postid']) // do same thing for comments and follows
				qa_db_post_set_parent($db, $commentfollow['postid'], $parentid, $commentfollow['lastuserid']);

		qa_db_points_update_ifuser($db, $oldanswer['userid'], array('aposts', 'aselecteds', 'cposts'));

		qa_db_post_acount_update($db, $question['postid']);
		qa_db_acount_update($db);
		qa_db_ccount_update($db);
		qa_db_unaqcount_update($db);
	
		if (!($oldanswer['hidden'] || $question['hidden'] || $parent['hidden'])) // only index if none of the things it depends on are hidden
			qa_post_index($db, $oldanswer['postid'], 'C', $question['postid'], null, $oldanswer['content'], null);
	}

	
	function qa_comment_set_hidden($db, $oldcomment, $hidden, $lastuserid, $question, $answer)
/*
	Set the hidden status (application level) of $oldcomment to $hidden. Pass the antecedent question's record
	in $question, the user doing this in $lastuserid, and the answer's database record in $answer if this is a
	comment on an answer, otherwise null. Handles indexing, user points and cached counts.
*/
	{
		qa_post_unindex($db, $oldcomment['postid']);
		
		qa_db_post_set_type($db, $oldcomment['postid'], $hidden ? 'C_HIDDEN' : 'C', $lastuserid);
		qa_db_points_update_ifuser($db, $oldcomment['userid'], array('cposts'));
		qa_db_ccount_update($db);
		
		if (!($hidden || $question['hidden'] || @$answer['hidden'])) // only index if none of the things it depends on are hidden
			qa_post_index($db, $oldcomment['postid'], 'C', $question['postid'], null, $oldcomment['content'], null);
	}

	
	function qa_comment_set_userid($db, $oldcomment, $userid)
/*
	Set the author (application level) of $oldcomment to $userid. Updates points as appropriate.
*/
	{
		qa_db_post_set_userid($db, $oldcomment['postid'], $userid);
		
		qa_db_points_update_ifuser($db, $oldcomment['userid'], array('cposts'));
		qa_db_points_update_ifuser($db, $userid, array('cposts'));
	}
	
?>