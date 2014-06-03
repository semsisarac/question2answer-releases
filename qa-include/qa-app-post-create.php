<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-create.php
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

	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
	function qa_question_validate($db, $title, $content, $tagstring)
	{
		require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$errors=array();
		
		$options=qa_get_options($db, array('min_len_q_title', 'min_len_q_content'));
		
		if (isset($title)) {
			$length=qa_strlen($title);
			
			if ($length < $options['min_len_q_title'])
				$errors['title']=qa_lang_sub('question/q_title_min', $options['min_len_q_title']);
			elseif ($length > QA_DB_MAX_TITLE_LENGTH)
				$errors['title']=qa_lang_sub('main/max_length_x', QA_DB_MAX_EMAIL_LENGTH);
		}
		
		if (isset($content)) {
			$length=qa_strlen($content);
			
			if ($length < $options['min_len_q_content'])
				$errors['content']=qa_lang_sub('question/q_content_min', $options['min_len_q_content']);
			elseif ($length > QA_DB_MAX_CONTENT_LENGTH)
				$errors['content']=qa_lang_sub('main/max_length_x', QA_DB_MAX_CONTENT_LENGTH);
		}
		
		if (isset($tagstring)) {
			$length=qa_strlen($tagstring);
	
			if ($length > QA_DB_MAX_TAGS_LENGTH)
				$errors['tags']=qa_lang_sub('main/max_length_x', QA_DB_MAX_TAGS_LENGTH);
		}
			
		return $errors;
	}
	
	function qa_question_create($db, $userid, $cookieid, $title, $content, $tagstring, $notify)
	{
		$postid=qa_db_post_create($db, 'Q', null, $userid, isset($userid) ? null : $cookieid, $title, $content, $tagstring, $notify);
		qa_post_index($db, $postid, $title, $content, $tagstring);
		qa_db_points_update_ifuser($db, $userid, 'qposts');
		qa_db_qcount_update($db);
		
		return $postid;
	}
	
	function qa_array_filter_by_keys($inarray, $keys)
	{
		$outarray=array();

		foreach ($keys as $key)
			if (isset($inarray[$key]))
				$outarray[$key]=$inarray[$key];
				
		return $outarray;
	}
	
	function qa_post_index($db, $postid, $title, $content, $tagstring, $skipcounts=false)
	{
		$titlewords=array_unique(qa_string_to_words($title));
		$contentcount=array_count_values(qa_string_to_words($content));
		$tagwords=array_unique(qa_tagstring_to_tags($tagstring));
		
		$words=array_unique(array_merge($titlewords, array_keys($contentcount), $tagwords));
		$wordtoid=qa_db_word_mapto_ids_add($db, $words);
		
		$titlewordids=qa_array_filter_by_keys($wordtoid, $titlewords);
		qa_db_titlewords_add_post_wordids($db, $postid, $titlewordids);
		
		$contentwordidcounts=array();
		foreach ($contentcount as $word => $count)
			$contentwordidcounts[$wordtoid[$word]]=$count;

		qa_db_contentwords_add_post_wordidcounts($db, $postid, $contentwordidcounts);

		$tagwordids=qa_array_filter_by_keys($wordtoid, $tagwords);
		qa_db_posttags_add_post_wordids($db, $postid, $tagwordids);
		
		if (!$skipcounts) {
			qa_db_word_titlecount_update($db, $titlewordids);
			qa_db_word_contentcount_update($db, array_keys($contentwordidcounts));
			qa_db_word_tagcount_update($db, $tagwordids);
			qa_db_tagcount_update($db);
		}
	}
		
	function qa_answer_validate($db, $content)
	{
		require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$errors=array();
		
		$length=qa_strlen($content);		
		$minlength=qa_get_option($db, 'min_len_a_content');
		
		if ($length < $minlength)
			$errors['content']=qa_lang_sub('question/a_content_min', $minlength);
		elseif ($length > QA_DB_MAX_CONTENT_LENGTH)
			$errors['content']=qa_lang_sub('main/max_length_x', QA_DB_MAX_CONTENT_LENGTH);
			
		return $errors;
	}
	
	function qa_answer_create($db, $questionid, $userid, $cookieid, $content, $q_title, $q_userid, $q_notify, $q_hidden)
	{
		$postid=qa_db_post_create($db, 'A', $questionid, $userid, isset($userid) ? null : $cookieid, '', $content, '', null);
		
		if (!$q_hidden)
			qa_post_index($db, $postid, null, $content, null);
		
		qa_db_post_acount_update($db, $questionid);
		qa_db_points_update_ifuser($db, $userid, 'aposts');
		qa_db_acount_update($db);
		
		if (isset($q_notify)) {
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$email=($q_notify=='@') ? qa_get_user_email($db, $q_userid) : $q_notify;
				
			if (isset($email) && qa_email_validate($email)) { // validate email here since it could be from external source
				require_once QA_INCLUDE_DIR.'qa-util-emailer.php';
	
				$options=qa_get_options($db, array('from_email', 'site_url', 'neat_urls', 'site_title'));
				
				$subs=array(
					'^site_title' => $options['site_title'],
					'^q_title' => $q_title,
					'^a_content' => $content,
					'^url' => qa_path(qa_q_request($questionid, $q_title), null, $options['site_url'], $options['neat_urls']),
				);
				
				qa_send_email(array(
					'fromemail' => $options['from_email'],
					'fromname' => $options['site_title'],
					'toemail' => $email,
					'toname' => '',
					'subject' => strtr(qa_lang('question/q_notify_subject'), $subs),
					'body' => strtr(qa_lang('question/q_notify_message'), $subs),
					'html' => false,
				));
			}
		}		
		
		return $postid;
	}
	
?>