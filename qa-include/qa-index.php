<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-index.php
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

//	Be ultra-strict for visible pages and load base include file

	define('QA_BASE_DIR', dirname(empty($_SERVER['SCRIPT_FILENAME']) ? __FILE__ : $_SERVER['SCRIPT_FILENAME']).'/');
	
	error_reporting(E_ALL);
	
	require 'qa-base.php';

//	Determine the request and root of the installation
	
	if (isset($_GET['qa-rewrite'])) { // URLs rewritten by .htaccess
		$qa_request_raw=qa_gpc_to_string($_GET['qa-rewrite']);
		$qa_rewritten=true;
		
	} else { // URLs not rewritten
		$qa_request_raw=urldecode($_SERVER['PHP_SELF']);
		$qa_rewritten=false;
	}
	
	$qa_request_parts=explode('/', $qa_request_raw);
	$qa_url_depth=0;
	$qa_root_path=dirname($_SERVER['PHP_SELF']);
	
	for ($part=count($qa_request_parts)-1; $part>=0; $part--) {
		$qa_request_part=$qa_request_parts[$part];
		$qa_url_depth++;
		
		if (!$qa_rewritten && (strtolower($qa_request_part)=='index.php')) {
			$qa_root_path=implode('/', array_slice($qa_request_parts, 0, $part));
			$qa_request_parts=array_slice($qa_request_parts, $part+1);
			break;
		}
		
		if (empty($qa_request_part))
			unset($qa_request_parts[$part]);
	}
	
	$qa_request=implode('/', $qa_request_parts);
		
	$qa_root_url_relative=($qa_url_depth>1) ? str_repeat('../', $qa_url_depth-1) : './';	
	$qa_root_url_inferred='http://'.$_SERVER['HTTP_HOST'].$qa_root_path.'/';

//	Memory/CPU usage tracking
	
	if (QA_DEBUG_PERFORMANCE) {
		function qa_usage_get()
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
		
		$qa_database_usage=array('queries' => 0, 'clock' => 0);
		$qa_database_queries='';
		$qa_usage_last=$qa_usage_start=qa_usage_get();
		
		function qa_usage_delta($oldusage, $newusage)
		{
			$delta=array();
			
			foreach ($newusage as $key => $value)
				$delta[$key]=max(0, $value-@$oldusage[$key]);
				
			return $delta;
		}
		
		function qa_usage_mark($stage)
		{
			global $qa_usage_last, $qa_usage_stages;
			
			$usage=qa_usage_get();
			$qa_usage_stages[$stage]=qa_usage_delta($qa_usage_last, $usage);
			$qa_usage_last=$usage;
		}
	
		function qa_usage_line($stage, $usage, $totalusage)
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
	}
	
//	Other required includes

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	
//	Connect to database

	function qa_db_fail_handler($type, $errno=null, $error=null, $query=null)
	{
		$pass_failure_type=$type;
		$pass_failure_errno=$errno;
		$pass_failure_error=$error;
		$pass_failure_query=$query;
		
		require QA_INCLUDE_DIR.'qa-install.php';
	}

	qa_base_db_connect('qa_db_fail_handler');
	
//	Self-reference

	function qa_self_html()
	{
		global $qa_rewritten, $qa_request;
		
		$params=$_GET;
		unset($params['qa-rewrite']);
		
		return qa_path_html($qa_request, $params, null, $qa_rewritten);
	}

//	Get the start position and set some options as pending

	$qa_start=min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);

	qa_options_set_pending(array('site_title', 'logo_show', 'logo_url', 'logo_width', 'logo_height', 'feedback_email',
		'site_language', 'site_theme', 'neat_urls', 'custom_sidebar', 'custom_header', 'custom_footer', 'pages_prev_next'));

