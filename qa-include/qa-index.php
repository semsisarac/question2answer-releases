<?php

/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-index.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: The Grand Central of Q2A - all non-Ajax requests come through here


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

//	Try our best to set base path here just in case it wasn't set in index.php (pre version 1.0.1)

	if (!defined('QA_BASE_DIR'))
		define('QA_BASE_DIR', dirname(empty($_SERVER['SCRIPT_FILENAME']) ? dirname(__FILE__) : $_SERVER['SCRIPT_FILENAME']).'/');

//	Be ultra-strict about error checking for visible pages and load base include file

	error_reporting(E_ALL);
	
	require 'qa-base.php';
	
//	Determine the request and root of the installation, and the requested start position used by many pages
	
	$relativedepth=0;
	$rootpath=dirname($_SERVER['PHP_SELF']);
	
	if (isset($_GET['qa-rewrite'])) { // URLs rewritten by .htaccess
		$qa_used_url_format=QA_URL_FORMAT_NEAT;
		$requestparts=explode('/', qa_gpc_to_string($_GET['qa-rewrite']));
		unset($_GET['qa-rewrite']);
		$relativedepth=count($requestparts);
		
		// Workaround for fact that Apache unescapes characters while rewriting, based on assumption that $_GET['qa-rewrite'] has
		// right path depth, which is true do long as there are only escaped characters in the last part of the path
		if (!empty($_SERVER['REQUEST_URI'])) {
			$origpath=$_SERVER['REQUEST_URI'];
			$_GET=array();
			
			$questionpos=strpos($origpath, '?');
			if (is_numeric($questionpos)) {
				$params=explode('&', substr($origpath, $questionpos+1));
				
				foreach ($params as $param)
					if (preg_match('/^([^\=]*)(\=(.*))?$/', $param, $matches))
						$_GET[urldecode($matches[1])]=qa_string_to_gpc(urldecode(@$matches[3]));

				$origpath=substr($origpath, 0, $questionpos);
			}
			
			$requestparts=array_slice(explode('/', urldecode($origpath)), -count($requestparts));
		}
		
	} elseif (isset($_GET['qa'])) {
		if (strpos($_GET['qa'], '/')===false) {
			$qa_used_url_format=( (empty($_SERVER['REQUEST_URI'])) || (strpos($_SERVER['REQUEST_URI'], '/index.php')!==false) )
				? QA_URL_FORMAT_SAFEST : QA_URL_FORMAT_PARAMS;
			$requestparts=array(qa_gpc_to_string($_GET['qa']));
			
			for ($part=1; $part<10; $part++)
				if (isset($_GET['qa_'.$part])) {
					$requestparts[]=qa_gpc_to_string($_GET['qa_'.$part]);
					unset($_GET['qa_'.$part]);
				}
		
		} else {
			$qa_used_url_format=QA_URL_FORMAT_PARAM;
			$requestparts=explode('/', qa_gpc_to_string($_GET['qa']));
		}
		
		unset($_GET['qa']);
	
	} else {
		$phpselfunescaped=strtr($_SERVER['PHP_SELF'], '+', ' '); // seems necessary, and plus does not work with this scheme
		$indexpath='/index.php/';
		$indexpos=strpos($phpselfunescaped, $indexpath);
		
		if (is_numeric($indexpos)) {
			$qa_used_url_format=QA_URL_FORMAT_INDEX;	
			$requestparts=explode('/', substr($phpselfunescaped, $indexpos+strlen($indexpath)));
			$relativedepth=1+count($requestparts);
			$rootpath=substr($phpselfunescaped, 0, $indexpos);
	
		} else {
			$qa_used_url_format=null; // at home page so can't identify path type
			$requestparts=array();
		}
	}
	
	foreach ($requestparts as $part => $requestpart) // remove any blank parts
		if (empty($requestpart))
			unset($requestparts[$part]);

	$qa_request=implode('/', $requestparts);
	$qa_request_lc=strtolower($qa_request);
	
	$qa_root_url_relative=($relativedepth>1) ? str_repeat('../', $relativedepth-1) : './';
	$qa_root_url_inferred='http://'.@$_SERVER['HTTP_HOST'].$rootpath.'/';
	
	$qa_start=min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);


	function qa_self_html()
