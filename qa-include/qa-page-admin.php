<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for most admin pages which just contain options


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

	require_once QA_INCLUDE_DIR.'qa-db-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	

//	Queue requests for pending admin options

	qa_admin_pending();
	

//	Define the options to show (and some other visual stuff) based on request
	
	$formstyle='tall';
	$checkboxtodisplay=null;
	
	switch (@$qa_request_lc_parts[1]) {
		case 'emails':
			$subtitle='admin/emails_title';
			$showoptions=array('from_email', 'feedback_email', 'notify_admin_q_post', 'feedback_enabled', 'email_privacy');
			
			if (!QA_EXTERNAL_USERS)
				$showoptions[]='custom_welcome';
			break;
			
		case 'layout':
			$subtitle='admin/layout_title';
			$showoptions=array('logo_show', 'logo_url', 'logo_width', 'logo_height', 'show_custom_sidebar', 'custom_sidebar', 'show_custom_sidepanel', 'custom_sidepanel', 'show_custom_header', 'custom_header', 'show_custom_footer', 'custom_footer', 'show_custom_in_head', 'custom_in_head', 'show_custom_home', 'custom_home_heading', 'custom_home_content', 'show_home_description', 'home_description');
			
			$checkboxtodisplay=array(
				'logo_url' => 'option_logo_show',
				'logo_width' => 'option_logo_show',
				'logo_height' => 'option_logo_show',
				'custom_sidebar' => 'option_show_custom_sidebar',
				'custom_sidepanel' => 'option_show_custom_sidepanel',
				'custom_header' => 'option_show_custom_header',
				'custom_footer' => 'option_show_custom_footer',
				'custom_in_head' => 'option_show_custom_in_head',
				'custom_home_heading' => 'option_show_custom_home',
				'custom_home_content' => 'option_show_custom_home',
				'home_description' => 'option_show_home_description',
			);
			break;
			
		case 'viewing':
			$getoptions=qa_get_options($qa_db, array('tags_or_categories'));

			$subtitle='admin/viewing_title';
			$showoptions=array('voting_on_qs', 'voting_on_q_page_only', 'voting_on_as', 'votes_separated', '', 'page_size_home', 'page_size_qs', 'page_size_una_qs', '');
			
			if (qa_using_tags($qa_db))
				array_push($showoptions, 'page_size_tags', 'columns_tags');
				
			array_push($showoptions, 'page_size_users', 'columns_users', '');
			
			if (qa_using_tags($qa_db))
				$showoptions[]='page_size_tag_qs';
				
			array_push($showoptions,
				'page_size_user_qs', 'page_size_user_as', 'page_size_search', '',
				'show_url_links', 'show_when_created', 'show_user_points', '',
				'sort_answers_by', 'show_selected_first', 'show_a_form_immediate', 'show_c_reply_buttons', '',
				'do_related_qs', 'match_related_qs', 'page_size_related_qs', '', 'pages_prev_next'
			);

			$formstyle='wide';

			$checkboxtodisplay=array(
				'votes_separated' => 'option_voting_on_qs || option_voting_on_as',
				'voting_on_q_page_only' => 'option_voting_on_qs',
				'match_related_qs' => 'option_do_related_qs',
				'page_size_related_qs' => 'option_do_related_qs',
			);
			break;
		
		case 'posting':
			$getoptions=qa_get_options($qa_db, array('tags_or_categories'));
			
			$subtitle='admin/posting_title';

			$showoptions=array('allow_multi_answers', 'comment_on_qs', 'comment_on_as', 'follow_on_as', '', 'min_len_q_title', 'max_len_q_title');
			
			$showoptions[]='min_len_q_content';
			
			if (qa_using_tags($qa_db))
				array_push($showoptions, 'min_num_q_tags', 'max_num_q_tags');
			
			array_push($showoptions, 'min_len_a_content', 'min_len_c_content', 'block_bad_words', '', 'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs', '');

			if (qa_using_tags($qa_db))
				array_push($showoptions, 'do_example_tags', 'match_example_tags', 'do_complete_tags', 'page_size_ask_tags');

			$formstyle='wide';

			$checkboxtodisplay=array(
				'min_len_c_content' => 'option_comment_on_qs || option_comment_on_as',
				'match_ask_check_qs' => 'option_do_ask_check_qs',
				'page_size_ask_check_qs' => 'option_do_ask_check_qs',
				'match_example_tags' => 'option_do_example_tags',
				'page_size_ask_tags' => 'option_do_example_tags || option_do_complete_tags',
			);
			break;
			
		case 'permissions':
			$subtitle='admin/permissions_title';
			
			$showoptions=array('permit_post_q', 'permit_post_a');
			
			$getoptions=qa_get_options($qa_db, array('comment_on_qs', 'comment_on_as', 'voting_on_qs', 'voting_on_as'));
			
			if ($getoptions['comment_on_qs'] || $getoptions['comment_on_as'])
				$showoptions[]='permit_post_c';
			
			if ($getoptions['voting_on_qs'])
				$showoptions[]='permit_vote_q';
				
			if ($getoptions['voting_on_as'])
				$showoptions[]='permit_vote_a';
				
			array_push($showoptions, 'permit_edit_q', 'permit_edit_a');
			
			if ($getoptions['comment_on_qs'] || $getoptions['comment_on_as'])
				$showoptions[]='permit_edit_c';
				
			array_push($showoptions, 'permit_select_a', 'permit_anon_view_ips', 'permit_hide_show', 'permit_delete_hidden');

			$formstyle='wide';
			break;
		
		case 'feeds':
			$subtitle='admin/feeds_title';
			
			$showoptions=array('feed_for_qa', 'feed_for_questions', 'feed_for_unanswered', 'feed_for_activity');
			
			if (qa_using_categories($qa_db))
				$showoptions[]='feed_per_category';
			
			if (qa_using_tags($qa_db))
				$showoptions[]='feed_for_tag_qs';
				
			array_push($showoptions, 'feed_for_search', 'feed_number_items', 'feed_full_text');
							
			$formstyle='wide';

			$checkboxtodisplay=array(
				'feed_per_category' => 'option_feed_for_qa || option_feed_for_questions || option_feed_for_unanswered || option_feed_for_activity',
			);
			break;
		
		case 'spam':
			$subtitle='admin/spam_title';
			
			$showoptions=array();
			
			$getoptions=qa_get_options($qa_db, array('feedback_enabled', 'permit_post_q', 'permit_post_a', 'permit_post_c'));
			
			if (!QA_EXTERNAL_USERS)
				array_push($showoptions, 'confirm_user_emails', '');
			
			$maxpermitpost=max($getoptions['permit_post_q'], $getoptions['permit_post_a'], $getoptions['permit_post_c']);
			
			if ($maxpermitpost > QA_PERMIT_USERS)
				$showoptions[]='captcha_on_anon_post';
				
			if ($maxpermitpost > QA_PERMIT_CONFIRMED)
				$showoptions[]='captcha_on_unconfirmed';
				
			if (!QA_EXTERNAL_USERS)
				array_push($showoptions, 'captcha_on_register', 'captcha_on_reset_password');
			
			if ($getoptions['feedback_enabled'])
				$showoptions[]='captcha_on_feedback';
				
			if (count($showoptions))
				array_push($showoptions, 'recaptcha_public_key', 'recaptcha_private_key', '');
				
			array_push($showoptions,
				'max_rate_ip_qs', 'max_rate_ip_as', 'max_rate_ip_cs', 'max_rate_ip_votes', 'max_rate_ip_logins', 'block_ips_write', '',
				'max_rate_user_qs', 'max_rate_user_as', 'max_rate_user_cs', 'max_rate_user_votes'
			);

			$formstyle='wide';

			if ($maxpermitpost > QA_PERMIT_USERS)
				$checkboxtodisplay=array(
					'captcha_on_unconfirmed' => 'option_confirm_user_emails && option_captcha_on_anon_post',
					'recaptcha_public_key' => 'option_captcha_on_register || option_captcha_on_anon_post || option_captcha_on_reset_password || option_captcha_on_feedback',
					'recaptcha_private_key' => 'option_captcha_on_register || option_captcha_on_anon_post || option_captcha_on_reset_password || option_captcha_on_feedback',
				);
			else
				$checkboxtodisplay=array(
					'captcha_on_unconfirmed' => 'option_confirm_user_emails',
					'recaptcha_public_key' => 'option_captcha_on_register || option_captcha_on_unconfirmed || option_captcha_on_reset_password || option_captcha_on_feedback',
					'recaptcha_private_key' => 'option_captcha_on_register || option_captcha_on_unconfirmed || option_captcha_on_reset_password || option_captcha_on_feedback',
				);
			break;
		
		default:
			$subtitle='admin/general_title';
			$showoptions=array('site_title', 'site_url', 'neat_urls', 'site_language', 'site_theme', 'tags_or_categories');
			
			qa_options_set_pending(array('cache_tagcount'));
			break;
	}
	

