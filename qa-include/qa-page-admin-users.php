<?php
	
/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-users.php
	Version: 1.0-beta-3
	Date: 2010-03-31 12:13:41 GMT


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	
//	Standard pre-admin operations and check not using external users

	qa_admin_pending();
	
	if (!qa_admin_check_privileges())
		return;

	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
		
//	Get list of special users

	qa_options_set_pending(array('page_size_users', 'columns_users'));
	
	$users=qa_db_select_with_pending($qa_db, qa_db_users_from_level_selectspec(QA_USER_LEVEL_EDITOR));

	$usershtml=qa_userids_handles_html($qa_db, $users);

//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/users_title');
	
	$qa_content['error']=qa_admin_page_error($qa_db);

	$qa_content['ranking']=array('items' => array(), 'rows' => ceil(qa_get_option($qa_db, 'page_size_users')/qa_get_option($qa_db, 'columns_users')));
	
	foreach ($users as $user) {
		$qa_content['ranking']['items'][]=array(
			'label' => $usershtml[$user['userid']],
			'score' => qa_html(qa_user_level_string($user['level'])),
		);
	}

	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=strtr(
			qa_lang_html('admin/suggest_editors'),
			
			array(
				'^1' => '<A HREF="'.qa_path_html('users').'">',
				'^2' => '</A>',
			)
		);

?>