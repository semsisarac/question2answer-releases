<?php
	
/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-create.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Creating questions, answers and comments (application level)


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

	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';

	
	function qa_notify_validate(&$errors, $notify, $email)
/*
	Add textual element to $errors if user-entered values for $notify checkbox and $email field invalid
*/
	{
		if ($notify && !empty($email)) {
			if (!qa_email_validate($email))
				$errors['email']=qa_lang('users/email_invalid');
			elseif (qa_strlen($email)>QA_DB_MAX_EMAIL_LENGTH)
				$errors['email']=qa_lang_sub('main/max_length_x', QA_DB_MAX_EMAIL_LENGTH);
		}
	}
	

	function qa_length_validate(&$errors, $field, $input, $minlength, $maxlength)
/*
	Add textual element $field to $errors if length of $input is not between $minlength and $maxlength
*/
	{
		if (isset($input)) {
			$length=qa_strlen($input);
			
			if ($length < $minlength)
				$errors[$field]=qa_lang_sub('main/min_length_x', $minlength);
			elseif ($length > $maxlength)
				$errors[$field]=qa_lang_sub('main/max_length_x', $maxlength);
		}
	}

	
	function qa_question_validate($db, $title, $content, $tagstring, $notify, $email)
/*
	Return $errors fields for any invalid aspect of user-entered question
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$options=qa_get_options($db, array('min_len_q_title', 'min_len_q_content'));
		
		$errors=array();
		
		qa_length_validate($errors, 'title', $title, $options['min_len_q_title'], QA_DB_MAX_TITLE_LENGTH);
		qa_length_validate($errors, 'content', $content, $options['min_len_q_content'], QA_DB_MAX_CONTENT_LENGTH);
		qa_length_validate($errors, 'tags', $tagstring, 0, QA_DB_MAX_TAGS_LENGTH);
		qa_notify_validate($errors, $notify, $email);
			
		return $errors;
	}

	
	function qa_combine_notify_email($userid, $notify, $email)
/*
	Return value to store in database combining $notify and $email values entered by user $userid (or null for anonymous)
*/
	{
		return $notify ? (empty($email) ? (isset($userid) ? '@' : null) : $email) : null;
	}

	
	function qa_question_create($db, $followanswer, $userid, $cookieid, $title, $content, $tagstring, $notify, $email)
/*
	Add a question (application level) - create record, update appropriate counts, index it, send notifications.
	If question is follow-on from an answer, $followanswer should contain answer database record, otherwise null.
*/
	{
		$postid=qa_db_post_create($db, 'Q', @$followanswer['postid'], $userid, isset($userid) ? null : $cookieid,
			$title, $content, $tagstring, qa_combine_notify_email($userid, $notify, $email));
		qa_post_index($db, $postid, 'Q', $postid, $title, $content, $tagstring);
		qa_db_points_update_ifuser($db, $userid, 'qposts');
		qa_db_qcount_update($db);
		qa_db_unaqcount_update($db);
		
		if (isset($followanswer['notify']) && !qa_post_is_by_user($followanswer, $userid, $cookieid)) {
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			
			qa_notification_pending();
			qa_options_set_pending(array('site_url'));
			
			qa_send_notification($db, $followanswer['userid'], $followanswer['notify'], @$followanswer['handle'], qa_lang('emails/a_followed_subject'), qa_lang('emails/a_followed_body'), array(
				'^q_title' => $title,
				'^a_content' => $followanswer['content'],
				'^url' => qa_path(qa_q_request($postid, $title), null, qa_get_option($db, 'site_url')),
			));
		}
		
		qa_options_set_pending(array('notify_admin_q_post', 'from_email', 'site_title', 'feedback_email'));
		
		if (qa_get_option($db, 'notify_admin_q_post')) {
			require_once QA_INCLUDE_DIR.'qa-util-emailer.php';
			
			$subs=array(
				'^site_title' => qa_get_option($db, 'site_title'),
				'^q_title' => $title,
				'^q_content' => $content,
				'^url' => qa_path(qa_q_request($postid, $title), null, qa_get_option($db, 'site_url')),
			);
			
			qa_send_email(array(
				'fromemail' => qa_get_option($db, 'from_email'),
				'fromname' => qa_get_option($db, 'site_title'),
				'toemail' => qa_get_option($db, 'feedback_email'),
				'toname' => qa_get_option($db, 'site_title'),
				'subject' => strtr(qa_lang('emails/q_posted_subject'), $subs),
				'body' => strtr(qa_lang('emails/q_posted_body'), $subs),
				'html' => false,
			));
		}
		
		return $postid;
	}

	
	function qa_array_filter_by_keys($inarray, $keys)