/*
	Return an HTML-ready relative URL for the current page, preserving GET parameters - this i useful for ACTION in FORMs
*/
	{
		global $qa_used_url_format, $qa_request;
		
		return qa_path_html($qa_request, $_GET, null, $qa_used_url_format);
	}


//	Enable gzip compression for HTML output (apparently needs to come early)

	if (($qa_request_lc!='install') && ($qa_request_lc!='admin/recalc')) // not for lengthy processes
		if (extension_loaded('zlib') && !headers_sent())
			ob_start('ob_gzhandler');

		
//	Memory/CPU usage tracking
	
	if (QA_DEBUG_PERFORMANCE) {

		function qa_usage_get()
	/*
		Return an array representing the resource usage as of now
	*/
		{
			global $qa_database_usage;
			
			$usage=array(
				'files' => count(get_included_files()),
				'queries' => $qa_database_usage['queries'],
				'ram' => function_exists('memory_get_usage') ? memory_get_usage() : 0,
				'clock' => array_sum(explode(' ', microtime())),
				'mysql' => $qa_database_usage['clock'],
			);
			
			if (function_exists('getrusage')) {
				$rusage=getrusage();
				$usage['cpu']=$rusage["ru_utime.tv_sec"]+$rusage["ru_stime.tv_sec"]
					+($rusage["ru_utime.tv_usec"]+$rusage["ru_stime.tv_usec"])/1000000;
			} else
				$usage['cpu']=0;
				
			$usage['other']=$usage['clock']-$usage['cpu']-$usage['mysql'];
				
			return $usage;
		}

		
		function qa_usage_delta($oldusage, $newusage)
	/*
		Return the difference between two resource usage arrays, as an array
	*/
		{
			$delta=array();
			
			foreach ($newusage as $key => $value)
				$delta[$key]=max(0, $value-@$oldusage[$key]);
				
			return $delta;
		}

		
		function qa_usage_mark($stage)
	/*
		Mark the beginning of a new stage of script execution and store usages accordingly
	*/
		{
			global $qa_usage_last, $qa_usage_stages;
			
			$usage=qa_usage_get();
			$qa_usage_stages[$stage]=qa_usage_delta($qa_usage_last, $usage);
			$qa_usage_last=$usage;
		}

	
		function qa_usage_line($stage, $usage, $totalusage)
	/*
		Return HTML to represent the resource $usage, showing appropriate proportions of $totalusage
	*/
		{
			return sprintf(
				"%s &ndash; <B>%.1fms</B> (%d%%) &ndash; PHP %.1fms (%d%%), MySQL %.1fms (%d%%), Other %.1fms (%d%%) &ndash; %d PHP %s, %d DB %s, %dk RAM (%d%%)",
				$stage, $usage['clock']*1000, $usage['clock']*100/$totalusage['clock'],
				$usage['cpu']*1000, $usage['cpu']*100/$totalusage['clock'],
				$usage['mysql']*1000, $usage['mysql']*100/$totalusage['clock'],
				$usage['other']*1000, $usage['other']*100/$totalusage['clock'],
				$usage['files'], ($usage['files']==1) ? 'file' : 'files',
				$usage['queries'], ($usage['queries']==1) ? 'query' : 'queries',
				$usage['ram']/1024, $usage['ram']*100/$totalusage['ram']
			);
		}

		
		function qa_usage_output()
	/*
		Output an (ugly) block of HTML detailing all resource usage and database queries
	*/
		{
			global $qa_usage_start, $qa_usage_stages, $qa_database_queries;
			
			echo '<P><BR><TABLE BGCOLOR="#CCCCCC" CELLPADDING="8" CELLSPACING="0" WIDTH="100%">';
		
			echo '<TR><TD COLSPAN="2">';
			
			$totaldelta=qa_usage_delta($qa_usage_start, qa_usage_get());
			
			echo qa_usage_line('Total', $totaldelta, $totaldelta).'<BR>';
			
			foreach ($qa_usage_stages as $stage => $stagedelta)
				
			echo '<BR>'.qa_usage_line(ucfirst($stage), $stagedelta, $totaldelta);
			
			echo '</TD></TR><TR VALIGN="bottom"><TD WIDTH="30%"><TEXTAREA COLS="40" ROWS="20" STYLE="width:100%;">';
			
			foreach (get_included_files() as $file)
				echo qa_html(implode('/', array_slice(explode('/', $file), -3)))."\n";
			
			echo '</TEXTAREA></TD>';
			
			echo '<TD WIDTH="70%"><TEXTAREA COLS="40" ROWS="20" STYLE="width:100%;">'.qa_html($qa_database_queries).'</TEXTAREA></TD>';
			
			echo '</TR></TABLE>';
		}
		
	//	Initialize a bunch of usage-related global variables

		$qa_database_usage=array('queries' => 0, 'clock' => 0);
		$qa_database_queries='';
		$qa_usage_last=$qa_usage_start=qa_usage_get();
	}

	
