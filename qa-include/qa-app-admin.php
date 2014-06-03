<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-admin.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Functions used in the admin center pages


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

	
	function qa_admin_check_privileges()
/*
	Return true if user is logged in with admin privileges. If not, return false
	and set up global $qa_content with the appropriate title and error message
*/
	{
		global $qa_db, $qa_content, $qa_login_userid, $qa_request;
		
		if (!isset($qa_login_userid)) {
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			qa_content_prepare();

			$qa_content['title']=qa_lang_html('admin/admin_title');
			$qa_content['error']=qa_insert_login_links(qa_lang_html('admin/not_logged_in'), $qa_request);
			
			return false;

		} elseif (qa_get_logged_in_level($qa_db)<QA_USER_LEVEL_ADMIN) {
			qa_content_prepare();
			
			$qa_content['title']=qa_lang_html('admin/admin_title');
			$qa_content['error']=qa_lang_html('admin/no_privileges');
			
			return false;
		}
		
		return true;
	}
	
	
	function qa_admin_language_options()
/*
	Return a sorted array of available languages, [short code] => [long name]
*/
	{
		$codetolanguage=array(
			'ar' => 'Arabic - العربية',
			'bg' => 'Bulgarian - Български',
			'ca' => 'Catalan - Català',
			'cs' => 'Czech - Čeština',
			'da' => 'Danish - Dansk',
			'de' => 'German - Deutsch',
			'el' => 'Greek - Ελληνικά',
			'en-GB' => 'English (UK)',
			'es' => 'Spanish - Español',
			'et' => 'Estonian - Eesti',
			'fa' => 'Persian - فارسی',
			'fi' => 'Finnish - Suomi',
			'fr' => 'French - Français',
			'he' => 'Hebrew - עברית',
			'hr' => 'Croatian - Hrvatski',
			'hu' => 'Hungarian - Magyar',
			'id' => 'Indonesian - Bahasa Indonesia',
			'is' => 'Icelandic - Íslenska',
			'it' => 'Italian - Italiano',
			'ja' => 'Japanese - 日本語',
			'ko' => 'Korean - 한국어',
			'lt' => 'Lithuanian - Lietuvių',
			'nl' => 'Dutch - Nederlands',
			'no' => 'Norwegian - Norsk',
			'pl' => 'Polish - Polski',
			'pt' => 'Portuguese - Português',
			'ro' => 'Romanian - Română',
			'ru' => 'Russian - Русский',
			'sk' => 'Slovak - Slovenčina',
			'sl' => 'Slovenian - Slovenščina',
			'sr' => 'Serbian - Српски',
			'sv' => 'Swedish - Svenska',
			'th' => 'Thai - ไทย',
			'tr' => 'Turkish - Türkçe',
			'uk' => 'Ukrainian - Українська',
			'vi' => 'Vietnamese - Tiếng Việt',
			'zh' => 'Chinese - 中文',
		);
		
		$options=array('' => 'English (US)');
		
		$directory=@opendir(QA_LANG_DIR);
		if (is_resource($directory)) {
			while (($code=readdir($directory))!==false)
				if (is_dir(QA_LANG_DIR.$code) && isset($codetolanguage[$code]))
					$options[$code]=$codetolanguage[$code];

			closedir($directory);
		}
		
		asort($options, SORT_STRING);
		
		return $options;
	}
	
	
	function qa_admin_theme_options()
/*
	Return a sorted array of available themes, [theme name] => [theme name]
*/
	{
		$options=array();

		$directory=@opendir(QA_THEME_DIR);
		if (is_resource($directory)) {
			while (($theme=readdir($directory))!==false)
				if ( (substr($theme, 0, 1)!='.') && (file_exists(QA_THEME_DIR.$theme.'/qa-theme.php') || file_exists(QA_THEME_DIR.$theme.'/qa-styles.css')) )
					$options[$theme]=$theme;

			closedir($directory);
		}
		
		asort($options, SORT_STRING);
		
		return $options;
	}

	
	function qa_admin_page_size_options($maximum)
/*
	Return an array of page size options up to $maximum, [page size] => [page size]
*/
	{
		$rawoptions=array(5, 10, 15, 20, 25, 30, 40, 50, 60, 80, 100, 120, 150, 200, 250, 300, 400, 500, 600, 800, 1000);
		
		$options=array();
		foreach ($rawoptions as $rawoption) {
			if ($rawoption>$maximum)
				break;
				
			$options[$rawoption]=$rawoption;
		}
		
		return $options;
	}
	
	
	function qa_admin_match_options()
