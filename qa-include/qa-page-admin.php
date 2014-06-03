<?php
	
/*
	Question2Answer 1.0.1-beta (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin.php
	Version: 1.0.1-beta
	Date: 2010-05-11 12:36:30 GMT
	Description: Controller for most admin pages which just contain options


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

	require_once QA_INCLUDE_DIR.'qa-db-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	

//	Standard pre-admin operations

	qa_admin_pending();
	
	if (!qa_admin_check_privileges())
		return;


//	Define the options to show (and some other visual stuff) based on request
	
	$formstyle='tall';
	
	switch ($qa_template) {
		case 'admin/layout':
			$subtitle='admin/layout_title';
			$showoptions=array('logo_show', 'logo_url', 'logo_width', 'logo_height', 'nav_unanswered', 'custom_sidebar', 'custom_header', 'custom_footer', 'custom_in_head');
			break;
			
		case 'admin/viewing':
			$subtitle='admin/viewing_title';
			$showoptions=array(
				'voting_on_qs', 'voting_on_as', 'votes_separated', '', 'page_size_home', 'page_size_qs', 'page_size_una_qs', '',
				'page_size_tags', 'columns_tags', 'page_size_users', 'columns_users', '',
				'page_size_tag_qs', 'page_size_user_qs', 'page_size_user_as', 'page_size_search', '',
				'show_user_points', 'show_url_links', '', 'do_related_qs', 'match_related_qs', 'page_size_related_qs', '',
				'pages_prev_next'
			);
			$formstyle='wide';
			break;
		
		case 'admin/posting':
			$subtitle='admin/posting_title';
			$showoptions=array(
				'comment_on_qs', 'comment_on_as', 'follow_on_as', '',
				'ask_needs_login', 'answer_needs_login', 'comment_needs_login', '',
				'min_len_q_title', 'min_len_q_content', 'min_len_a_content', 'min_len_c_content', '',
				'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs', '',
				'do_example_tags', 'match_example_tags', 'do_complete_tags', 'page_size_ask_tags'
			);
			$formstyle='wide';
			break;
			
		case 'admin/emails':
			$subtitle='admin/emails_title';
			$showoptions=array('from_email', 'notify_admin_q_post', 'feedback_enabled', 'feedback_email', 'email_privacy');
			
			if (!QA_EXTERNAL_USERS)
				$showoptions[]='custom_welcome';
			break;
			
		case 'admin/spam':
			$subtitle='admin/spam_title';
			
			$showoptions=array();
			
			$getoptions=qa_get_options($qa_db, array('ask_needs_login', 'answer_needs_login', 'comment_needs_login', 'feedback_enabled'));
			
			if (!($getoptions['ask_needs_login'] && $getoptions['answer_needs_login'] && $getoptions['comment_needs_login']))
				$showoptions[]='captcha_on_anon_post';
				
			if (!QA_EXTERNAL_USERS) {
				$showoptions[]='captcha_on_register';
				$showoptions[]='captcha_on_reset_password';
			}
			
			if ($getoptions['feedback_enabled'])
				$showoptions[]='captcha_on_feedback';
			
			if (count($showoptions))
				array_push($showoptions, 'recaptcha_public_key', 'recaptcha_private_key', '');
			
			array_push($showoptions,
				'max_rate_ip_qs', 'max_rate_ip_as', 'max_rate_ip_cs', 'max_rate_ip_votes', '',
				'max_rate_user_qs', 'max_rate_user_as', 'max_rate_user_cs', 'max_rate_user_votes'
			);

			$formstyle='wide';
			break;
		
		default:
			$subtitle='admin/general_title';
			$showoptions=array('site_title', 'site_url', 'neat_urls', 'site_language', 'site_theme');
			break;
	}
	

//	Option types and maxima
	
	$optiontype=array(
		'columns_tags' => 'number',
		'columns_users' => 'number',
		'logo_height' => 'number-blank',
		'logo_width' => 'number-blank',
		'max_rate_ip_as' => 'number',
		'max_rate_ip_cs' => 'number',
		'max_rate_ip_qs' => 'number',
		'max_rate_ip_votes' => 'number',
		'max_rate_user_as' => 'number',
		'max_rate_user_cs' => 'number',
		'max_rate_user_qs' => 'number',
		'max_rate_user_votes' => 'number',
		'min_len_a_content' => 'number',
		'min_len_c_content' => 'number',
		'min_len_q_content' => 'number',
		'min_len_q_title' => 'number',
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
		
		'answer_needs_login' => 'checkbox',
		'ask_needs_login' => 'checkbox',
		'captcha_on_anon_post' => 'checkbox',
		'captcha_on_feedback' => 'checkbox',
		'captcha_on_register' => 'checkbox',
		'captcha_on_reset_password' => 'checkbox',
		'comment_needs_login' => 'checkbox',
		'comment_on_as' => 'checkbox',
		'comment_on_qs' => 'checkbox',
		'do_ask_check_qs' => 'checkbox',
		'do_complete_tags' => 'checkbox',
		'do_example_tags' => 'checkbox',
		'do_related_qs' => 'checkbox',
		'feedback_enabled' => 'checkbox',
		'follow_on_as' => 'checkbox',
		'logo_show' => 'checkbox',
		'nav_unanswered' => 'checkbox',
		'neat_urls' => 'checkbox',
		'notify_admin_q_post' => 'checkbox',
		'show_url_links' => 'checkbox',
		'show_user_points' => 'checkbox',
		'votes_separated' => 'checkbox',
		'voting_on_as' => 'checkbox',
		'voting_on_qs' => 'checkbox',
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
	);
	
	$optionminimum=array(
		'page_size_ask_check_qs' => 3,
		'page_size_ask_tags' => 3,
		'page_size_home' => 3,
		'page_size_qs' => 3,
		'page_size_search' => 3,
		'page_size_tag_qs' => 3,
		'page_size_tags' => 3,
		'page_size_users' => 3,
	);
	

//	Filter out blanks and get/set appropriate options
	
	$getoptions=array();
	foreach ($showoptions as $optionname)
		if (!empty($optionname)) // empties represent spacers in forms
			$getoptions[]=$optionname;
	
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

	foreach ($showoptions as $optionname)
		if (empty($optionname)) {
			$qa_content['form']['fields'][]=array(
				'type' => 'blank'
			);
		
		} else {
			$type=@$optiontype[$optionname];
			if ($type=='number-blank')
				$type='number';
			
			$optionfield=array(
				'label' => qa_lang_html('options/'.$optionname),
				'tags' => ' NAME="option_'.$optionname.'" ',
				'value' => qa_html($options[$optionname]),
				'type' => $type,
			);
			
			if (isset($optionmaximum[$optionname]))
				$optionfield['note']=qa_lang_sub_html('admin/maximum_x', $optionmaximum[$optionname]);
			
			switch ($optionname) { // special treatment for certain options
				case 'site_language':
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					
					$languagevalue=$options[$optionname];
					$languageoptions=qa_admin_language_options();
					
					$optionfield['type']='select';
					$optionfield['options']=$languageoptions;
					$optionfield['value']=$languageoptions[isset($languageoptions[$languagevalue]) ? $languagevalue : 'en-US'];
					
					if (!qa_has_multibyte())
						$optionfield['error']=qa_lang_html('admin/no_multibyte');
					break;
					
				case 'neat_urls':
					$neatvalue=$options[$optionname];
					$neatoptions=array();

					$rawoptions=array(
						QA_URL_FORMAT_NEAT,
						QA_URL_FORMAT_INDEX,
						QA_URL_FORMAT_QUERY,
						QA_URL_FORMAT_PARAM,
						QA_URL_FORMAT_PARAMS,
						QA_URL_FORMAT_SAFEST,
					);
					
					foreach ($rawoptions as $rawoption)
						$neatoptions[$rawoption]=
							'<IFRAME SRC="'.qa_path_html('url/test/$&-_#%@', array('dummy' => '', 'param' => '$&-_#%@'), null, $rawoption).'" WIDTH="20" HEIGHT="16" STYLE="vertical-align:middle; border:0" SCROLLING="no" FRAMEBORDER="0"></IFRAME>&nbsp;'.
							'<SMALL>'.
							qa_path_html('questions/123/why-do-birds-sing', null, '/', $rawoption).
							(($rawoption==QA_URL_FORMAT_NEAT) ? strtr(qa_lang_html('admin/neat_urls_note'), array(
								'^1' => '<A HREF="http://www.question2answer.org/htaccess.php" TARGET="_blank">',
								'^2' => '</A>',
							)) : '').
							'</SMALL>';
							
					$optionfield['type']='select-radio';
					$optionfield['options']=$neatoptions;
					$optionfield['value']=$neatoptions[isset($neatoptions[$neatvalue]) ? $neatvalue : QA_URL_FORMAT_SAFE];
					$optionfield['note']=qa_lang_sub_html('admin/url_format_note', '<SPAN STYLE=" '.qa_admin_url_test_html().'/SPAN>');
					break;
					
				case 'site_theme':
					$themevalue=$options[$optionname];
					$themeoptions=qa_admin_theme_options();
					
					$optionfield['type']='select';
					$optionfield['options']=$themeoptions;
					$optionfield['value']=$themeoptions[isset($themeoptions[$themevalue]) ? $themevalue : 'Default'];
					break;
				
				case 'logo_show':
					$optionfield['tags'].=' ID="logo_show" ';
					
					qa_checkbox_to_display($qa_content, array(
						'logo_url' => 'logo_show',
						'logo_width' => 'logo_show',
						'logo_height' => 'logo_show',
					));
					break;
					
				case 'feedback_enabled':
					$optionfield['tags'].=' ID="feedback_enabled" ';
					
					qa_checkbox_to_display($qa_content, array(
						'feedback_email' => 'feedback_enabled || notify_admin_q_post',
					));
					break;
					
				case 'notify_admin_q_post':
					$optionfield['tags'].=' ID="notify_admin_q_post" ';
					break;
					
				case 'voting_on_qs':
					$optionfield['tags'].=' ID="voting_on_qs" ';
					
					qa_checkbox_to_display($qa_content, array(
						'votes_separated' => 'voting_on_qs || voting_on_as',
					));
					break;
					
				case 'voting_on_as':
					$optionfield['tags'].=' ID="voting_on_as" ';
					break;

				case 'comment_on_qs':
					$optionfield['tags'].=' ID="comment_on_qs" ';
					
					qa_checkbox_to_display($qa_content, array(
						'comment_needs_login' => 'comment_on_qs || comment_on_as',
						'min_len_c_content' => 'comment_on_qs || comment_on_as',
					));
					break;
					
				case 'comment_on_as':
					$optionfield['tags'].=' ID="comment_on_as" ';
					break;
					
				case 'logo_url':
				case 'logo_width':
				case 'logo_height':
				case 'page_size_related_qs':
				case 'page_size_ask_check_qs':
				case 'page_size_ask_tags':
				case 'comment_needs_login':
				case 'min_len_c_content':
				case 'votes_separated':
				case 'feedback_email':
					$optionfield['id']=$optionname; // this wraps ID around table rows instead of assigning it to input element
					break;
					
				case 'custom_sidebar':
					$optionfield['rows']=6;
					break;
				
				case 'custom_header':
				case 'custom_footer':
				case 'custom_in_head':
				case 'custom_welcome':
					$optionfield['rows']=3;
					break;
				
				case 'pages_prev_next':
					$optionfield['type']='select';
					$optionfield['options']=array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
					break;
	
				case 'columns_tags':
				case 'columns_users':
					$optionfield['type']='select';
					$optionfield['options']=array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
					break;
					
				case 'do_related_qs':
					$optionfield['tags'].=' ID="do_related_qs" ';
					
					qa_checkbox_to_display($qa_content, array(
						'match_related_qs' => 'do_related_qs',
						'page_size_related_qs' => 'do_related_qs',
					));
					break;
					
				case 'do_ask_check_qs':
					$optionfield['tags'].=' ID="do_ask_check_qs" ';
					
					qa_checkbox_to_display($qa_content, array(
						'match_ask_check_qs' => 'do_ask_check_qs',
						'page_size_ask_check_qs' => 'do_ask_check_qs',
					));
					break;
					
				case 'do_example_tags':
					$optionfield['tags'].=' ID="do_example_tags" ';
					
					qa_checkbox_to_display($qa_content, array(
						'match_example_tags' => 'do_example_tags',
						'page_size_ask_tags' => 'do_example_tags || do_complete_tags',
					));
					break;
					
				case 'do_complete_tags':
					$optionfield['tags'].=' ID="do_complete_tags" ';
					break;

				case 'match_related_qs':
				case 'match_ask_check_qs':
				case 'match_example_tags':
					$matchvalue=$options[$optionname];
					$matchoptions=qa_admin_match_options();
					
					$optionfield['id']=$optionname;
					$optionfield['type']='select';
					$optionfield['options']=$matchoptions;
					$optionfield['value']=$matchoptions[isset($matchoptions[$matchvalue]) ? $matchvalue : 3];
					break;
					
				case 'captcha_on_register':
				case 'captcha_on_anon_post':
					$optionfield['tags'].=' ID="'.$optionname.'" ';
					break;
					
				case 'recaptcha_public_key':
					$optionfield['style']='tall';
					$optionfield['id']=$optionname;
					
					qa_checkbox_to_display($qa_content, array(
						'recaptcha_public_key' => 'captcha_on_register || captcha_on_anon_post',
						'recaptcha_private_key' => 'captcha_on_register || captcha_on_anon_post',
					));
					break;
					
				case 'recaptcha_private_key':
					require_once QA_INCLUDE_DIR.'qa-app-captcha.php';

					$optionfield['style']='tall';
					$optionfield['id']=$optionname;
					$optionfield['error']=qa_captcha_error($qa_db);
					break;
			}

			$qa_content['form']['fields'][$optionname]=$optionfield;
		}
		
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

?>