//	Other includes required for all page views

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';

	
//	Connect to database

	function qa_db_fail_handler($type, $errno=null, $error=null, $query=null)
/*
	Standard database failure handler function which bring up the install/repair/upgrade page
*/
	{
		$pass_failure_type=$type;
		$pass_failure_errno=$errno;
		$pass_failure_error=$error;
		$pass_failure_query=$query;
		
		require QA_INCLUDE_DIR.'qa-install.php';
	}

	qa_base_db_connect('qa_db_fail_handler');


//	Queue many common options for retrieval and define the function which prepares page content

	qa_options_set_pending(array('site_title', 'logo_show', 'logo_url', 'logo_width', 'logo_height', 'feedback_enabled', 'nav_unanswered',
		'site_language', 'site_theme', 'neat_urls', 'custom_sidebar', 'custom_header', 'custom_footer', 'custom_in_head', 'pages_prev_next'));
		
	
	function qa_content_prepare($voting=false)
/*
	Start preparing theme content in global $qa_content variable, with or without $voting support
*/
	{
		global $qa_db, $qa_content, $qa_root_url_relative, $qa_request, $qa_login_userid, $qa_login_level, $qa_login_user, $qa_template, $qa_vote_error;
		
		if (QA_DEBUG_PERFORMANCE)
			qa_usage_mark('control');
		
		$qa_content=array(
			'navigation' => array(
				'user' => array(),

				'main' => array(
					'questions' => array(
						'url' => qa_path_html('questions'),
						'label' => qa_lang_html('main/nav_qs'),
					),
					
					'unanswered' => array(
						'url' => qa_path_html('unanswered'),
						'label' => qa_lang_html('main/nav_unanswered'),
					),
					
					'tag' => array(
						'url' => qa_path_html('tags'),
						'label' => qa_lang_html('main/nav_tags'),
					),
					
					'user' => array(
						'url' => qa_path_html('users'),
						'label' => qa_lang_html('main/nav_users'),
					),
			
					'ask' => array(
						'url' => qa_path_html('ask'),
						'label' => qa_lang_html('main/nav_ask'),
					),
					
					'admin' => array(
						'url' => qa_path_html('admin'),
						'label' => qa_lang_html('main/nav_admin'),
					),
				),
				
				'footer' => array(
					'feedback' => array(
						'url' => qa_path_html('feedback'),
						'label' => qa_lang_html('main/nav_feedback'),
					),
				),
	
			),
			
			'sidebar' => qa_get_option($qa_db, 'custom_sidebar'),
		);
		
		$qa_content['search']=array(
			'form_tags' => ' METHOD="GET" ACTION="'.qa_path_html('search').'" ',
			'form_extra' => qa_path_form_html('search'),
			'title' => qa_lang_html('main/search_title'),
			'field_tags' => ' NAME="q" ',
			'button_label' => qa_lang_html('main/search_button'),
		);
		
		if (!qa_get_option($qa_db, 'feedback_enabled'))
			unset($qa_content['navigation']['footer']['feedback']);
		
		if (!qa_get_option($qa_db, 'nav_unanswered'))
			unset($qa_content['navigation']['main']['unanswered']);

		$logoshow=qa_get_option($qa_db, 'logo_show');
		$logourl=qa_get_option($qa_db, 'logo_url');
		$logowidth=qa_get_option($qa_db, 'logo_width');
		$logoheight=qa_get_option($qa_db, 'logo_height');
		
		if ($logoshow)
			$qa_content['logo']='<A HREF="'.qa_path_html('').'" CLASS="qa-logo-link" TITLE="'.qa_html(qa_get_option($qa_db, 'site_title')).'">'.
				'<IMG SRC="'.(is_numeric(strpos($logourl, '://')) ? $logourl : $qa_root_url_relative.$logourl).'"'.
				($logowidth ? (' WIDTH="'.$logowidth.'"') : '').($logoheight ? (' HEIGHT="'.$logoheight.'"') : '').
				' BORDER="0"/></A>';
		else
			$qa_content['logo']='<A HREF="'.qa_path_html('').'" CLASS="qa-logo-link">'.qa_html(qa_get_option($qa_db, 'site_title')).'</A>';

		$topath=qa_get('to'); // lets user switch between login and register without losing destination page

		$userlinks=qa_get_login_links($qa_root_url_relative, isset($topath) ? $topath : qa_path($qa_request, null, ''));
		
		$qa_content['navigation']['user']=array();
			
		if (isset($qa_login_userid)) {
			$qa_content['loggedin']=qa_lang_sub_split_html('main/logged_in_x', qa_get_logged_in_user_html($qa_db, $qa_login_user, $qa_root_url_relative, false));
			
			if (!QA_EXTERNAL_USERS)
				$qa_content['navigation']['user']['account']=array(
					'url' => qa_path_html('account'),
					'label' => qa_lang_html('main/nav_account'),
				);
				
			if (!empty($userlinks['logout']))
				$qa_content['navigation']['user']['logout']=array(
					'url' => qa_html(@$userlinks['logout']),
					'label' => qa_lang_html('main/nav_logout'),
				);
				
		} else {
			if (!empty($userlinks['login']))
				$qa_content['navigation']['user']['login']=array(
					'url' => qa_html(@$userlinks['login']),
					'label' => qa_lang_html('main/nav_login'),
				);
				
			if (!empty($userlinks['register']))
				$qa_content['navigation']['user']['register']=array(
					'url' => qa_html(@$userlinks['register']),
					'label' => qa_lang_html('main/nav_register'),
				);
		}
		
		if ($qa_login_level<QA_USER_LEVEL_ADMIN)
			unset($qa_content['navigation']['main']['admin']);
			
		if ($voting) {
			$qa_content['error']=@$qa_vote_error;
			$qa_content['script_src']=array('jxs_compressed.js', 'qa-votes.js?'.QA_VERSION);
		} else
			$qa_content['script_src']=array();
			
		$qa_content['script_var']=array(
			'qa_root' => $qa_root_url_relative,
			'qa_request' => $qa_request,
		);
	}