//	Option types and maxima
	
	$optiontype=array(
		'columns_tags' => 'number',
		'columns_users' => 'number',
		'feed_number_items' => 'number',
		'logo_height' => 'number-blank',
		'logo_width' => 'number-blank',
		'max_rate_ip_as' => 'number',
		'max_rate_ip_cs' => 'number',
		'max_rate_ip_qs' => 'number',
		'max_rate_ip_votes' => 'number',
		'max_rate_ip_logins' => 'number',
		'max_rate_user_as' => 'number',
		'max_rate_user_cs' => 'number',
		'max_rate_user_qs' => 'number',
		'max_rate_user_votes' => 'number',
		'min_len_a_content' => 'number',
		'min_len_c_content' => 'number',
		'min_len_q_content' => 'number',
		'min_len_q_title' => 'number',
		'min_num_q_tags' => 'number',
		'max_len_q_title' => 'number',
		'max_num_q_tags' => 'number',
		'page_size_ask_check_qs' => 'number',
		'page_size_ask_tags' => 'number',
		'page_size_home' => 'number',
		'page_size_qs' => 'number',
		'page_size_related_qs' => 'number',
		'page_size_search' => 'number',
		'page_size_tag_qs' => 'number',
		'page_size_tags' => 'number',
		'page_size_una_qs' => 'number',
		'page_size_user_as' => 'number',
		'page_size_user_qs' => 'number',
		'page_size_users' => 'number',
		'pages_prev_next' => 'number',
		
		'captcha_on_anon_post' => 'checkbox',
		'captcha_on_feedback' => 'checkbox',
		'captcha_on_register' => 'checkbox',
		'captcha_on_reset_password' => 'checkbox',
		'captcha_on_unconfirmed' => 'checkbox',
		'comment_on_as' => 'checkbox',
		'comment_on_qs' => 'checkbox',
		'confirm_user_emails' => 'checkbox',
		'feed_for_qa' => 'checkbox',
		'feed_for_questions' => 'checkbox',
		'feed_for_unanswered' => 'checkbox',
		'feed_for_activity' => 'checkbox',
		'feed_for_search' => 'checkbox',
		'feed_per_category' => 'checkbox',
		'feed_for_tag_qs' => 'checkbox',
		'show_c_reply_buttons' => 'checkbox',
		'show_custom_sidebar' => 'checkbox',
		'show_custom_sidepanel' => 'checkbox',
		'show_custom_header' => 'checkbox',
		'show_custom_footer' => 'checkbox',
		'show_custom_in_head' => 'checkbox',
		'show_custom_home' => 'checkbox',
		'show_home_description' => 'checkbox',
		'allow_multi_answers' => 'checkbox',
		'do_ask_check_qs' => 'checkbox',
		'do_complete_tags' => 'checkbox',
		'do_example_tags' => 'checkbox',
		'do_related_qs' => 'checkbox',
		'feedback_enabled' => 'checkbox',
		'feed_full_text' => 'checkbox',
		'follow_on_as' => 'checkbox',
		'logo_show' => 'checkbox',
		'neat_urls' => 'checkbox',
		'notify_admin_q_post' => 'checkbox',
		'show_url_links' => 'checkbox',
		'show_user_points' => 'checkbox',
		'show_selected_first' => 'checkbox',
		'show_when_created' => 'checkbox',
		'votes_separated' => 'checkbox',
		'voting_on_as' => 'checkbox',
		'voting_on_qs' => 'checkbox',
		'voting_on_q_page_only' => 'checkbox',
	);
	
	$optionmaximum=array(
		'page_size_ask_check_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_ask_tags' => QA_DB_RETRIEVE_QS_AS,
		'page_size_home' => QA_DB_RETRIEVE_QS_AS,
		'page_size_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_related_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_search' => QA_DB_RETRIEVE_QS_AS,
		'page_size_tag_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_tags' => QA_DB_RETRIEVE_TAGS,
		'page_size_una_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_user_as' => QA_DB_RETRIEVE_QS_AS,
		'page_size_user_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_users' => QA_DB_RETRIEVE_USERS,
		'feed_number_items' => QA_DB_RETRIEVE_QS_AS,
		'max_len_q_title' => QA_DB_MAX_TITLE_LENGTH,
	);
	
	$optionminimum=array(
		'max_num_q_tags' => 2,
		'page_size_ask_check_qs' => 3,
		'page_size_ask_tags' => 3,
		'page_size_home' => 3,
		'page_size_qs' => 3,
		'page_size_search' => 3,
		'page_size_tag_qs' => 3,
		'page_size_tags' => 3,
		'page_size_users' => 3,
	);
	

