<?php
	
/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-ip.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Controller for page showing activity for an IP address


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
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

	
	$ip=$pass_ip; // picked up from qa-page.php


//	Queue requests for pending options
	
	qa_options_set_pending(array('show_when_created', 'show_user_points', 'permit_anon_view_ips', 'block_ips_write', 'block_bad_words'));


//	Find recently hidden questions, answers, comments

	list($qs, $qs_hidden, $a_qs, $a_hidden_qs, $c_qs, $c_hidden_qs, $categories)=qa_db_select_with_pending($qa_db,
		qa_db_recent_qs_selectspec($qa_login_userid, 0, null, $ip, false),
		qa_db_recent_qs_selectspec($qa_login_userid, 0, null, $ip, true),
		qa_db_recent_a_qs_selectspec($qa_login_userid, 0, null, $ip, false),
		qa_db_recent_a_qs_selectspec($qa_login_userid, 0, null, $ip, true),
		qa_db_recent_c_qs_selectspec($qa_login_userid, 0, null, $ip, false),
		qa_db_recent_c_qs_selectspec($qa_login_userid, 0, null, $ip, true),
		qa_db_categories_selectspec()
	);
	
	
//	Check we have permission to view this page, and whether we can block or unblock users

	if (qa_user_permit_error($qa_db, 'permit_anon_view_ips')) {
		qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return;
	}
	
	$blockable=qa_get_logged_in_level($qa_db)>=QA_USER_LEVEL_MODERATOR;
		

//	Perform blocking or unblocking operations as appropriate

	if ($blockable) {
		if (qa_clicked('doblock')) {
			$oldblocked=qa_get_option($qa_db, 'block_ips_write');
			qa_set_option($qa_db, 'block_ips_write', (strlen($oldblocked) ? ($oldblocked.' , ') : '').$ip);
		}
		
		if (qa_clicked('dounblock')) {
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
			$blockipclauses=qa_block_ips_explode(qa_get_option($qa_db, 'block_ips_write'));
			
			foreach ($blockipclauses as $key => $blockipclause)
				if (qa_block_ip_match($ip, $blockipclause))
					unset($blockipclauses[$key]);
					
			qa_set_option($qa_db, 'block_ips_write', implode(' , ', $blockipclauses));
		}
	}
	

//	Combine sets of questions and get information for users

	$questions=qa_any_sort_and_dedupe(array_merge($qs, $qs_hidden, $a_qs, $a_hidden_qs, $c_qs, $c_hidden_qs));
	
	$usershtml=qa_userids_handles_html($qa_db, qa_any_get_userids_handles($questions));

	$hostname=gethostbyaddr($ip);


//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html_sub('main/ip_address_x', qa_html($ip));

	$qa_content['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
			
			'style' => 'wide',
			
			'fields' => array(
				'host' => array(
					'type' => 'static',
					'label' => qa_lang_html('misc/host_name'),
					'value' => qa_html($hostname),
				),
			),
		);
		

	if ($blockable) {
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		$blockipclauses=qa_block_ips_explode(qa_get_option($qa_db, 'block_ips_write'));		
		$matchclauses=array();
		
		foreach ($blockipclauses as $blockipclause)
			if (qa_block_ip_match($ip, $blockipclause))	
				$matchclauses[]=$blockipclause;
		
		if (count($matchclauses)) {
			$qa_content['form']['fields']['status']=array(
				'type' => 'static',
				'label' => qa_lang_html('misc/matches_blocked_ips'),
				'value' => qa_html(implode("\n", $matchclauses), true),
			);
			
			$qa_content['form']['buttons']['unblock']=array(
				'tags' => ' NAME="dounblock" ',
				'label' => qa_lang_html('misc/unblock_ip_button'),
			);

		} else
			$qa_content['form']['buttons']['block']=array(
				'tags' => ' NAME="doblock" ',
				'label' => qa_lang_html('misc/block_ip_button'),
			);
	}

	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		$qa_content['q_list']['title']=qa_lang_html_sub('misc/recent_posts_from_x', qa_html($ip));
	
		foreach ($questions as $question) {
			$htmlfields=qa_any_to_q_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				false, qa_using_categories($qa_db) ? $categories : null, false, qa_get_option($qa_db, 'show_when_created'),
				false, qa_get_option($qa_db, 'show_user_points'), qa_get_block_words_preg($qa_db));
			
			unset($htmlfields['answers']); // show less info than usual
			
			if (isset($htmlfields['what_url'])) // link directly to relevant content
				$htmlfields['url']=$htmlfields['what_url'];

			$qa_content['q_list']['qs'][]=$htmlfields;
		}

	} else
		$qa_content['q_list']['title']=qa_lang_html_sub('main/no_posts_from_x', qa_html($ip));
	

/*
	Omit PHP closing tag to help avoid accidental output
*/