//	User identification phase, including retrieving the current identifying cookie

	function qa_check_login($db)
/*
	Check if we're logged in (via database if necessary) and set global variables accordingly
*/
	{
		global $qa_login_user, $qa_login_userid, $qa_login_level, $qa_login_email;
		
		$qa_login_user=qa_get_logged_in_user($db);
		
		if (is_array($qa_login_user)) {
			$qa_login_userid=$qa_login_user['userid'];
			$qa_login_level=$qa_login_user['level'];
			$qa_login_email=$qa_login_user['email'];
		
		} else {
			$qa_login_userid=null;
			$qa_login_level=null;
			$qa_login_email=null;
		}
	}
	
	qa_check_login($qa_db);
	
	$qa_cookieid=qa_cookie_get();


//	End of setup phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('setup');

	
//	Process any incoming votes

	if (qa_is_http_post())
		foreach ($_POST as $field => $value)
			if (strpos($field, 'vote_')===0) {
				@list($dummy, $postid, $vote)=explode('_', $field);
				
				if (isset($postid) && isset($vote)) {
					require_once QA_INCLUDE_DIR.'qa-app-votes.php';
					$qa_vote_error=qa_user_vote_error($qa_db, $qa_login_userid, $postid, $vote, $qa_request);
					break;
				}
			}