//	Filter out blanks to get list of valid options
	
	$getoptions=array();
	foreach ($showoptions as $optionname)
		if (!empty($optionname)) // empties represent spacers in forms
			$getoptions[]=$optionname;
	
	qa_options_set_pending($getoptions);
	
	$categories=qa_db_select_with_pending($qa_db, qa_db_categories_selectspec());
	

//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges())
		return;


//	Process user actions

	if (qa_clicked('doresetoptions'))
		qa_reset_options($qa_db, $getoptions);

	elseif (qa_clicked('dosaveoptions'))
		foreach ($getoptions as $optionname) {
			$optionvalue=qa_post_text('option_'.$optionname);
			
			if (
				(@$optiontype[$optionname]=='number') ||
				(@$optiontype[$optionname]=='checkbox') ||
				((@$optiontype[$optionname]=='number-blank') && strlen($optionvalue))
			)
				$optionvalue=(int)$optionvalue;
				
			if (isset($optionmaximum[$optionname]))
				$optionvalue=min($optionmaximum[$optionname], $optionvalue);

			if (isset($optionminimum[$optionname]))
				$optionvalue=max($optionminimum[$optionname], $optionvalue);
				
			if ($optionname=='block_ips_write') {
				require_once QA_INCLUDE_DIR.'qa-app-limits.php';
				
				$optionvalue=implode(' , ', qa_block_ips_explode($optionvalue));
			}
			
			if ($optionname=='block_bad_words') {
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				
				$optionvalue=implode(' , ', qa_block_words_explode($optionvalue));
			}
				
			qa_set_option($qa_db, $optionname, $optionvalue);
		}

	$options=qa_get_options($qa_db, $getoptions);

	
