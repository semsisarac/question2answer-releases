<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin.php
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

	require_once QA_INCLUDE_DIR.'qa-db-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	
//	Check we have administrative privileges

	if (!qa_admin_check_privileges())
		return;

//	Define the options to show based on request
	
	$formstyle='tall';
	
	switch ($qa_template) {
		case 'admin/layout':
			$subtitle='admin/layout_title';
			$showoptions=array('site_theme', 'logo_show', 'logo_url', 'logo_width', 'logo_height', 'custom_sidebar', 'custom_header', 'custom_footer');
			break;
			
		case 'admin/viewing':
			$subtitle='admin/viewing_title';
			$showoptions=array(
				'page_size_home', 'page_size_qs', /*'page_size_as',*/ '', 'page_size_tags', 'columns_tags', 'page_size_users', 'columns_users', '',
				'page_size_tag_qs', 'page_size_user_qs', 'page_size_user_as', 'page_size_search', '', 'show_url_links', '', 'do_related_qs', 'match_related_qs', 'page_size_related_qs',
				'', 'pages_prev_next'
			);
			$formstyle='wide';
			break;
		
		case 'admin/posting':
			$subtitle='admin/posting_title';
			$showoptions=array('ask_needs_login', 'answer_needs_login', '', 'min_len_q_title', 'min_len_q_content', 'min_len_a_content', '',
			'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs', '', 'do_example_tags', 'match_example_tags', 'do_complete_tags', 'page_size_ask_tags');
			$formstyle='wide';
			break;
			
		case 'admin/emails':
			$subtitle='admin/emails_title';
			$showoptions=array('from_email', 'feedback_email', 'email_privacy');
			
			if (!QA_EXTERNAL_USERS)
				$showoptions[]='custom_welcome';
			break;
			
		case 'admin/limits':
			$subtitle='admin/limits_title';
			$showoptions=array(
				'max_rate_ip_qs', 'max_rate_ip_as', 'max_rate_ip_votes', '', 'max_rate_user_qs', 'max_rate_user_as', 'max_rate_user_votes',
			);
			$formstyle='wide';
			break;
		
		default:
			$subtitle='admin/general_title';
			$showoptions=array('site_title', 'site_url', 'neat_urls', 'site_language');
			break;
	}
	
//	Option types and maxima
	
	$optiontype=array(
		'logo_width' => 'number-blank',
		'logo_height' => 'number-blank',
		'min_len_q_title' => 'number',
		'min_len_q_content' => 'number',
		'min_len_a_content' => 'number',
		'max_rate_ip_qs' => 'number',
		'max_rate_ip_as' => 'number',
		'max_rate_ip_votes' => 'number',
		'max_rate_user_qs' => 'number',
		'max_rate_user_as' => 'number',
		'max_rate_user_votes' => 'number',
		'pages_prev_next' => 'number',
		'page_size_home' => 'number',
		'page_size_qs' => 'number',
		'page_size_as' => 'number',
		'page_size_search' => 'number',
		'page_size_tags' => 'number',
		'page_size_tag_qs' => 'number',
		'page_size_users' => 'number',
		'page_size_user_qs' => 'number',
		'page_size_user_as' => 'number',
		'page_size_related_qs' => 'number',
		'page_size_ask_check_qs' => 'number',
		'page_size_ask_tags' => 'number',
		'columns_tags' => 'number',
		'columns_users' => 'number',
		
		'neat_urls' => 'checkbox',
		'ask_needs_login' => 'checkbox',
		'answer_needs_login' => 'checkbox',
		'do_ask_check_qs' => 'checkbox',
		'do_example_tags' => 'checkbox',
		'do_complete_tags' => 'checkbox',
		'do_related_qs' => 'checkbox',
		'logo_show' => 'checkbox',
		'show_url_links' => 'checkbox',
	);
	
	$optionmaximum=array(
		'page_size_home' => QA_DB_RETRIEVE_QS_AS,
		'page_size_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_as' => QA_DB_RETRIEVE_QS_AS,
		'page_size_search' => QA_DB_RETRIEVE_QS_AS,
		'page_size_tags' => QA_DB_RETRIEVE_TAGS,
		'page_size_tag_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_users' => QA_DB_RETRIEVE_USERS,
		'page_size_user_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_user_as' => QA_DB_RETRIEVE_QS_AS,
		'page_size_related_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_ask_check_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_ask_tags' => QA_DB_RETRIEVE_QS_AS,
	);
	
	$optionminimum=array(
		'page_size_home' => 3,
		'page_size_qs' => 3,
		'page_size_as' => 3,
		'page_size_search' => 3,
		'page_size_tags' => 3,
		'page_size_tag_qs' => 3,
		'page_size_users' => 3,
		'page_size_ask_check_qs' => 3,
		'page_size_ask_tags' => 3,
	);
	