//	Now include the appropriate PHP file for the page in the request
	
	$qa_routing=array(
		'' => QA_INCLUDE_DIR.'qa-page-home.php',
		'questions' => QA_INCLUDE_DIR.'qa-page-home.php',
		'unanswered' => QA_INCLUDE_DIR.'qa-page-home.php',
		'answers' => QA_INCLUDE_DIR.'qa-page-home.php', // not currently in navigation
		'ask' => QA_INCLUDE_DIR.'qa-page-ask.php',
		'comments' => QA_INCLUDE_DIR.'qa-page-home.php', // not currently in navigation
		'search' => QA_INCLUDE_DIR.'qa-page-search.php',
		'tags' => QA_INCLUDE_DIR.'qa-page-tags.php',
		'users' => QA_INCLUDE_DIR.'qa-page-users.php',
		'register' => QA_INCLUDE_DIR.'qa-page-register.php',
		'account' => QA_INCLUDE_DIR.'qa-page-account.php',
		'admin' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/emails' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/layout' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/viewing' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/posting' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/points' => QA_INCLUDE_DIR.'qa-page-admin-points.php',
		'admin/spam' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/users' => QA_INCLUDE_DIR.'qa-page-admin-users.php',
		'admin/hidden' => QA_INCLUDE_DIR.'qa-page-admin-hidden.php',
		'admin/stats' => QA_INCLUDE_DIR.'qa-page-admin-stats.php',
		'admin/recalc' => QA_INCLUDE_DIR.'qa-page-admin-recalc.php',
		'login' => QA_INCLUDE_DIR.'qa-page-login.php',
		'forgot' => QA_INCLUDE_DIR.'qa-page-forgot.php',
		'reset' => QA_INCLUDE_DIR.'qa-page-reset.php',
		'logout' => QA_INCLUDE_DIR.'qa-page-logout.php',
		'feedback' => QA_INCLUDE_DIR.'qa-page-feedback.php',

		'install' => QA_INCLUDE_DIR.'qa-install.php',
		'url/test/'.QA_URL_TEST_STRING => QA_INCLUDE_DIR.'qa-url-test.php',
	);
	
	if (isset($qa_routing[$qa_request_lc])) {
		$qa_template=$qa_request_lc;
		require $qa_routing[$qa_request_lc];

	} else {
		$qa_operation_parts=explode('/', $qa_request);
		
		if (is_numeric($qa_operation_parts[0])) {
			$pass_questionid=$qa_operation_parts[0]; // effectively a parameter that is passed to file
			$qa_template='question';
			require QA_INCLUDE_DIR.'qa-page-question.php';

		} elseif ( (strtolower($qa_operation_parts[0])=='tag') && !empty($qa_operation_parts[1]) ) {
			$pass_tag=$qa_operation_parts[1]; // effectively a parameter that is passed to file
			$qa_template='tag';
			require QA_INCLUDE_DIR.'qa-page-tag.php';

		} elseif ( (strtolower($qa_operation_parts[0])=='user') && !empty($qa_operation_parts[1]) ) {
			$pass_handle=$qa_operation_parts[1]; // effectively a parameter that is passed to file
			$qa_template='user';
			require QA_INCLUDE_DIR.'qa-page-user.php';

		} else {
			$qa_template='not-found';
			qa_content_prepare();
			$qa_content['error']=qa_lang_html('main/page_not_found');
		}
	}
	
	