//	Function called by qa-page-* files to start preparing theme content
	
	function qa_content_prepare($voting=false)
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
			
			'search' => array(
				'form_tags' => ' METHOD="GET" ACTION="'.qa_path_html('search').'" ',
				'title' => qa_lang_html('main/search_title'),		
				'field_tags' => ' NAME="q" ',
				'button_label' => qa_lang_html('main/search_button')
			),
			
			'sidebar' => qa_get_option($qa_db, 'custom_sidebar'),
		);
		
		if (!strlen(qa_get_option($qa_db, 'feedback_email')))
			unset($qa_content['navigation']['footer']['feedback']);

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

		$userlinks=qa_get_login_links($qa_root_url_relative, qa_path($qa_request, null, ''));
		
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

//	See if we're logged in or if we have a cookie

	function qa_check_login($db)
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

	if ($_SERVER['REQUEST_METHOD']=='POST')
		foreach ($_POST as $field => $value)
			if (strpos($field, 'vote_')===0) {
				@list($dummy, $postid, $vote)=explode('_', $field);
				
				if (isset($postid) && isset($vote)) {
					require_once QA_INCLUDE_DIR.'qa-app-votes.php';
					$qa_vote_error=qa_user_vote_error($qa_db, $qa_login_userid, $postid, $vote, $qa_request);
					break;
				}
			}

//	Do the right thing
	
	$qa_routing=array(
		'' => QA_INCLUDE_DIR.'qa-page-home.php',
		'questions' => QA_INCLUDE_DIR.'qa-page-home.php',
		'answers' => QA_INCLUDE_DIR.'qa-page-home.php', // not currently in navigation
		'ask' => QA_INCLUDE_DIR.'qa-page-ask.php',
		'search' => QA_INCLUDE_DIR.'qa-page-search.php',
		'tags' => QA_INCLUDE_DIR.'qa-page-tags.php',
		'users' => QA_INCLUDE_DIR.'qa-page-users.php',
		'register' => QA_INCLUDE_DIR.'qa-page-register.php',
		'account' => QA_INCLUDE_DIR.'qa-page-account.php',
		'admin' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/layout' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/viewing' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/posting' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/stats' => QA_INCLUDE_DIR.'qa-page-admin-stats.php',
		'admin/points' => QA_INCLUDE_DIR.'qa-page-admin-points.php',
		'admin/emails' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/limits' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/users' => QA_INCLUDE_DIR.'qa-page-admin-users.php',
		'admin/recalc' => QA_INCLUDE_DIR.'qa-page-admin-recalc.php',
		'login' => QA_INCLUDE_DIR.'qa-page-login.php',
		'forgot' => QA_INCLUDE_DIR.'qa-page-forgot.php',
		'reset' => QA_INCLUDE_DIR.'qa-page-reset.php',
		'logout' => QA_INCLUDE_DIR.'qa-page-logout.php',
		'feedback' => QA_INCLUDE_DIR.'qa-page-feedback.php',

		'install' => QA_INCLUDE_DIR.'qa-install.php',
		'rewrite-test' => QA_INCLUDE_DIR.'qa-rewrite-test.php',
		'rewrite-pass' => QA_INCLUDE_DIR.'qa-rewrite-test.php',
	);
	
	$qa_operation_lc=strtolower($qa_request);

	if (isset($qa_routing[$qa_operation_lc])) {
		$qa_template=$qa_operation_lc;
		require $qa_routing[$qa_operation_lc];

	} else {
		$qa_operation_parts=explode('/', $qa_request);
		
		if (is_numeric($qa_operation_parts[0])) {
			$pass_questionid=$qa_operation_parts[0];
			$qa_template='question';
			require QA_INCLUDE_DIR.'qa-page-question.php';

		} elseif (strtolower($qa_operation_parts[0])=='tag') {
			$pass_tag=$qa_operation_parts[1];
			$qa_template='tag';
			require QA_INCLUDE_DIR.'qa-page-tag.php';

		} elseif (strtolower($qa_operation_parts[0])=='user') {
			$pass_handle=$qa_operation_parts[1];
			$qa_template='user';
			require QA_INCLUDE_DIR.'qa-page-user.php';

		} else {
			$qa_template='not-found';
			qa_content_prepare();
			$qa_content['title']='';
			$qa_content['error']=qa_lang_html('main/page_not_found');
		}
	}
	