//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html($subtitle);
	
	$qa_content['error']=qa_admin_page_error($qa_db);

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => $formstyle,
		
		'fields' => array(),
		
		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html('admin/save_options_button'),
			),
			
			'reset' => array(
				'tags' => ' NAME="doresetoptions" ',
				'label' => qa_lang_html('admin/reset_options_button'),
			),
		),
		
		'hidden' => array(
			'dosaveoptions' => '1' // for IE
		),
	);

	if (qa_clicked('doresetoptions'))
		$qa_content['form']['ok']=qa_lang_html('admin/options_reset');
	elseif (qa_clicked('dosaveoptions'))
		$qa_content['form']['ok']=qa_lang_html('admin/options_saved');
		

	function qa_optionfield_make_select(&$optionfield, $options, $value, $default)
	{
		$optionfield['type']='select';
		$optionfield['options']=$options;
		$optionfield['value']=isset($options[$value]) ? $options[$value] : $options[$default];
	}
	

	foreach ($showoptions as $optionname)
		if (empty($optionname)) {
			$qa_content['form']['fields'][]=array(
				'type' => 'blank'
			);
		
		} else {
			$type=@$optiontype[$optionname];
			if ($type=='number-blank')
				$type='number';
			
			$value=$options[$optionname];
			
			$optionfield=array(
				'id' => $optionname,
				'label' => qa_lang_html('options/'.$optionname),
				'tags' => ' NAME="option_'.$optionname.'" ID="option_'.$optionname.'" ',
				'value' => qa_html($value),
				'type' => $type,
			);
			
			if (isset($optionmaximum[$optionname]))
				$optionfield['note']=qa_lang_html_sub('admin/maximum_x', $optionmaximum[$optionname]);
				
			$feedrequest=null;
			$feedisexample=false;
			
			switch ($optionname) { // special treatment for certain options
				case 'site_language':
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					
					qa_optionfield_make_select($optionfield, qa_admin_language_options(), $value, '');
					
					if (!qa_has_multibyte())
						$optionfield['error']=qa_lang_html('admin/no_multibyte');
					break;
					
				case 'neat_urls':
					$neatoptions=array();

					$rawoptions=array(
						QA_URL_FORMAT_NEAT,
						QA_URL_FORMAT_INDEX,
						QA_URL_FORMAT_PARAM,
						QA_URL_FORMAT_PARAMS,
						QA_URL_FORMAT_SAFEST,
					);
					
					foreach ($rawoptions as $rawoption)
						$neatoptions[$rawoption]=
							'<IFRAME SRC="'.qa_path_html('url/test/'.QA_URL_TEST_STRING, array('dummy' => '', 'param' => QA_URL_TEST_STRING), null, $rawoption).'" WIDTH="20" HEIGHT="16" STYLE="vertical-align:middle; border:0" SCROLLING="no" FRAMEBORDER="0"></IFRAME>&nbsp;'.
							'<SMALL>'.
							qa_path_html('questions/123/why-do-birds-sing', null, '/', $rawoption).
							(($rawoption==QA_URL_FORMAT_NEAT) ? strtr(qa_lang_html('admin/neat_urls_note'), array(
								'^1' => '<A HREF="http://www.question2answer.org/htaccess.php" TARGET="_blank">',
								'^2' => '</A>',
							)) : '').
							'</SMALL>';
							
					qa_optionfield_make_select($optionfield, $neatoptions, $value, QA_URL_FORMAT_SAFEST);
							
					$optionfield['type']='select-radio';
					$optionfield['note']=qa_lang_html_sub('admin/url_format_note', '<SPAN STYLE=" '.qa_admin_url_test_html().'/SPAN>');
					break;
					
				case 'site_theme':
					qa_optionfield_make_select($optionfield, qa_admin_theme_options(), $value, 'Default');
					break;
				
				case 'tags_or_categories':
					qa_optionfield_make_select($optionfield, array(
						'' => qa_lang_html('admin/no_classification'),
						't' => qa_lang_html('admin/tags'),
						'c' => qa_lang_html('admin/categories'),
						'tc' => qa_lang_html('admin/tags_and_categories'),
					), $value, 'tc');

					$optionfield['error']='';
					
					if (qa_get_option($qa_db, 'cache_tagcount') && !qa_using_tags($qa_db))
						$optionfield['error'].=qa_lang_html('admin/tags_not_shown').' ';
					
					if (!qa_using_categories($qa_db))
						foreach ($categories as $category)
							if ($category['qcount']) {
								$optionfield['error'].=qa_lang_html('admin/categories_not_shown');
								break;
							}
					break;
				
				case 'custom_sidebar':
				case 'custom_sidepanel':
				case 'custom_header':
				case 'custom_footer':
				case 'custom_in_head':
				case 'home_description':
					unset($optionfield['label']);
					$optionfield['rows']=6;
					break;
					
				case 'custom_home_content':
					$optionfield['rows']=16;
					break;
					
				case 'custom_welcome':
					$optionfield['rows']=3;
					break;
				
				case 'pages_prev_next':
					qa_optionfield_make_select($optionfield, array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), $value, 3);
					break;
	
				case 'columns_tags':
				case 'columns_users':
					qa_optionfield_make_select($optionfield, array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), $value, 2);
					break;
					
				case 'sort_answers_by':
					qa_optionfield_make_select($optionfield, array(
						'created' => qa_lang_html('options/sort_time'),
						'votes' => qa_lang_html('options/sort_votes'),
					), $value, 'created');
					break;
					
				case 'show_a_form_immediate':
					qa_optionfield_make_select($optionfield, array(
						'always' => qa_lang_html('options/show_always'),
						'if_no_as' => qa_lang_html('options/show_if_no_as'),
						'never' => qa_lang_html('options/show_never'),
					), $value, 'if_no_as');
					break;
					
				case 'match_related_qs':
				case 'match_ask_check_qs':
				case 'match_example_tags':
					qa_optionfield_make_select($optionfield, qa_admin_match_options(), $value, 3);
					break;
					
				case 'block_bad_words':
					$optionfield['style']='tall';
					$optionfield['rows']=4;
					$optionfield['note']=qa_lang_html('admin/block_words_note');
					break;
				
				case 'recaptcha_public_key':
					$optionfield['style']='tall';
					break;
					
				case 'recaptcha_private_key':
					require_once QA_INCLUDE_DIR.'qa-app-captcha.php';

					$optionfield['style']='tall';
					$optionfield['error']=qa_captcha_error($qa_db);
					break;
					
				case 'block_ips_write':
					$optionfield['style']='tall';
					$optionfield['rows']=4;
					$optionfield['note']=qa_lang_html('admin/block_ips_note');
					break;
					
				case 'permit_post_q':
				case 'permit_post_a':
				case 'permit_post_c':
				case 'permit_vote_q':
				case 'permit_vote_a':
				case 'permit_edit_q':
				case 'permit_edit_a':
				case 'permit_edit_c':
				case 'permit_select_a':
				case 'permit_hide_show':
				case 'permit_delete_hidden':
				case 'permit_anon_view_ips':
					if ( ($optionname=='permit_post_q') || ($optionname=='permit_post_a') || ($optionname=='permit_post_c') || ($optionname=='permit_anon_view_ips') )
						$widest=QA_PERMIT_ALL;
					elseif ( ($optionname=='permit_select_a') || ($optionname=='permit_hide_show') )
						$widest=QA_PERMIT_EXPERTS;
					elseif ($optionname=='permit_delete_hidden')
						$widest=QA_PERMIT_EDITORS;
					else
						$widest=QA_PERMIT_USERS;
						
					if ( ($optionname=='permit_edit_c') || ($optionname=='permit_select_a') || ($optionname=='permit_hide_show') || ($optionname=='permit_anon_view_ips') )
						$narrowest=QA_PERMIT_MODERATORS;
					elseif ( ($optionname=='permit_post_c') || ($optionname=='permit_edit_q') || ($optionname=='permit_edit_a') )
						$narrowest=QA_PERMIT_EDITORS;
					elseif ( ($optionname=='permit_vote_q') || ($optionname=='permit_vote_a') )
						$narrowest=QA_PERMIT_CONFIRMED;
					elseif ($optionname=='permit_delete_hidden')
						$narrowest=QA_PERMIT_ADMINS;
					else
						$narrowest=QA_PERMIT_EXPERTS;
					
					$permitoptions=qa_admin_permit_options($widest, $narrowest, (!QA_EXTERNAL_USERS) && qa_get_option($qa_db, 'confirm_user_emails'));
					
					if (count($permitoptions)>1)
						qa_optionfield_make_select($optionfield, $permitoptions, $value,
							($value==QA_PERMIT_CONFIRMED) ? QA_PERMIT_USERS : min(array_keys($permitoptions)));
					else {
						$optionfield['type']='static';
						$optionfield['value']=reset($permitoptions);
					}
					break;
					
				case 'feed_for_qa':
					$feedrequest='qa';
					break;

				case 'feed_for_questions':
					$feedrequest='questions';
					break;

				case 'feed_for_unanswered':
					$feedrequest='unanswered';
					break;

				case 'feed_for_activity':
					$feedrequest='activity';
					break;
					
				case 'feed_per_category':
					if (count($categories)) {
						$category=reset($categories);
						$categoryslug=$category['tags'];

					} else
						$categoryslug='example-category';
						
					if (qa_get_option($qa_db, 'feed_for_qa'))
						$feedrequest='qa';
					elseif (qa_get_option($qa_db, 'feed_for_questions'))
						$feedrequest='questions';
					elseif (qa_get_option($qa_db, 'feed_for_unanswered'))
						$feedrequest='unanswered';
					else
						$feedrequest='activity';
					
					$feedrequest.='/'.$categoryslug;
					$feedisexample=true;
					break;
					
				case 'feed_for_tag_qs':
					$populartags=qa_db_select_with_pending($qa_db, qa_db_popular_tags_selectspec(0, 1));
					
					if (count($populartags)) {
						reset($populartags);
						$feedrequest='tag/'.key($populartags);
					} else
						$feedrequest='tag/singing';
						
					$feedisexample=true;
					break;

				case 'feed_for_search':
					$feedrequest='search/why do birds sing';
					$feedisexample=true;
					break;
			}

			if (isset($feedrequest) && $value)
				$optionfield['note']='<A HREF="'.qa_path_html(qa_feed_request($feedrequest)).'">'.qa_lang_html($feedisexample ? 'admin/feed_link_example' : 'admin/feed_link').'</A>';

			$qa_content['form']['fields'][$optionname]=$optionfield;
		}
		

	if ( (@$qa_request_lc_parts[1]=='permissions')) { // some static items added here

		$qa_content['form']['fields']['permit_block']=array(
			'type' => 'static',
			'label' => qa_lang_html('options/permit_block'),
			'value' => qa_lang_html('options/permit_moderators'),
		);
		
		if (!QA_EXTERNAL_USERS) {
			$qa_content['form']['fields']['permit_create_experts']=array(
				'type' => 'static',
				'label' => qa_lang_html('options/permit_create_experts'),
				'value' => qa_lang_html('options/permit_moderators'),
			);

			$qa_content['form']['fields']['permit_see_emails']=array(
				'type' => 'static',
				'label' => qa_lang_html('options/permit_see_emails'),
				'value' => qa_lang_html('options/permit_admins'),
			);
	
			$qa_content['form']['fields']['permit_create_eds_mods']=array(
				'type' => 'static',
				'label' => qa_lang_html('options/permit_create_eds_mods'),
				'value' => qa_lang_html('options/permit_admins'),
			);
	
			$qa_content['form']['fields']['permit_create_admins']=array(
				'type' => 'static',
				'label' => qa_lang_html('options/permit_create_admins'),
				'value' => qa_lang_html('options/permit_supers'),
			);

		}
	}
	
	if (isset($checkboxtodisplay))
		qa_checkbox_to_display($qa_content, $checkboxtodisplay);
		

	$qa_content['navigation']['sub']=qa_admin_sub_navigation();


/*
	Omit PHP closing tag to help avoid accidental output
*/