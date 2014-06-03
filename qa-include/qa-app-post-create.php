<?php
	
/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-create.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Creating questions, answers and comments (application level)


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
				$errors[$field]=($minlength==1) ? qa_lang('main/field_required') : qa_lang_sub('main/min_length_x', $minlength);
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

		$options=qa_get_options($db, array('min_len_q_title', 'max_len_q_title', 'min_len_q_content', 'min_num_q_tags', 'max_num_q_tags'));
		
		$errors=array();
		
		$maxtitlelength=max($options['min_len_q_title'], min($options['max_len_q_title'], QA_DB_MAX_TITLE_LENGTH));
		
		qa_length_validate($errors, 'title', $title, $options['min_len_q_title'], $maxtitlelength);
		qa_length_validate($errors, 'content', $content, $options['min_len_q_content'], QA_DB_MAX_CONTENT_LENGTH);
		
		if (isset($tagstring)) {
			$counttags=count(qa_tagstring_to_tags($tagstring));
			
			$mintags=min($options['min_num_q_tags'], $options['max_num_q_tags']); // to deal with silly settings
			
			if ($counttags<$mintags)
				$errors['tags']=qa_lang_sub('question/min_tags_x', $mintags);
			elseif ($counttags>$options['max_num_q_tags'])
				$errors['tags']=qa_lang_sub('question/max_tags_x', $options['max_num_q_tags']);
			else
				qa_length_validate($errors, 'tags', $tagstring, 0, QA_DB_MAX_TAGS_LENGTH);
		}
		
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
	
	
	function qa_category_options($db, $categories)
/*
	Return the appropriate list of category options to be shown, [id] => [title] - returns null if none to show
*/
	{
		if (qa_using_categories($db) && count($categories)) {
			$categoryoptions=array();

			if (qa_get_option($db, 'allow_no_category'))
				$categoryoptions['']=qa_lang_html('main/no_category');

			foreach ($categories as $category)
				$categoryoptions[$category['categoryid']]=qa_html($category['title']);

		} else
			$categoryoptions=null;
			
		return $categoryoptions;
	}

	
	function qa_question_create($db, $followanswer, $userid, $cookieid, $title, $content, $tagstring, $notify, $email, $categoryid=null)
/*
	Add a question (application level) - create record, update appropriate counts, index it, send notifications.
	If question is follow-on from an answer, $followanswer should contain answer database record, otherwise null.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$postid=qa_db_post_create($db, 'Q', @$followanswer['postid'], $userid, isset($userid) ? null : $cookieid,
			@$_SERVER['REMOTE_ADDR'], $title, $content, $tagstring, qa_combine_notify_email($userid, $notify, $email), $categoryid);
		
		qa_db_ifcategory_qcount_update($db, $categoryid);
		qa_post_index($db, $postid, 'Q', $postid, $title, $content, $tagstring);
		qa_db_points_update_ifuser($db, $userid, 'qposts');
		qa_db_qcount_update($db);
		qa_db_unaqcount_update($db);
		
		qa_notification_pending();
		qa_options_set_pending(array('notify_admin_q_post', 'from_email', 'site_title', 'site_url', 'feedback_email', 'block_bad_words'));
		
		if (isset($followanswer['notify']) && !qa_post_is_by_user($followanswer, $userid, $cookieid)) {
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$blockwordspreg=qa_get_block_words_preg($db);
			$sendtitle=qa_block_words_replace($title, $blockwordspreg);
			$sendcontent=qa_block_words_replace($followanswer['content'], $blockwordspreg);
			
			qa_send_notification($db, $followanswer['userid'], $followanswer['notify'], @$followanswer['handle'], qa_lang('emails/a_followed_subject'), qa_lang('emails/a_followed_body'), array(
				'^q_title' => $sendtitle,
				'^a_content' => $sendcontent,
				'^url' => qa_path(qa_q_request($postid, $sendtitle), null, qa_get_option($db, 'site_url')),
			));
		}
		
		if (qa_get_option($db, 'notify_admin_q_post'))
			qa_send_notification($db, null, qa_get_option($db, 'feedback_email'), null, qa_lang('emails/q_posted_subject'), qa_lang('emails/q_posted_body'), array(
				'^q_title' => $title, // don't censor title or content since we want the admin to see bad words
				'^q_content' => $content,
				'^url' => qa_path(qa_q_request($postid, $title), null, qa_get_option($db, 'site_url')),
			));
		
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
			@$_SERVER['REMOTE_ADDR'], null, $content, null, qa_combine_notify_email($userid, $notify, $email), $question['categoryid']);
		
		if (!$question['hidden']) // don't index answer if parent question is hidden
			qa_post_index($db, $postid, 'A', $question['postid'], null, $content, null);
		
		qa_db_post_acount_update($db, $question['postid']);
		qa_db_points_update_ifuser($db, $userid, 'aposts');
		qa_db_acount_update($db);
		qa_db_unaqcount_update($db);
		
		if (isset($question['notify']) && !qa_post_is_by_user($question, $userid, $cookieid)) {
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			qa_notification_pending();
			qa_options_set_pending(array('site_url', 'block_bad_words'));
			
			$blockwordspreg=qa_get_block_words_preg($db);
			$sendtitle=qa_block_words_replace($question['title'], $blockwordspreg);
			$sendcontent=qa_block_words_replace($content, $blockwordspreg);

			qa_send_notification($db, $question['userid'], $question['notify'], @$question['handle'], qa_lang('emails/q_answered_subject'), qa_lang('emails/q_answered_body'), array(
				'^q_title' => $sendtitle,
				'^a_content' => $sendcontent,
				'^url' => qa_path(qa_q_request($question['postid'], $sendtitle), null, qa_get_option($db, 'site_url'), null, qa_anchor('A', $postid)),
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
		require_once QA_INCLUDE_DIR.'qa-util-string.php';

		$parent=isset($answer) ? $answer : $question;
		
		$postid=qa_db_post_create($db, 'C', $parent['postid'], $userid, isset($userid) ? null : $cookieid,
			@$_SERVER['REMOTE_ADDR'], null, $content, null, qa_combine_notify_email($userid, $notify, $email), $question['categoryid']);
		
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
		qa_options_set_pending(array('site_url', 'block_bad_words'));
			
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
		
		$blockwordspreg=qa_get_block_words_preg($db);
		$sendcontext=qa_block_words_replace($context, $blockwordspreg);
		$sendcontent=qa_block_words_replace($content, $blockwordspreg);
		$sendtitle=qa_block_words_replace($question['title'], $blockwordspreg);
		$sendurl=qa_path(qa_q_request($question['postid'], $sendtitle), null,
			qa_get_option($db, 'site_url'), null, qa_anchor($parent['basetype'], $parent['postid']));
			
		if (isset($parent['notify']) && !qa_post_is_by_user($parent, $userid, $cookieid)) {
			$senduserid=$parent['userid'];
			$sendemail=@$parent['notify'];
			
			if (qa_email_validate($sendemail))
				$senttoemail[$sendemail]=true;
			elseif (isset($senduserid))
				$senttouserid[$senduserid]=true;

			qa_send_notification($db, $senduserid, $sendemail, @$parent['handle'], $subject, $body, array(
				'^c_context' => $sendcontext,
				'^c_content' => $sendcontent,
				'^url' => $sendurl,
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
						'^c_context' => $sendcontext,
						'^c_content' => $sendcontent,
						'^url' => $sendurl,
					));
				}


		return $postid;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/