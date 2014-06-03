<?php

/*
	Question2Answer 1.4-beta-1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page.php
	Version: 1.4-beta-1
	Date: 2011-05-25 07:38:57 GMT
	Description: Routing and utility functions for page requests


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/


//	Table routing requests to the appropriate PHP file

	$qa_routing=array(
		'answers/' => QA_INCLUDE_DIR.'qa-page-answers.php',
		'comments/' => QA_INCLUDE_DIR.'qa-page-comments.php',
		'unanswered/' => QA_INCLUDE_DIR.'qa-page-unanswered.php',
		'activity/' => QA_INCLUDE_DIR.'qa-page-activity.php',
		'questions/' => QA_INCLUDE_DIR.'qa-page-questions.php',
		'hot' => QA_INCLUDE_DIR.'qa-page-hot.php',
		'ask' => QA_INCLUDE_DIR.'qa-page-ask.php',
		'search' => QA_INCLUDE_DIR.'qa-page-search.php',
		'categories/' => QA_INCLUDE_DIR.'qa-page-categories.php',
		'tags' => QA_INCLUDE_DIR.'qa-page-tags.php',
		'tag/' => QA_INCLUDE_DIR.'qa-page-tag.php',
		'users' => QA_INCLUDE_DIR.'qa-page-users.php',
		'users/special' => QA_INCLUDE_DIR.'qa-page-users-special.php',
		'users/blocked' => QA_INCLUDE_DIR.'qa-page-users-blocked.php',
		'user/' => QA_INCLUDE_DIR.'qa-page-user.php',
		'message/' => QA_INCLUDE_DIR.'qa-page-message.php',
		'ip/' => QA_INCLUDE_DIR.'qa-page-ip.php',
		'register' => QA_INCLUDE_DIR.'qa-page-register.php',
		'confirm' => QA_INCLUDE_DIR.'qa-page-confirm.php',
		'account' => QA_INCLUDE_DIR.'qa-page-account.php',
		'admin' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/emails' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/layout' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/users' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/lists' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/viewing' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/posting' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/permissions' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/points' => QA_INCLUDE_DIR.'qa-page-admin-points.php',
		'admin/feeds' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/spam' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/hidden' => QA_INCLUDE_DIR.'qa-page-admin-hidden.php',
		'admin/stats' => QA_INCLUDE_DIR.'qa-page-admin-stats.php',
		'admin/plugins' => QA_INCLUDE_DIR.'qa-page-admin-plugins.php',
		'admin/recalc' => QA_INCLUDE_DIR.'qa-page-admin-recalc.php',
		'admin/categories' => QA_INCLUDE_DIR.'qa-page-admin-categories.php',
		'admin/userfields' => QA_INCLUDE_DIR.'qa-page-admin-userfields.php',
		'admin/usertitles' => QA_INCLUDE_DIR.'qa-page-admin-usertitles.php',
		'admin/pages' => QA_INCLUDE_DIR.'qa-page-admin-pages.php',
		'admin/layoutwidgets' => QA_INCLUDE_DIR.'qa-page-admin-widgets.php',
		'login' => QA_INCLUDE_DIR.'qa-page-login.php',
		'forgot' => QA_INCLUDE_DIR.'qa-page-forgot.php',
		'reset' => QA_INCLUDE_DIR.'qa-page-reset.php',
		'logout' => QA_INCLUDE_DIR.'qa-page-logout.php',
		'feedback' => QA_INCLUDE_DIR.'qa-page-feedback.php',
	);
	

	function qa_self_html()
/*
	Return an HTML-ready relative URL for the current page, preserving GET parameters - this is useful for ACTION in FORMs
*/
	{
		global $qa_used_url_format, $qa_request;
		
		return qa_path_html($qa_request, $_GET, null, $qa_used_url_format);
	}


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
				$usage['ram']/1024, $usage['ram'] ? ($usage['ram']*100/$totalusage['ram']) : 0
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

	function qa_page_db_fail_handler($type, $errno=null, $error=null, $query=null)
/*
	Standard database failure handler function which bring up the install/repair/upgrade page
*/
	{
		$pass_failure_type=$type;
		$pass_failure_errno=$errno;
		$pass_failure_error=$error;
		$pass_failure_query=$query;
		
		require QA_INCLUDE_DIR.'qa-install.php';
		
		exit;
	}

	qa_base_db_connect('qa_page_db_fail_handler');


