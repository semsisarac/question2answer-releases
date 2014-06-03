<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-options.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Getting and setting admin options (application level)


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

	require_once QA_INCLUDE_DIR.'qa-db-options.php';


	define('QA_PERMIT_ALL', 150);
	define('QA_PERMIT_USERS', 120);
	define('QA_PERMIT_CONFIRMED', 110);
	define('QA_PERMIT_EXPERTS', 100);
	define('QA_PERMIT_EDITORS', 70);
	define('QA_PERMIT_MODERATORS', 40);
	define('QA_PERMIT_ADMINS', 20);
	define('QA_PERMIT_SUPERS', 0);

	
	function qa_get_option($db, $name)
/*
	Return the setting for the single option $name, see qa_get_options() for more details
*/
	{
		$options=qa_get_options($db, array($name));

		return $options[$name];
	}


	function qa_get_options($db, $names)
/*
	Return an array [name] => [value] of settings for each option in $names.
	If any options are missing from the database, set them to their defaults
*/
	{
		global $qa_options_cache;
		
	//	If any options not cached, retrieve them from database via standard pending mechanism

		if (qa_options_set_pending($names)) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			
			qa_db_select_with_pending($db);
		}
		
	//	Pull out the options specifically requested here

		$options=array();
		foreach ($names as $name)
			$options[$name]=$qa_options_cache[$name];
		
		return $options;
	}

	
	function qa_options_set_pending($names)
/*
	Queue the options in $names to be retrieved during next database query
*/
	{
		global $qa_options_cache, $qa_options_pending;
		
		$needtoload=false;
		
		foreach ($names as $name)
			if (!isset($qa_options_cache[$name])) {
				$qa_options_pending[$name]=true;
				$needtoload=true;
			}
			
		return $needtoload;
	}

	
	function qa_options_pending_selectspec()
/*
	Return selectspec array (see qa-db.php) to get queued options from database
*/
	{
		global $qa_options_pending;
		
		if (count($qa_options_pending))
			return array(
				'columns' => array('title', 'content' => 'BINARY content'),
				'source' => '^options WHERE title IN ($)',
				'arguments' => array(array_keys($qa_options_pending)),
				'arraykey' => 'title',
				'arrayvalue' => 'content',
			);
		else
			return false;
	}

	
	function qa_options_load_options($db, $selectspec, $gotoptions)
/*
	Called after the options are retrieved from the database using $selectspec which returned $gotoptions
*/
	{
		global $qa_options_cache, $qa_options_pending;
		
		if (is_array($selectspec)) {
			foreach ($selectspec['arguments'][0] as $name) {
				unset($qa_options_pending[$name]);
				
				if (isset($gotoptions[$name])) // sets those successfully retrieved
					$qa_options_cache[$name]=$gotoptions[$name];
			}
			
			foreach ($selectspec['arguments'][0] as $name)
				if (!isset($qa_options_cache[$name])) { // set others to default values

					switch ($name) { // don't write default to database if option was deprecated, or depends on site language (which could be changed)
						case 'custom_sidebar':
						case 'site_title':
						case 'email_privacy':
						case 'answer_needs_login':
						case 'ask_needs_login':
						case 'comment_needs_login':
							$todatabase=false;
							break;
						
						default:
							$todatabase=true;
							break;
					}
					
					qa_set_option($db, $name, qa_default_option($db, $name), $todatabase);
				}
		}
	}


	function qa_set_option($db, $name, $value, $todatabase=true)
/*
	Set an option $name to $value (application level) in both cache and database, unless
	$todatabase=false, in which case set it in the cache only
*/
	{
		global $qa_options_cache, $qa_options_pending;
		
		if ($todatabase && isset($value))
			qa_db_set_option($db, $name, $value);

		$qa_options_cache[$name]=$value;
		unset($qa_options_pending[$name]);
	}

	
	function qa_reset_options($db, $names)
/*
	Reset the options in $names
*/
	{
		foreach ($names as $name)
			qa_set_option($db, $name, qa_default_option($db, $name));
	}

	
	function qa_default_option($db, $name)