//	End of view phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('view');
	
	
//	Set appropriate selected flags for navigation (not done in qa_content_prepare() since it also applies to sub-navigation)
	
	foreach ($qa_content['navigation'] as $navtype => $navigation)
		foreach ($navigation as $navprefix => $navlink)
			if (substr($qa_request_lc.'$', 0, strlen($navprefix)) == $navprefix)
				$qa_content['navigation'][$navtype][$navprefix]['selected']=true;


//	Load the appropriate theme class

	$themeclass=qa_load_theme_class(qa_get_option($qa_db, 'site_theme'), $qa_template, $qa_content, $qa_request);


//	Set HTTP header and output start of HTML document
		
	header('Content-type: text/html; charset=utf-8');
	
	$themeclass->doctype();
	$themeclass->output(
		'<HTML>',
		'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->'
	);


//	Output <HEAD> section, mainly a bunch of dynamic JavaScripts
		
	$themeclass->output(
		'<HEAD>',
		'<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8"/>',
		'<TITLE>'.((empty($qa_content['title']) || empty($qa_request)) ? '' : (strip_tags($qa_content['title']).' - ')).qa_html(qa_get_option($qa_db, 'site_title')).'</TITLE>'
	);
	
	$themeclass->output('<SCRIPT TYPE="text/javascript"><!--');

	if (isset($qa_content['script_var']))
		foreach ($qa_content['script_var'] as $var => $value)
			$themeclass->output('var '.$var.'='.qa_js($value).';');
	
	if (isset($qa_content['script_lines']))
		foreach ($qa_content['script_lines'] as $script) {
			$themeclass->output('');
			$themeclass->output_array($script);
		}
		
	if (isset($qa_content['focusid']))
		$qa_content['script_onloads'][]=array(
			"var elem=document.getElementById(".qa_js($qa_content['focusid']).");",
			"if (elem) {",
			"\telem.select();",
			"\telem.focus();",
			"}",
		);
		
	if (isset($qa_content['script_onloads'])) {
		$themeclass->output(
			'',
			'var qa_oldonload=window.onload;',
			'window.onload=function() {',
			"\tif (typeof qa_oldonload=='function')",
			"\t\tqa_oldonload();"
		);
		
		foreach ($qa_content['script_onloads'] as $script) {
			$themeclass->output("\t");
			
			foreach ($script as $scriptline)
				$themeclass->output("\t".$scriptline);
		}

		$themeclass->output('}');
	}

	$themeclass->output('--></SCRIPT>');
	
	if (isset($qa_content['script_src']))
		foreach ($qa_content['script_src'] as $script_src)
			$themeclass->output('<SCRIPT SRC="'.qa_html($qa_root_url_relative.'qa-content/'.$script_src).'" TYPE="text/javascript"></SCRIPT>');

	$themeclass->head_css();
	$themeclass->head_custom();

	$themeclass->output_raw(qa_get_option($qa_db, 'custom_in_head'));

	$themeclass->output('</HEAD>');

	
//	Output <BODY> section

	$themeclass->output('<BODY');
	$themeclass->body_tags();
	$themeclass->output('>');

	$themeclass->output_raw(qa_get_option($qa_db, 'custom_header'));
	$themeclass->body_content();
	$themeclass->output_raw(qa_get_option($qa_db, 'custom_footer'));

	$themeclass->output('</BODY>');


//	Output end of HTML document and let theme do any clearing up
	
	$themeclass->output(
		'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->',
		'</HTML>'
	);

	$themeclass->finish();

			
//	End of output phase

	if (QA_DEBUG_PERFORMANCE) {
		qa_usage_mark('theme');
		qa_usage_output();
	}

	
//	Disconnect from the database

	qa_base_db_disconnect();


/*
	Omit PHP closing tag to help avoid accidental output
*/