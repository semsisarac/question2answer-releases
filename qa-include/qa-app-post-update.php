<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-update.php
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

	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-update.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	
	function qa_question_set_text($db, $postid, $hidden, $title, $content, $tagstring, $notify)
	{
		qa_post_unindex($db, $postid);
		
		qa_db_post_set_text($db, $postid, $title, $content, $tagstring, $notify);
		
		if (!$hidden)
			qa_post_index($db, $postid, $title, $content, $tagstring);
	}
	
	function qa_question_set_selchildid($db, $questionid, $question, $selchildid, $answers)
	{
		$oldselchildid=$question['selchildid'];
		
		qa_db_post_set_selchildid($db, $questionid, isset($selchildid) ? $selchildid : null);
		qa_db_points_update_ifuser($db, $question['userid'], 'aselects');
		
		if (isset($oldselchildid))
			if (isset($answers[$oldselchildid]))
				qa_db_points_update_ifuser($db, $answers[$oldselchildid]['userid'], 'aselecteds');
			
		if (isset($selchildid))
			qa_db_points_update_ifuser($db, $answers[$selchildid]['userid'], 'aselecteds');
	}
	
	function qa_question_set_hidden($db, $postid, $hidden, $userid, $title, $content, $tagstring, $answers)
	{
		qa_post_unindex($db, $postid);
		
		foreach ($answers as $answer)
			qa_post_unindex($db, $answer['postid']);
		
		qa_db_post_set_type($db, $postid, $hidden ? 'Q_HIDDEN' : 'Q');
		qa_db_points_update_ifuser($db, $userid, array('qposts', 'aselects', 'qvoteds'));
		qa_db_qcount_update($db);
		
		if (!$hidden) {
			qa_post_index($db, $postid, $title, $content, $tagstring);

			foreach ($answers as $answer)
				if ($answer['type']=='A')
					qa_post_index($db, $answer['postid'], null, $answer['content'], null);
		}
	}
	
	function qa_post_unindex($db, $postid)
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
	
	function qa_answer_set_text($db, $postid, $hidden, $content, $q_hidden)
	{
		qa_post_unindex($db, $postid);
		
		qa_db_post_set_text($db, $postid, '', $content, '', null);
		
		if ( (!$hidden) && (!$q_hidden) )
			qa_post_index($db, $postid, null, $content, null);
	}
	
	function qa_answer_set_hidden($db, $questionid, $postid, $hidden, $userid, $content, $q_hidden)
	{
		qa_post_unindex($db, $postid);
		
		qa_db_post_set_type($db, $postid, $hidden ? 'A_HIDDEN' : 'A');
		qa_db_points_update_ifuser($db, $userid, array('aposts', 'aselecteds', 'avoteds'));
		qa_db_post_acount_update($db, $questionid);
		qa_db_acount_update($db);
		
		if ( (!$hidden) && (!$q_hidden) )
			qa_post_index($db, $postid, null, $content, null);
	}
	
?>