/*
	Return an array containing the elements of $inarray whose key is in $keys
*/
	{
		$outarray=array();

		foreach ($keys as $key)
			if (isset($inarray[$key]))
				$outarray[$key]=$inarray[$key];
				
		return $outarray;
	}

	
	function qa_post_index($db, $postid, $type, $questionid, $title, $content, $tagstring, $skipcounts=false)
/*
	Add post $postid (which comes under $questionid) of $type (Q/A/C) to the database index, with $title, $content
	and $tagstring. Set $skipcounts to true to not update counts - useful during recalculationss.
*/
	{
	
	//	Get words from each textual element
	
		$titlewords=array_unique(qa_string_to_words($title));
		$contentcount=array_count_values(qa_string_to_words($content));
		$tagwords=array_unique(qa_tagstring_to_tags($tagstring));
		
	//	Map all words to their word IDs
		
		$words=array_unique(array_merge($titlewords, array_keys($contentcount), $tagwords));
		$wordtoid=qa_db_word_mapto_ids_add($db, $words);
		
	//	Add to title words index
		
		$titlewordids=qa_array_filter_by_keys($wordtoid, $titlewords);
		qa_db_titlewords_add_post_wordids($db, $postid, $titlewordids);
	
	//	Add to content words index (including word counts)
	
		$contentwordidcounts=array();
		foreach ($contentcount as $word => $count)
			if (isset($wordtoid[$word]))
				$contentwordidcounts[$wordtoid[$word]]=$count;

		qa_db_contentwords_add_post_wordidcounts($db, $postid, $type, $questionid, $contentwordidcounts);
		
	//	Add to tag words index

		$tagwordids=qa_array_filter_by_keys($wordtoid, $tagwords);
		qa_db_posttags_add_post_wordids($db, $postid, $tagwordids);
		
	//	Update counts cached in database
		
		if (!$skipcounts) {
			qa_db_word_titlecount_update($db, $titlewordids);
			qa_db_word_contentcount_update($db, array_keys($contentwordidcounts));
			qa_db_word_tagcount_update($db, $tagwordids);
			qa_db_tagcount_update($db);
		}
	}

		
	function qa_answer_validate($db, $content, $notify, $email)
/*
	Return $errors fields for any invalid aspect of user-entered answer
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$errors=array();
		
		qa_length_validate($errors, 'content', $content, qa_get_option($db, 'min_len_a_content'), QA_DB_MAX_CONTENT_LENGTH);
		qa_notify_validate($errors, $notify, $email);
		
		return $errors;
	}

	
	function qa_answer_create($db, $userid, $cookieid, $content, $notify, $email, $question)
/*
	Add an answer (application level) - create record, update appropriate counts, index it, send notifications.
	$question should contain database record for the question this is an answer to.
*/
	{
		$postid=qa_db_post_create($db, 'A', $question['postid'], $userid, isset($userid) ? null : $cookieid,
			null, $content, null, qa_combine_notify_email($userid, $notify, $email));
		
		if (!$question['hidden']) // don't index answer if parent question is hidden
			qa_post_index($db, $postid, 'A', $question['postid'], null, $content, null);
		
		qa_db_post_acount_update($db, $question['postid']);
		qa_db_points_update_ifuser($db, $userid, 'aposts');
		qa_db_acount_update($db);
		qa_db_unaqcount_update($db);
		
		if (isset($question['notify']) && !qa_post_is_by_user($question, $userid, $cookieid)) {
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			
			qa_notification_pending();
			qa_options_set_pending(array('site_url'));
			
			qa_send_notification($db, $question['userid'], $question['notify'], @$question['handle'], qa_lang('emails/q_answered_subject'), qa_lang('emails/q_answered_body'), array(
				'^q_title' => $question['title'],
				'^a_content' => $content,
				'^url' => qa_path(qa_q_request($question['postid'], $question['title']), null, qa_get_option($db, 'site_url'), null, $postid),
			));
		}
		
		return $postid;
	}

	
	function qa_comment_validate($db, $content, $notify, $email)