/*
	Return an array of options representing matching precision, [value] => [label]
*/
	{
		return array(
			5 => qa_lang_html('options/match_5'),
			4 => qa_lang_html('options/match_4'),
			3 => qa_lang_html('options/match_3'),
			2 => qa_lang_html('options/match_2'),
			1 => qa_lang_html('options/match_1'),
		);
	}

	
	function qa_admin_permit_options($widest, $narrowest, $doconfirms)
/*
	Return an array of options representing permission restrictions, [value] => [label]
	ranging from $widest to $narrowest. Set $doconfirms to whether email confirmations are on
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		$options=array(
			QA_PERMIT_ALL => qa_lang_html('options/permit_all'),
			QA_PERMIT_USERS => qa_lang_html('options/permit_users'),
			QA_PERMIT_CONFIRMED => qa_lang_html('options/permit_confirmed'),
			QA_PERMIT_EXPERTS => qa_lang_html('options/permit_experts'),
			QA_PERMIT_EDITORS => qa_lang_html('options/permit_editors'),
			QA_PERMIT_MODERATORS => qa_lang_html('options/permit_moderators'),
			QA_PERMIT_ADMINS => qa_lang_html('options/permit_admins'),
			QA_PERMIT_SUPERS => qa_lang_html('options/permit_supers'),
		);
		
		foreach ($options as $key => $label)
			if (($key<$narrowest) || ($key>$widest))
				unset($options[$key]);
		
		if (!$doconfirms)
			unset($options[QA_PERMIT_CONFIRMED]);
			
		return $options;
	}

	
	function qa_admin_sub_navigation()
/*
	Return the sub navigation structure common to admin pages
*/
	{
		global $qa_db;
		
		$navigation=array(
			'admin$' => array(
				'label' => qa_lang('admin/general_title'),
				'url' => qa_path_html('admin'),
			),
			
			'admin/emails' => array(
				'label' => qa_lang('admin/emails_title'),
				'url' => qa_path_html('admin/emails'),
			),
			
			'admin/layout' => array(
				'label' => qa_lang('admin/layout_title'),
				'url' => qa_path_html('admin/layout'),
			),
			
			'admin/viewing' => array(
				'label' => qa_lang('admin/viewing_title'),
				'url' => qa_path_html('admin/viewing'),
			),
			
			'admin/posting' => array(
				'label' => qa_lang('admin/posting_title'),
				'url' => qa_path_html('admin/posting'),
			),
			
			'admin/categories' => array(
				'label' => qa_lang('admin/categories_title'),
				'url' => qa_path_html('admin/categories'),
			),
			
			'admin/permissions' => array(
				'label' => qa_lang('admin/permissions_title'),
				'url' => qa_path_html('admin/permissions'),
			),
			
			'admin/pages' => array(
				'label' => qa_lang('admin/pages_title'),
				'url' => qa_path_html('admin/pages'),
			),
			
			'admin/feeds' => array(
				'label' => qa_lang('admin/feeds_title'),
				'url' => qa_path_html('admin/feeds'),
			),
			
			'admin/points' => array(
				'label' => qa_lang('admin/points_title'),
				'url' => qa_path_html('admin/points'),
			),
			
			'admin/spam' => array(
				'label' => qa_lang('admin/spam_title'),
				'url' => qa_path_html('admin/spam'),
			),

			'admin/hidden' => array(
				'label' => qa_lang('admin/hidden_title'),
				'url' => qa_path_html('admin/hidden'),
			),
			
			'admin/stats' => array(
				'label' => qa_lang('admin/stats_title'),
				'url' => qa_path_html('admin/stats'),
			),
		);
		
		if (!qa_using_categories($qa_db))
			unset($navigation['admin/categories']);
		
		return $navigation;
	}
	
	
	function qa_admin_pending()
/*
	Queue any option requests needed for common admin functions
*/
	{
		qa_options_set_pending(array('db_version', 'tags_or_categories'));
	}

	
	function qa_admin_page_error($db)
/*
	Return an error to be displayed on all admin pages, or null if none
*/
	{
		@include_once QA_INCLUDE_DIR.'qa-db-install.php';
		
		if (defined('QA_DB_VERSION_CURRENT') && (qa_get_option($db, 'db_version')<QA_DB_VERSION_CURRENT))
			return strtr(
				qa_lang_html('admin/upgrade_db'),
				
				array(
					'^1' => '<A HREF="'.qa_path_html('install').'">',
					'^2' => '</A>',
				)
			);
		else
			return null;
	}


	function qa_admin_url_test_html()
/*
	Return the HTML to display for a URL test which has passed
*/
	{
		return '; font-size:9px; color:#060; font-weight:bold; font-family:arial,sans-serif; border-color:#060;">OK<';
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/