/*
	Return the default value for option $name
*/
	{
		global $qa_root_url_inferred;
		
		$fixed_defaults=array(
			'allow_multi_answers' => 1,
			'captcha_on_anon_post' => 1,
			'captcha_on_feedback' => 1,
			'captcha_on_register' => 1,
			'captcha_on_reset_password' => 1,
			'captcha_on_unconfirmed' => 0,
			'columns_tags' => 3,
			'columns_users' => 2,
			'comment_on_as' => 1,
			'comment_on_qs' => 0,
			'confirm_user_emails' => 1,
			'do_ask_check_qs' => 0,
			'do_complete_tags' => 1,
			'do_example_tags' => 1,
			'do_related_qs' => 1,
			'feed_for_activity' => 1,
			'feed_for_qa' => 1,
			'feed_for_questions' => 1,
			'feed_for_unanswered' => 1,
			'feed_full_text' => 1,
			'feed_number_items' => 50,
			'feed_per_category' => 1,
			'feedback_enabled' => 1,
			'follow_on_as' => 1,
			'match_ask_check_qs' => 3,
			'match_example_tags' => 3,
			'match_related_qs' => 3,
			'max_len_q_title' => 120,
			'max_num_q_tags' => 5,
			'max_rate_ip_as' => 150,
			'max_rate_ip_cs' => 100,
			'max_rate_ip_logins' => 100,
			'max_rate_ip_qs' => 50,
			'max_rate_ip_votes' => 1500,
			'max_rate_user_as' => 30,
			'max_rate_user_cs' => 20,
			'max_rate_user_qs' => 10,
			'max_rate_user_votes' => 300,
			'min_len_a_content' => 12,
			'min_len_c_content' => 12,
			'min_len_q_content' => 0,
			'min_len_q_title' => 12,
			'min_num_q_tags' => 0,
			'nav_ask' => 1,
			'nav_qa_not_home' => 1,
			'nav_questions' => 1,
			'nav_tags' => 1,
			'nav_unanswered' => 1,
			'nav_users' => 1,
			'neat_urls' => QA_URL_FORMAT_SAFEST,
			'page_size_ask_check_qs' => 5,
			'page_size_ask_tags' => 5,
			'page_size_home' => 20,
			'page_size_qs' => 20,
			'page_size_related_qs' => 5,
			'page_size_search' => 10,
			'page_size_tag_qs' => 20,
			'page_size_tags' => 30,
			'page_size_una_qs' => 20,
			'page_size_user_as' => 20,
			'page_size_user_qs' => 20,
			'page_size_users' => 20,
			'pages_prev_next' => 3,
			'permit_anon_view_ips' => QA_PERMIT_EDITORS,
			'permit_delete_hidden' => QA_PERMIT_MODERATORS,
			'permit_edit_a' => QA_PERMIT_EXPERTS,
			'permit_edit_c' => QA_PERMIT_EDITORS,
			'permit_edit_q' => QA_PERMIT_EDITORS,
			'permit_hide_show' => QA_PERMIT_EDITORS,
			'permit_select_a' => QA_PERMIT_EXPERTS,
			'permit_vote_a' => QA_PERMIT_USERS,
			'permit_vote_q' => QA_PERMIT_USERS,
			'points_a_selected' => 30,
			'points_a_voted_max_gain' => 20,
			'points_a_voted_max_loss' => 5,
			'points_base' => 100,
			'points_multiple' => 10,
			'points_per_a_voted' => 2,
			'points_per_q_voted' => 1,
			'points_post_a' => 4,
			'points_post_q' => 2,
			'points_q_voted_max_gain' => 10,
			'points_q_voted_max_loss' => 3,
			'points_select_a' => 3,
			'show_c_reply_buttons' => 1,
			'show_a_form_immediate' => 'if_no_as',
			'show_selected_first' => 1,
			'show_url_links' => 1,
			'show_user_points' => 1,
			'show_when_created' => 1,
			'site_theme' => 'Default',
			'sort_answers_by' => 'created',
			'tags_or_categories' => 'tc',
			'voting_on_as' => 1,
			'voting_on_qs' => 1,
		);
		
		if (isset($fixed_defaults[$name]))
			$value=$fixed_defaults[$name];
			
		else
			switch ($name) {
				case 'site_url':
					$value=$qa_root_url_inferred; // from qa-index.php
					break;
					
				case 'site_title':
					$value=qa_default_site_title();
					break;
					
				case 'from_email': // heuristic to remove short prefix (e.g. www. or qa.)
					$parts=explode('.', @$_SERVER['HTTP_HOST']);
					
					if ( (count($parts)>2) && (strlen($parts[0])<5) && !is_numeric($parts[0]) )
						unset($parts[0]);
						
					$value='no-reply@'.((count($parts)>1) ? implode('.', $parts) : 'example.com');
					break;
					
				case 'email_privacy':
					$value=qa_lang_html('options/default_privacy');
					break;
				
				case 'show_custom_sidebar':
					$value=strlen(qa_get_option($db, 'custom_sidebar')) ? true : false;
					break;
					
				case 'show_custom_header':
					$value=strlen(qa_get_option($db, 'custom_header')) ? true : false;
					break;
					
				case 'show_custom_footer':
					$value=strlen(qa_get_option($db, 'custom_footer')) ? true : false;
					break;
					
				case 'show_custom_in_head':
					$value=strlen(qa_get_option($db, 'custom_in_head')) ? true : false;
					break;
					
				case 'custom_sidebar':
					$value=qa_lang_sub('options/default_sidebar', qa_html(qa_get_option($db, 'site_title')));
					break;
					
				case 'permit_post_q': // convert from deprecated option if available
					$value=qa_get_option($db, 'ask_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
					break;
					
				case 'permit_post_a': // convert from deprecated option if available
					$value=qa_get_option($db, 'answer_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
					break;
				
				case 'permit_post_c': // convert from deprecated option if available
					$value=qa_get_option($db, 'comment_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
					break;
					
				case 'points_vote_up_q':
				case 'points_vote_down_q':
					$oldvalue=qa_get_option($db, 'points_vote_on_q');
					$value=is_numeric($oldvalue) ? $oldvalue : 1;
					break;
					
				case 'points_vote_up_a':
				case 'points_vote_down_a':
					$oldvalue=qa_get_option($db, 'points_vote_on_a');
					$value=is_numeric($oldvalue) ? $oldvalue : 1;
					break;
				
				default:
					$value='';
					break;
			}
		
		return $value;
	}

	
	function qa_default_site_title()
/*
	Return a heuristic guess at the name of the site from the HTTP HOST
*/
	{
		$parts=explode('.', @$_SERVER['HTTP_HOST']);

		$longestpart='';
		foreach ($parts as $part)
			if (strlen($part)>strlen($longestpart))
				$longestpart=$part;
			
		return ((strlen($longestpart)>3) ? (ucfirst($longestpart).' ') : '').qa_lang('options/default_suffix');
	}

	
	function qa_get_vote_view($db, $basetype, $full=false, $enabledif=true)
/*
	Return $voteview parameter to pass to qa_post_html_fields() in qa-app-format.php for posts of $basetype (Q/A/C),
	with buttons enabled if appropriate (based on whether $full post shown) unless $enabledif is false.
*/
	{
		if ($basetype=='Q') {
			$view=qa_get_option($db, 'voting_on_qs');
			$enabled=$enabledif && $view && ($full || !qa_get_option($db, 'voting_on_q_page_only'));

		} elseif ($basetype=='A') {
			$view=qa_get_option($db, 'voting_on_as');
			$enabled=$enabledif;
			
		} else
			$view=false;
		
		return $view ? (qa_get_option($db, 'votes_separated') ? ($enabled ? 'updown' : 'updown-disabled') : ($enabled ? 'net' : 'net-disabled')) : false;
	}
	
	
	function qa_using_tags($db)
/*
	Return whether the option is set to classify questions by tags
*/
	{
		return strpos(qa_get_option($db, 'tags_or_categories'), 't')!==false;
	}
	
	
	function qa_using_categories($db)
/*
	Return whether the option is set to classify questions by categories
*/
	{
		return strpos(qa_get_option($db, 'tags_or_categories'), 'c')!==false;
	}
	
	
	function qa_get_block_words_preg($db)
/*
	Return the regular expression to match the blocked words options set in the database
*/
	{
		global $qa_blockwordspreg, $qa_blockwordspreg_set;
		
		if (!@$qa_blockwordspreg_set) {
			$blockwordstring=qa_get_option($db, 'block_bad_words');
			
			if (strlen($blockwordstring)) {
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				$qa_blockwordspreg=qa_block_words_to_preg($blockwordstring);

			} else
				$qa_blockwordspreg=null;
			
			$qa_blockwordspreg_set=true;
		}
		
		return $qa_blockwordspreg;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/