//	Get common parameters, queue some database information for retrieval and get the ID/cookie of the current user (if any)

	$qa_start=min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	$qa_state=qa_get('state');
	unset($_GET['state']); // to prevent being passed through on forms

	$qa_nav_pages_pending=true;
	$qa_widgets_pending=true;
	$qa_logged_in_pending=true;

	$qa_login_userid=qa_get_logged_in_userid();
	$qa_cookieid=qa_cookie_get();
	

// 	If not currently logged in as anyone, see if any of the registered login modules can help
	
	if (!isset($qa_login_userid)) {
		$modulenames=qa_list_modules('login');
		
		foreach ($modulenames as $tryname) {
			$module=qa_load_module('login', $tryname);
			
			if (method_exists($module, 'check_login')) {
				$module->check_login();
				$qa_login_userid=qa_get_logged_in_userid();
	
				if (isset($qa_login_userid)) // stop and reload page if it worked
					qa_redirect($qa_request, $_GET);
			}
		}
	}

	
	function qa_content_prepare($voting=false, $categoryids=null)
/*
	Start preparing theme content in global $qa_content variable, with or without $voting support,
	in the context of $categoryid (if not null)
*/
	{
		global $qa_root_url_relative, $qa_request, $qa_template, $qa_login_userid, $qa_vote_error_html, $qa_nav_pages_cached, $qa_widgets_cached, $qa_routing;
		
		if (QA_DEBUG_PERFORMANCE)
			qa_usage_mark('control');
		
		if (isset($categoryids) && !is_array($categoryids)) // accept old-style parameter
			$categoryids=array($categoryids);
			
		$lastcategoryid=count($categoryids) ? end($categoryids) : null;
		
		$qa_content=array(
			'content_type' => 'text/html; charset=utf-8',
			
			'site_title' => qa_html(qa_opt('site_title')),
			
			'head_lines' => array(),
			
			'navigation' => array(
				'user' => array(),

				'main' => array(),
				
				'footer' => array(
					'feedback' => array(
						'url' => qa_path_html('feedback'),
						'label' => qa_lang_html('main/nav_feedback'),
					),
				),
	
			),
			
			'sidebar' => qa_opt('show_custom_sidebar') ? qa_opt('custom_sidebar') : null,
			
			'sidepanel' => qa_opt('show_custom_sidepanel') ? qa_opt('custom_sidepanel') : null,
			
			'widgets' => array(),
		);

		if (qa_opt('show_custom_in_head'))
			$qa_content['head_lines'][]=qa_opt('custom_in_head');
		
		if (qa_opt('show_custom_header'))
			$qa_content['body_header']=qa_opt('custom_header');
	
		if (qa_opt('show_custom_footer'))
			$qa_content['body_footer']=qa_opt('custom_footer');

		if (isset($categoryids))
			$qa_content['categoryids']=$categoryids;
		
		foreach ($qa_nav_pages_cached as $page)
			if ($page['nav']=='B')
				qa_navigation_add_page($qa_content['navigation']['main'], $page);
		
		$hascustomhome=qa_opt('show_custom_home');
		
		if (qa_opt('nav_home') && $hascustomhome)
			$qa_content['navigation']['main']['$']=array(
				'url' => qa_path_html(''),
				'label' => qa_lang_html('main/nav_home'),
			);

		if (qa_opt('nav_activity'))
			$qa_content['navigation']['main']['activity']=array(
				'url' => qa_path_html('activity'),
				'label' => qa_lang_html('main/nav_activity'),
			);
			
		if (qa_opt($hascustomhome ? 'nav_qa_not_home' : 'nav_qa_is_home'))
			$qa_content['navigation']['main'][$hascustomhome ? 'qa' : '$']=array(
				'url' => qa_path_html($hascustomhome ? 'qa' : ''),
				'label' => qa_lang_html('main/nav_qa'),
			);
			
		if (qa_opt('nav_questions'))
			$qa_content['navigation']['main']['questions']=array(
				'url' => qa_path_html('questions'),
				'label' => qa_lang_html('main/nav_qs'),
			);

		if (qa_opt('nav_hot'))
			$qa_content['navigation']['main']['hot']=array(
				'url' => qa_path_html('hot'),
				'label' => qa_lang_html('main/nav_hot'),
			);

		if (qa_opt('nav_unanswered'))
			$qa_content['navigation']['main']['unanswered']=array(
				'url' => qa_path_html('unanswered'),
				'label' => qa_lang_html('main/nav_unanswered'),
			);
			
		if (qa_using_tags() && qa_opt('nav_tags'))
			$qa_content['navigation']['main']['tag']=array(
				'url' => qa_path_html('tags'),
				'label' => qa_lang_html('main/nav_tags'),
			);
			
		if (qa_using_categories() && qa_opt('nav_categories'))
			$qa_content['navigation']['main']['categories']=array(
				'url' => qa_path_html('categories'),
				'label' => qa_lang_html('main/nav_categories'),
			);

		if (qa_opt('nav_users'))
			$qa_content['navigation']['main']['user']=array(
				'url' => qa_path_html('users'),
				'label' => qa_lang_html('main/nav_users'),
			);
			
		if (qa_user_permit_error('permit_post_q')!='level')
			$qa_content['navigation']['main']['ask']=array(
				'url' => qa_path_html('ask', (qa_using_categories() && strlen($lastcategoryid)) ? array('cat' => $lastcategoryid) : null),
				'label' => qa_lang_html('main/nav_ask'),
			);
		
		
		if (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN)
			$qa_content['navigation']['main']['admin']=array(
				'url' => qa_path_html((isset($_COOKIE['qa_admin_last']) && isset($qa_routing[$_COOKIE['qa_admin_last']]))
					? $_COOKIE['qa_admin_last'] : 'admin'), // use previously requested admin page if valid
				'label' => qa_lang_html('main/nav_admin'),
			);
		
		$qa_content['search']=array(
			'form_tags' => 'METHOD="GET" ACTION="'.qa_path_html('search').'"',
			'form_extra' => qa_path_form_html('search'),
			'title' => qa_lang_html('main/search_title'),
			'field_tags' => 'NAME="q"',
			'button_label' => qa_lang_html('main/search_button'),
		);
		
		if (!qa_opt('feedback_enabled'))
			unset($qa_content['navigation']['footer']['feedback']);
			
		foreach ($qa_nav_pages_cached as $page)
			if ( ($page['nav']=='M') || ($page['nav']=='O') || ($page['nav']=='F') )
				qa_navigation_add_page($qa_content['navigation'][($page['nav']=='F') ? 'footer' : 'main'], $page);
				
		$regioncodes=array(
			'F' => 'full',
			'M' => 'main',
			'S' => 'side',
		);
		
		$placecodes=array(
			'T' => 'top',
			'H' => 'high',
			'L' => 'low',
			'B' => 'bottom',
		);

		foreach ($qa_widgets_cached as $widget)
			if (is_numeric(strpos(','.$widget['tags'].',', ','.$qa_template.',')) || is_numeric(strpos(','.$widget['tags'].',', ',all,'))) { // see if it has been selected for display on this template
				$region=@$regioncodes[substr($widget['place'], 0, 1)];
				$place=@$placecodes[substr($widget['place'], 1, 2)];
				
				if (isset($region) && isset($place)) { // check region/place codes recognized
					$module=qa_load_module('widget', $widget['title']);
					
					if (
						isset($module) && method_exists($module, 'allow_template') && $module->allow_template($qa_template) &&
						method_exists($module, 'allow_region') && $module->allow_region($region) && method_exists($module, 'output_widget')
					)
						$qa_content['widgets'][$region][$place][]=$module; // if module loaded and happy to be displayed here, tell theme about it
				}
			}
			
		$logoshow=qa_opt('logo_show');
		$logourl=qa_opt('logo_url');
		$logowidth=qa_opt('logo_width');
		$logoheight=qa_opt('logo_height');
		
		if ($logoshow)
			$qa_content['logo']='<A HREF="'.qa_path_html('').'" CLASS="qa-logo-link" TITLE="'.qa_html(qa_opt('site_title')).'">'.
				'<IMG SRC="'.qa_html(is_numeric(strpos($logourl, '://')) ? $logourl : $qa_root_url_relative.$logourl).'"'.
				($logowidth ? (' WIDTH="'.$logowidth.'"') : '').($logoheight ? (' HEIGHT="'.$logoheight.'"') : '').
				' BORDER="0"/></A>';
		else
			$qa_content['logo']='<A HREF="'.qa_path_html('').'" CLASS="qa-logo-link">'.qa_html(qa_opt('site_title')).'</A>';

		$topath=qa_get('to'); // lets user switch between login and register without losing destination page

		$userlinks=qa_get_login_links($qa_root_url_relative, isset($topath) ? $topath : qa_path($qa_request, $_GET, ''));
		
		$qa_content['navigation']['user']=array();
			
		if (isset($qa_login_userid)) {
			$qa_content['loggedin']=qa_lang_html_sub_split('main/logged_in_x', QA_EXTERNAL_USERS
				? qa_get_logged_in_user_html(qa_get_logged_in_user_cache(), $qa_root_url_relative, false)
				: qa_get_one_user_html(qa_get_logged_in_handle(), false)
			);
			
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
			
			if (!QA_EXTERNAL_USERS) {
				$source=qa_get_logged_in_source();
				
				if (strlen($source)) {
					$modulenames=qa_list_modules('login');
					
					foreach ($modulenames as $tryname) {
						$module=qa_load_module('login', $tryname);
						
						if (method_exists($module, 'match_source') && $module->match_source($source) && method_exists($module, 'logout_html')) {
							ob_start();
							$module->logout_html(qa_path('logout', array(), qa_opt('site_url')));
							$qa_content['navigation']['user']['logout']=array('label' => ob_get_clean());
						}
					}
				}
			}
			
		} else {
			$modulenames=qa_list_modules('login');
			
			foreach ($modulenames as $tryname) {
				$module=qa_load_module('login', $tryname);
				
				if (method_exists($module, 'login_html')) {
					ob_start();
					$module->login_html(isset($topath) ? (qa_opt('site_url').$topath) : qa_path($qa_request, $_GET, qa_opt('site_url')), 'menu');
					$qa_content['navigation']['user'][$tryname]=array('label' => ob_get_clean());
				}
			}
			
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
		
		$qa_content['script_rel']=array('qa-content/jquery-1.6.1.min.js');
		
		if ($voting) {
			$qa_content['error']=@$qa_vote_error_html;
			$qa_content['script_rel'][]='qa-content/qa-votes.js?'.QA_VERSION;
		}
			
		$qa_content['script_var']=array(
			'qa_root' => $qa_root_url_relative,
			'qa_request' => $qa_request,
		);
		
		return $qa_content;
	}


//	End of setup phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('setup');

	
//	Process any incoming votes

	if (qa_is_http_post())
		foreach ($_POST as $field => $value)
			if (strpos($field, 'vote_')===0) {
				@list($dummy, $postid, $vote, $anchor)=explode('_', $field);
				
				if (isset($postid) && isset($vote)) {
					require_once QA_INCLUDE_DIR.'qa-app-votes.php';
					require_once QA_INCLUDE_DIR.'qa-db-selects.php';
					
					$post=qa_db_select_with_pending(qa_db_full_post_selectspec($qa_login_userid, $postid));
					$qa_vote_error_html=qa_vote_error_html($post, $qa_login_userid, $qa_request);

					if (!$qa_vote_error_html) {
						qa_vote_set($post, $qa_login_userid, qa_get_logged_in_handle(), $qa_cookieid, $vote);
						qa_redirect($qa_request, $_GET, null, null, $anchor);
					}
					break;
				}
			}


//	Otherwise include the appropriate PHP file for the page in the request

	if (!isset($qa_content)) {
		if (isset($qa_routing[$qa_request_lc])) {
			if ($qa_request_lc_parts[0]=='admin') {
				$_COOKIE['qa_admin_last']=$qa_request_lc; // for navigation tab now...
				setcookie('qa_admin_last', $_COOKIE['qa_admin_last'], 0, '/', QA_COOKIE_DOMAIN); // ...and in future
			}
			
			$qa_template=$qa_request_lc_parts[0];
			$qa_content=require $qa_routing[$qa_request_lc];
	
		} elseif (isset($qa_routing[$qa_request_lc_parts[0].'/'])) {
			$pass_subrequests=array_slice($qa_request_parts, 1); // effectively a parameter that is passed to file
			$qa_template=$qa_request_lc_parts[0];
			$qa_content=require $qa_routing[$qa_request_lc_parts[0].'/'];
			
		} elseif (is_numeric($qa_request_parts[0])) {
			$pass_questionid=$qa_request_parts[0]; // effectively a parameter that is passed to file
			$qa_template='question';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-question.php';
	
		} else {
			$qa_template=strlen($qa_request_lc_parts[0]) ? $qa_request_lc_parts[0] : 'qa';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-default.php'; // handles many other pages, including custom pages and page modules
		}
	}
	
	
	function qa_is_slug_reserved($requestpart)
/*
	Returns whether a URL path beginning with $requestpart is reserved by QA
*/
	{
		global $qa_routing, $QA_CONST_PATH_MAP;
		
		$requestpart=trim(strtolower($requestpart));
		
		if (isset($qa_routing[$requestpart]) || isset($qa_routing[$requestpart.'/']) || is_numeric($requestpart))
			return true;
			
		if (isset($QA_CONST_PATH_MAP))
			foreach ($QA_CONST_PATH_MAP as $requestmap)
				if (trim(strtolower($requestmap)) == $requestpart)
					return true;
			
		switch ($requestpart) {
			case '':
			case 'qa':
			case 'feed':
			case 'install':
			case 'url':
			case 'image':
			case 'ajax':
				return true;
		}
		
		$modulenames=qa_list_modules('page');
		
		foreach ($modulenames as $tryname) {
			$trypage=qa_load_module('page', $tryname);

			if (method_exists($trypage, 'match_request') && $trypage->match_request($requestpart))
				return true;
		}
			
		return false;
	}
	
	
//	End of view phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('view');
	

//	Output the content if there is any
	
	if (is_array($qa_content)) {
	
	//	Set appropriate selected flags for navigation (not done in qa_content_prepare() since it also applies to sub-navigation)
		
		foreach ($qa_content['navigation'] as $navtype => $navigation)
			if (is_array($navigation) && ($navtype!='cat'))
				foreach ($navigation as $navprefix => $navlink)
					if (substr($qa_request_lc.'$', 0, strlen($navprefix)) == $navprefix)
						$qa_content['navigation'][$navtype][$navprefix]['selected']=true;
	
	
	//	Handle maintenance mode
	
		if (qa_opt('site_maintenance') && ($qa_request_lc!='login')) {
			if (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) {
				if (!isset($qa_content['error']))
					$qa_content['error']=strtr(qa_lang_html('admin/maintenance_admin_only'), array(
						'^1' => '<A HREF="'.qa_path_html('admin').'">',
						'^2' => '</A>',
					));
	
			} else {
				$qa_content=qa_content_prepare();
				$qa_content['error']=qa_lang_html('misc/site_in_maintenance');
			}
		}
	
	
	//	Combine various Javascript elements in $qa_content into single array for theme layer
	
		$script=array('<SCRIPT TYPE="text/javascript"><!--');
		
		if (isset($qa_content['script_var']))
			foreach ($qa_content['script_var'] as $var => $value)
				$script[]='var '.$var.'='.qa_js($value).';';
				
		if (isset($qa_content['script_lines']))
			foreach ($qa_content['script_lines'] as $scriptlines) {
				$script[]='';
				$script=array_merge($script, $scriptlines);
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
			array_push($script,
				'',
				'var qa_oldonload=window.onload;',
				'window.onload=function() {',
				"\tif (typeof qa_oldonload=='function')",
				"\t\tqa_oldonload();"
			);
			
			foreach ($qa_content['script_onloads'] as $scriptonload) {
				$script[]="\t";
				
				foreach ((array)$scriptonload as $scriptline)
					$script[]="\t".$scriptline;
			}
	
			$script[]='}';
		}
		
		$script[]='--></SCRIPT>';
		
		if (isset($qa_content['script_rel'])) {
			$uniquerel=array_unique($qa_content['script_rel']); // remove any duplicates
			foreach ($uniquerel as $script_rel)
				$script[]='<SCRIPT SRC="'.qa_html($qa_root_url_relative.$script_rel).'" TYPE="text/javascript"></SCRIPT>';
		}
		
		if (isset($qa_content['script_src']))
			foreach ($qa_content['script_src'] as $script_src)
				$script[]='<SCRIPT SRC="'.qa_html($script_src).'" TYPE="text/javascript"></SCRIPT>';
	
		$qa_content['script']=$script;
		
	
	//	Load the appropriate theme class and output the page
	
		$themeclass=qa_load_theme_class(qa_opt('site_theme'), $qa_template, $qa_content, $qa_request);
	
		header('Content-type: '.$qa_content['content_type']);
		
		$themeclass->doctype();
		$themeclass->html();
		$themeclass->finish();
	
				
	//	Increment question view counter (do at very end so page can be output first)
	
		if (isset($qa_content['inc_views_postid'])) {
			require_once QA_INCLUDE_DIR.'qa-db-hotness.php';
			qa_db_hotness_update($qa_content['inc_views_postid'], null, true);
		}


	//	End of output phase
	
		if (QA_DEBUG_PERFORMANCE) {
			qa_usage_mark('theme');
			qa_usage_output();
		}
	}

	
//	Disconnect from the database

	qa_base_db_disconnect();


/*
	Omit PHP closing tag to help avoid accidental output
*/