/*
	Return $errors fields for any invalid aspect of user-entered comment
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$errors=array();
		
		qa_length_validate($errors, 'content', $content, qa_get_option($db, 'min_len_c_content'), QA_DB_MAX_CONTENT_LENGTH);
		qa_notify_validate($errors, $notify, $email);
			
		return $errors;
	}

	
	function qa_comment_create($db, $userid, $cookieid, $content, $notify, $email, $question, $answer, $commentsfollows)
/*
	Add a comment (application level) - create record, update appropriate counts, index it, send notifications.
	$question should contain database record for the question this is part of (as direct or comment on Q's answer).
	If this is a comment on an answer, $answer should contain database record for the answer, otherwise null.
	$commentsfollows should contain database records for all previous comments on the same question or answer,
	but it can also contain other records that are ignored.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$parent=isset($answer) ? $answer : $question;
		
		$postid=qa_db_post_create($db, 'C', $parent['postid'], $userid, isset($userid) ? null : $cookieid,
			null, $content, null, qa_combine_notify_email($userid, $notify, $email));
		
		if (!($question['hidden'] || @$answer['hidden'])) // don't index comment if parent or parent of parent is hidden
			qa_post_index($db, $postid, 'C', $question['postid'], null, $content, null);
		
		qa_db_points_update_ifuser($db, $userid, 'cposts');
		qa_db_ccount_update($db);
		
	//	$senttoemail and $senttouserid ensure each user or email gets only one notification about an added comment,
	//	even if they have several previous comments in the same thread and asked for notifications for the parent.
	//	Still, if a person posted some comments as a registered user and some others anonymously,
	//	they could get two emails about a subsequent comment. Shouldn't be a problem in practice.

		$senttoemail=array();
		$senttouserid=array();
		
		qa_notification_pending();
		qa_options_set_pending(array('site_url'));
			
		switch ($parent['basetype']) {
			case 'Q':
				$subject=qa_lang('emails/q_commented_subject');
				$body=qa_lang('emails/q_commented_body');
				$context=$parent['title'];
				break;
				
			case 'A':
				$subject=qa_lang('emails/a_commented_subject');
				$body=qa_lang('emails/a_commented_body');
				$context=$parent['content'];
				break;
		}
			
		if (isset($parent['notify']) && !qa_post_is_by_user($parent, $userid, $cookieid)) {
			$senduserid=$parent['userid'];
			$sendemail=@$parent['notify'];
			
			if (qa_email_validate($sendemail))
				$senttoemail[$sendemail]=true;
			elseif (isset($senduserid))
				$senttouserid[$senduserid]=true;

			qa_send_notification($db, $senduserid, $sendemail, @$parent['handle'], $subject, $body, array(
				'^c_context' => $context,
				'^c_content' => $content,
				'^url' => qa_path(qa_q_request($question['postid'], $question['title']), null, qa_get_option($db, 'site_url'), null, $parent['postid']),
			));
		}
		
		foreach ($commentsfollows as $comment)
			if (($comment['basetype']=='C') && ($comment['parentid']==$parent['postid']) && (!$comment['hidden'])) // find just those for this parent
				if (isset($comment['notify']) && !qa_post_is_by_user($comment, $userid, $cookieid)) {
					$senduserid=$comment['userid'];
					$sendemail=@$comment['notify'];
					
					if (qa_email_validate($sendemail)) {
						if (@$senttoemail[$sendemail])
							continue;
							
						$senttoemail[$sendemail]=true;
						
					} elseif (isset($senduserid)) {
						if (@$senttouserid[$senduserid])
							continue;
							
						$senttouserid[$senduserid]=true;
					}

					qa_send_notification($db, $senduserid, $sendemail, @$comment['handle'], qa_lang('emails/c_commented_subject'), qa_lang('emails/c_commented_body'), array(
						'^c_context' => $context,
						'^c_content' => $content,
						'^url' => qa_path(qa_q_request($question['postid'], $question['title']), null, qa_get_option($db, 'site_url'), null, $parent['postid']),
					));
				}


		return $postid;
	}
	
?>