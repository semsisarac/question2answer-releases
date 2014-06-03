<?php
	
/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-hidden.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Controller for admin page showing hidden questions, answers and comments


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
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

	
//	Standard pre-admin operations

	qa_admin_pending();
	
	if (!qa_admin_check_privileges())
		return;
		
		
//	Find recently hidden questions, answers, comments

	qa_options_set_pending(array('voting_on_qs', 'votes_separated', 'show_user_points'));

	list($hiddenquestions, $hiddenanswers, $hiddencomments)=qa_db_select_with_pending($qa_db,
		qa_db_recent_qs_selectspec($qa_login_userid, 0, true),
		qa_db_recent_a_qs_selectspec($qa_login_userid, 0, true),
		qa_db_recent_c_qs_selectspec($qa_login_userid, 0, true)
	);
	
	
//	Combine sets of questions and get information for users

	$questions=qa_any_sort_and_dedupe(array_merge($hiddenquestions, $hiddenanswers, $hiddencomments));
	
	$usershtml=qa_userids_handles_html($qa_db, qa_any_get_userids_handles($questions));


//	Prepare content for theme
	
	qa_content_prepare(true);

	$qa_content['title']=qa_lang_html('admin/recent_hidden_title');
	
	$qa_content['error']=qa_admin_page_error($qa_db);
	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		foreach ($questions as $question) {
			$htmlfields=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, false, qa_get_option($qa_db, 'show_user_points'));
			
			unset($htmlfields['answers']); // show less info than usual
			unset($htmlfields['q_tags']);

			$qa_content['q_list']['qs'][]=$htmlfields;
		}

	} else
		$qa_content['title']=qa_lang_html('admin/no_hidden_found');
		
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();
	

/*
	Omit PHP closing tag to help avoid accidental output
*/