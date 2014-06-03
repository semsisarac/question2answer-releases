<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-admin.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Functions used in the admin center pages


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

	
	function qa_admin_check_privileges()
/*
	Return true if user is logged in with admin privileges. If not, return false
	and set up global $qa_content with the appropriate title and error message
*/
	{
		global $qa_content, $qa_login_userid, $qa_login_level, $qa_request;
		
		if (!isset($qa_login_userid)) {
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			qa_content_prepare();

			$qa_content['title']=qa_lang_html('admin/admin_title');
			$qa_content['error']=qa_insert_login_links(qa_lang_html('admin/not_logged_in'), $qa_request);
			
			return false;

		} elseif ($qa_login_level<QA_USER_LEVEL_ADMIN) {
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
			'pt' => 'Portugese - Português',
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

	
	function qa_admin_sub_navigation()
/*
	Return the sub navigation structure common to admin pages
*/
	{
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
			
			'admin/points' => array(
				'label' => qa_lang('admin/points_title'),
				'url' => qa_path_html('admin/points'),
			),
			
			'admin/spam' => array(
				'label' => qa_lang('admin/spam_title'),
				'url' => qa_path_html('admin/spam'),
			),

			'admin/users' => array(
				'label' => qa_lang('admin/users_title'),
				'url' => qa_path_html('admin/users'),
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
		
		if (QA_EXTERNAL_USERS)
			unset($navigation['admin/users']);
		
		return $navigation;
	}

	
	function qa_admin_pending()
/*
	Queue any option requests needed by qa_admin_page_error()
*/
	{
		qa_options_set_pending(array('db_version'));
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

?>