//	Filter out blanks and get/set appropriate options
	
	$getoptions=array();
	foreach ($showoptions as $optionname)
		if (!empty($optionname))
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
					$optionfield['label'].='&nbsp; <IFRAME SRC="'.qa_path_html('rewrite-test', null, null, false).'" WIDTH="200" HEIGHT="22" STYLE="vertical-align:middle; border:1px solid #999;" SCROLLING="no" FRAMEBORDER="0"></IFRAME>';
					break;
					
				case 'site_theme':
					$themevalue=$options[$optionname];
					$themeoptions=qa_admin_theme_options();
					
					$optionfield['type']='select';
					$optionfield['options']=$themeoptions;
					$optionfield['value']=$themeoptions[isset($themeoptions[$themevalue]) ? $themevalue : 'Default'];
					break;
				
				case 'logo_show':
					$optionfield['tags'].=' ID="logo_show" onClick="qa_logo_display();" ';
					
					$qa_content['script_lines'][]=array(
						"function qa_logo_display() {",
						"\tvar d=document.getElementById('logo_show').checked ? '' : 'none';",
						"\tdocument.getElementById('logo_url').style.display=d;",
						"\tdocument.getElementById('logo_width').style.display=d;",
						"\tdocument.getElementById('logo_height').style.display=d;",
						"}",
					);
					
					$qa_content['script_onloads'][]=array('qa_logo_display();');
					break;
				
				case 'logo_url':
				case 'logo_width':
				case 'logo_height':
				case 'page_size_related_qs':
				case 'page_size_ask_check_qs':
				case 'page_size_ask_tags':
					$optionfield['id']=$optionname;
					break;
					
				case 'custom_header':
				case 'custom_footer':
				case 'custom_sidebar':
				case 'custom_welcome':
					$optionfield['rows']=4;
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
					$optionfield['tags'].=' ID="do_related_qs" onClick="qa_related_qs_display();" ';
					
					$qa_content['script_lines'][]=array(
						"function qa_related_qs_display() {",
						"\tvar d=document.getElementById('do_related_qs').checked ? '' : 'none';",
						"\tdocument.getElementById('match_related_qs').style.display=d;",
						"\tdocument.getElementById('page_size_related_qs').style.display=d;",
						"}",
					);
					
					$qa_content['script_onloads'][]=array('qa_related_qs_display();');
					break;
					
				case 'do_ask_check_qs':
					$optionfield['tags'].=' ID="do_ask_check_qs" onClick="qa_ask_check_qs_display();" ';
					
					$qa_content['script_lines'][]=array(
						"function qa_ask_check_qs_display() {",
						"\tvar d=document.getElementById('do_ask_check_qs').checked ? '' : 'none';",
						"\tdocument.getElementById('match_ask_check_qs').style.display=d;",
						"\tdocument.getElementById('page_size_ask_check_qs').style.display=d;",
						"}",
					);
					
					$qa_content['script_onloads'][]=array('qa_ask_check_qs_display();');
					break;
					
				case 'do_example_tags':
					$optionfield['tags'].=' ID="do_example_tags" onClick="qa_tags_display();" ';
					
					$qa_content['script_lines'][]=array(
						"function qa_tags_display() {",
						"\tvar ce=document.getElementById('do_example_tags').checked;",
						"\tvar cc=document.getElementById('do_complete_tags').checked;",
						"\tdocument.getElementById('match_example_tags').style.display=ce ? '' : 'none';",
						"\tdocument.getElementById('page_size_ask_tags').style.display=(cc||ce) ? '' : 'none';",
						"}",
					);
					
					$qa_content['script_onloads'][]=array('qa_tags_display();');
					break;
					
				case 'do_complete_tags':
					$optionfield['tags'].=' ID="do_complete_tags" onClick="qa_tags_display();" ';
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
			}

			$qa_content['form']['fields'][$optionname]=$optionfield;
		}
		
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

?>