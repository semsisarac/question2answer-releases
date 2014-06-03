<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-users.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for top scoring users page


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

	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';


//	Get list of all users
	
	qa_options_set_pending(array('page_size_users', 'columns_users'));
	
	list($users, $usercount)=qa_db_select_with_pending($qa_db,
		qa_db_top_users_selectspec($qa_start),
		qa_db_options_cache_selectspec('cache_userpointscount')
	);
	
	$pagesize=qa_get_option($qa_db, 'page_size_users');
	$users=array_slice($users, 0, $pagesize);
	$usershtml=qa_userids_handles_html($qa_db, $users);


//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html('main/highest_users');

	$qa_content['ranking']=array('items' => array(), 'rows' => ceil($pagesize/qa_get_option($qa_db, 'columns_users')));
	
	if (count($users)) {
		foreach ($users as $userid => $user)
			$qa_content['ranking']['items'][]=array(
				'label' => $usershtml[$user['userid']],
				'score' => qa_html(number_format($user['points'])),
			);
	
	} else
		$qa_content['title']=qa_lang_html('main/no_active_users');
	
	$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $usercount, qa_get_option($qa_db, 'pages_prev_next'));

	$qa_content['navigation']['sub']=qa_users_sub_navigation($qa_db);


/*
	Omit PHP closing tag to help avoid accidental output
*/