//	End of view phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('view');
	
//	Set appropriate selected flags for navigation
	
	foreach ($qa_content['navigation'] as $navtype => $navigation)
		foreach ($navigation as $navprefix => $navlink)
			if (substr($qa_operation_lc.'$', 0, strlen($navprefix)) == $navprefix)
				$qa_content['navigation'][$navtype][$navprefix]['selected']=true;

//	Load up the appropriate theme, with lots of safety measures

	require_once QA_INCLUDE_DIR.'qa-theme-base.php';
	
	$themephpfile=QA_THEME_DIR.qa_get_option($qa_db, 'site_theme').'/qa-theme.php';
	$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/'.qa_get_option($qa_db, 'site_theme').'/');
	
	if (file_exists($themephpfile)) {
		require_once QA_THEME_DIR.qa_get_option($qa_db, 'site_theme').'/qa-theme.php';

		if (class_exists('qa_html_theme'))
			$theme=new qa_html_theme($qa_template, $qa_content, $themeroothtml);
	}
	
	if (!isset($theme)) {
		if (!file_exists(QA_THEME_DIR.qa_get_option($qa_db, 'site_theme').'/qa-styles.css'))
			$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/Default/');
			
		$theme=new qa_html_theme_base($qa_template, $qa_content, $themeroothtml);
	}
		
//	Output the page using the theme

	if (extension_loaded('zlib') && !headers_sent())
		ob_start('ob_gzhandler');

	header('Content-type: text/html; charset=utf-8');
	
	$theme->doctype();
	
	$theme->output(
		'<HTML>',
		'<HEAD>',
		'<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8"/>',
		'<TITLE>'.(strlen($qa_request) ? (strip_tags($qa_content['title']).' - ') : '').qa_html(qa_get_option($qa_db, 'site_title')).'</TITLE>'
	);
	
	$theme->output('<SCRIPT TYPE="text/javascript"><!--');

	if (isset($qa_content['script_var']))
		foreach ($qa_content['script_var'] as $var => $value)
			$theme->output('var '.$var.'='.qa_js($value).';');
	
	if (isset($qa_content['script_lines']))
		foreach ($qa_content['script_lines'] as $script) {
			$theme->output('');
			$theme->output_array($script);
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
		$theme->output(
			'',
			'var qa_oldonload=window.onload;',
			'window.onload=function() {',
			"\tif (typeof qa_oldonload=='function')",
			"\t\toldonload();"
		);
		
		foreach ($qa_content['script_onloads'] as $script) {
			$theme->output("\t");
			
			foreach ($script as $scriptline)
				$theme->output("\t".$scriptline);
		}

		$theme->output(
			"}"
		);
	}

	$theme->output('--></SCRIPT>');
	
	if (isset($qa_content['script_src']))
		foreach ($qa_content['script_src'] as $script_src)
			$theme->output('<SCRIPT SRC="'.qa_html($qa_root_url_relative.'qa-content/'.$script_src).'" TYPE="text/javascript"></SCRIPT>');

	$theme->head_custom();
	
	$theme->output('</HEAD>', '<BODY');
	$theme->body_tags();
	$theme->output('>');
	$theme->output_raw(qa_get_option($qa_db, 'custom_header'));
	$theme->body_content();
	$theme->output_raw(qa_get_option($qa_db, 'custom_footer'));
	$theme->output('</BODY>', '</HTML>');
	$theme->finish();
			
//	End of output phase

	if (QA_DEBUG_PERFORMANCE) {
		qa_usage_mark('theme');
		qa_usage_output();
	}
	
//	Disconnect from the database

	qa_base_db_disconnect();

?>