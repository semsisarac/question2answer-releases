<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-users-blocked.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for page showing users who have been blocked


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

	
//	Check we're not using single-sign on integration
	
	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
		

//	Queue requests for pending options

	qa_options_set_pending(array('page_size_users', 'columns_users'));
	

//	Get list of special users

	$users=qa_db_select_with_pending($qa_db, qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED));


//	Check we have permission to view this page (moderator or above)

	if ( (!isset($qa_login_userid)) || (qa_get_logged_in_level($qa_db)<QA_USER_LEVEL_MODERATOR) ) {
		qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return;
	}


//	Get userids and handles of retrieved users

	$usershtml=qa_userids_handles_html($qa_db, $users);


//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=count($users) ? qa_lang_html('users/blocked_users') : qa_lang_html('users/no_blocked_users');
	
	$qa_content['ranking']=array('items' => array(), 'rows' => ceil(qa_get_option($qa_db, 'page_size_users')/qa_get_option($qa_db, 'columns_users')));
	
	foreach ($users as $user) {
		$qa_content['ranking']['items'][]=array(
			'label' => $usershtml[$user['userid']],
			'score' => qa_html(qa_user_level_string($user['level'])),
		);
	}

	$qa_content['navigation']['sub']=qa_users_sub_navigation($qa_db);


/*
	Omit PHP closing tag to help avoid accidental output
*/