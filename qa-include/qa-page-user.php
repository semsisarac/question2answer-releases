<?php
	
/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-user.php
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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	
	function qa_page_user_not_found()
	{
		global $qa_content;

		qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/user_not_found');
	}

	if (QA_EXTERNAL_USERS) {
		$publictouserid=qa_get_userids_from_public($qa_db, array($pass_handle));
		$userid=@$publictouserid[$pass_handle];
		
		if (!isset($userid))
			return qa_page_user_not_found();
		
		$usershtml=qa_get_users_html($qa_db, array($userid), false, qa_path(''), true);
		$userhtml=@$usershtml[$userid];

	} else {
		$handle=$pass_handle; // picked up from index.php
		$userhtml=qa_html($handle);
	}
	
//	Find the user profile and questions and answers for this handle
	
	qa_options_set_pending(array('page_size_user_qs', 'page_size_user_as', 'points_per_q_voted', 'points_per_a_voted',
		'voting_on_qs', 'voting_on_as', 'votes_separated', 'comment_on_qs', 'comment_on_as'));
	
	$identifier=QA_EXTERNAL_USERS ? $userid : $handle;

	@list($useraccount, $userprofile, $userpoints, $userrank, $questions, $answerquestions)=qa_db_select_with_pending($qa_db,
		QA_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
		QA_EXTERNAL_USERS ? null : qa_db_user_profile_selectspec($handle, false),
		qa_db_user_points_selectspec($identifier),
		qa_db_user_rank_selectspec($identifier),
		qa_db_user_recent_qs_selectspec($qa_login_userid, $identifier),
		qa_db_user_recent_as_selectspec($identifier)
	);
	
	if (!QA_EXTERNAL_USERS) {
		if ((!is_array($userpoints)) && !is_array($useraccount))
			return qa_page_user_not_found();
	
		$userid=$useraccount['userid'];
		$useradminable=(($qa_login_level>=QA_USER_LEVEL_SUPER) && ($qa_login_userid!=$userid)) || // can't change self
			(($qa_login_level>=QA_USER_LEVEL_ADMIN) && ($qa_login_level>$useraccount['level']));
		$usereditable=$useradminable;
		$userediting=false;
	}

//	Process edit or save button for user

	if ((!QA_EXTERNAL_USERS) && $usereditable) {
		if (qa_clicked('docancel'))
			;
		
		elseif (qa_clicked('doedit'))
			$userediting=true;
			
		elseif (qa_clicked('dosave')) {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			$inemail=qa_post_text('email');
			$inname=qa_post_text('name');
			$inlocation=qa_post_text('location');
			$inwebsite=qa_post_text('website');
			$inabout=qa_post_text('about');
			
			$errors=array_merge(
				qa_handle_email_validate($qa_db, $handle, $inemail, $userid),
				qa_profile_fields_validate($qa_db, $inname, $inlocation, $inwebsite, $inabout)
			);	

			if (!isset($errors['email']))
				qa_db_user_set($qa_db, $userid, 'email', $inemail);

			if (!isset($errors['name']))
				qa_db_user_profile_set($qa_db, $userid, 'name', $inname);
	
			if (!isset($errors['location']))
				qa_db_user_profile_set($qa_db, $userid, 'location', $inlocation);
	
			if (!isset($errors['website']))
				qa_db_user_profile_set($qa_db, $userid, 'website', $inwebsite);
	
			if (!isset($errors['about']))
				qa_db_user_profile_set($qa_db, $userid, 'about', $inabout);

			if ($useradminable) {
				$inlevel=min(($qa_login_level>=QA_USER_LEVEL_SUPER) ? QA_USER_LEVEL_SUPER : QA_USER_LEVEL_EDITOR, (int)qa_post_text('level'));
					// constrain based on logged in user permissions to prevent simple browser-based attack
					
				qa_db_user_set($qa_db, $userid, 'level', $inlevel);
			}
			
			list($useraccount, $userprofile)=qa_db_select_with_pending($qa_db,
				qa_db_user_account_selectspec($userid, true),
				qa_db_user_profile_selectspec($userid, true)
			); // reload user

			if (count($errors))
				$userediting=true;
		}
	}

//	Get information on user references in and answers
	
	$pagesize_qs=qa_get_option($qa_db, 'page_size_user_qs');
	$pagesize_as=qa_get_option($qa_db, 'page_size_user_as');

	$questions=array_slice($questions, 0, $pagesize_qs);
	$answerquestions=array_slice($answerquestions, 0, $pagesize_as);
	$usershtml=qa_userids_handles_html($qa_db, $answerquestions);
	$usershtml[$userid]=$userhtml;
	
//	Prepare content for theme
	
	qa_content_prepare(true);
	
	$qa_content['title']=qa_lang_sub_html('profile/user_x', $userhtml);
	
	if (!QA_EXTERNAL_USERS) {
		$qa_content['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
			
			'style' => 'wide',
			
			'fields' => array(
				'duration' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/member_for'),
					'value' => qa_time_to_string(time()-$useraccount['created']),
				),
				
				'level' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/member_type'),
					'value' => qa_html(qa_user_level_string($useraccount['level'])),
				),
				
				'email' => null,
				
				'name' => array(
					'type' => $userediting ? 'text' : 'static',
					'label' => qa_lang_html('users/full_name'),
					'tags' => ' NAME="name" ',
					'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
					'error' => qa_html(@$errors['name']),
				),
			
				'location' => array(
					'type' => $userediting ? 'text' : 'static',
					'label' => qa_lang_html('users/location'),
					'tags' => ' NAME="location" ',
					'value' => qa_html(isset($inlocation) ? $inlocation : @$userprofile['location']),
					'error' => qa_html(@$errors['location']),
				),
	
				'website' => array(
					'type' => $userediting ? 'text' : 'static',
					'label' => qa_lang_html('users/website'),
					'tags' => ' NAME="website" ',
					'value' => $userediting
									? qa_html(isset($inwebsite) ? $inwebsite : @$userprofile['website'])
									: qa_url_to_html_link(@$userprofile['website']),
					'error' => qa_html(@$errors['website']),
				),
	
				'about' => array(
					'type' => $userediting ? 'text' : 'static',
					'label' => qa_lang_html('users/about'),
					'tags' => ' NAME="about" ',
					'value' => qa_html(isset($inabout) ? $inabout : @$userprofile['about']),
					'error' => qa_html(@$errors['about']),
					'rows' => 8,
				),
			),
		);
		
		if ($usereditable)
			$qa_content['form']['fields']['email']=array(
				'type' => $userediting ? 'text' : 'static',
				'label' => qa_lang_html('users/email_label'),
				'tags' => ' NAME="email" ',
				'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
				'error' => qa_html(@$errors['email']),
				'note' => qa_lang_html('users/only_shown_admins'),
			);
		else
			unset($qa_content['form']['fields']['email']);
		
		if ($userediting) {
			if ($useradminable) {
				$qa_content['form']['fields']['level']['type']='select';
	
				$qa_content['form']['fields']['level']['tags']=' NAME="level" ';
				
				$qa_content['form']['fields']['level']['options']=array(
					QA_USER_LEVEL_BASIC => qa_html(qa_user_level_string(QA_USER_LEVEL_BASIC)),
					QA_USER_LEVEL_EDITOR => qa_html(qa_user_level_string(QA_USER_LEVEL_EDITOR)),
				);
				
				if ($qa_login_level>=QA_USER_LEVEL_SUPER) {
					$qa_content['form']['fields']['level']['options'][QA_USER_LEVEL_ADMIN]=qa_html(qa_user_level_string(QA_USER_LEVEL_ADMIN));
					$qa_content['form']['fields']['level']['options'][QA_USER_LEVEL_SUPER]=qa_html(qa_user_level_string(QA_USER_LEVEL_SUPER));
				}
			}
		
			$qa_content['form']['buttons']=array(
				'save' => array(
					'label' => qa_lang_html('users/save_profile'),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('users/cancel_button'),
				),
			);
			
			$qa_content['form']['hidden']=array(
				'dosave' => '1',
			);
	
		} elseif ($usereditable) {
			$qa_content['form']['buttons']=array(
				'edit' => array(
					'tags' => ' NAME="doedit" ',
					'label' => qa_lang_html('users/edit_button'),
				),
			);
		}
	}
	
	$netvotesin=number_format(round(@$userpoints['qvoteds']/qa_get_option($qa_db, 'points_per_q_voted')+@$userpoints['avoteds']/qa_get_option($qa_db, 'points_per_a_voted')));
	if ($netvotesin>0)
		$netvotesin='+'.$netvotesin;
	
	$qa_content['form_2']=array(
		'title' => qa_lang_sub_html('profile/activity_by_x', $userhtml),
		
		'style' => 'wide',
		
		'fields' => array(
			'points' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/score'),
				'value' => (@$userpoints['points']==1)
					? qa_lang_sub_html('main/1_point', '<SPAN CLASS="qa-uf-user-points">1</SPAN>', '1')
					: qa_lang_sub_html('main/x_points', '<SPAN CLASS="qa-uf-user-points">'.qa_html(number_format(@$userpoints['points'])).'</SPAN>')
			),
	
			'questions' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/questions'),
				'value' => '<SPAN CLASS="qa-uf-user-q-posts">'.qa_html(number_format(@$userpoints['qposts'])).'</SPAN>',
			),
	
			'answers' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/answers'),
				'value' => '<SPAN CLASS="qa-uf-user-a-posts">'.qa_html(number_format(@$userpoints['aposts'])).'</SPAN>',
			),
		),
	);
	
	if (qa_get_option($qa_db, 'comment_on_qs') || qa_get_option($qa_db, 'comment_on_as')) {
		$qa_content['form_2']['fields']['comments']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/comments'),
			'value' => '<SPAN CLASS="qa-uf-user-c-posts">'.qa_html(number_format(@$userpoints['cposts'])).'</SPAN>',
		);
	}
	
	if (qa_get_option($qa_db, 'voting_on_qs') || qa_get_option($qa_db, 'voting_on_as')) {
		$votedonvalue='';
		
		if (qa_get_option($qa_db, 'voting_on_qs')) {
			$innervalue='<SPAN CLASS="qa-uf-user-q-votes">'.number_format(@$userpoints['qvotes']).'</SPAN>';
			$votedonvalue.=(@$userpoints['qvotes']==1) ? qa_lang_sub_html('main/1_question', $innervalue, '1')
				: qa_lang_sub_html('main/x_questions', $innervalue);
				
			if (qa_get_option($qa_db, 'voting_on_as'))
				$votedonvalue.=', ';
		}
		
		if (qa_get_option($qa_db, 'voting_on_as')) {
			$innervalue='<SPAN CLASS="qa-uf-user-a-votes">'.number_format(@$userpoints['avotes']).'</SPAN>';
			$votedonvalue.=(@$userpoints['avotes']==1) ? qa_lang_sub_html('main/1_answer', $innervalue, '1')
				: qa_lang_sub_html('main/x_answers', $innervalue);
		}
		
		$qa_content['form_2']['fields']['votedon']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/voted_on'),
			'value' => $votedonvalue,
		);
		
		$innervalue='<SPAN CLASS="qa-uf-user-upvoteds">'.number_format(@$userpoints['upvoteds']).'</SPAN>';
		$votegotvalue=(@$userpoints['upvoteds']==1) ? qa_lang_sub_html('profile/1_up_vote', $innervalue, '1')
			: qa_lang_sub_html('profile/x_up_votes', $innervalue);
			
		$votegotvalue.=', ';
	
		$innervalue='<SPAN CLASS="qa-uf-user-downvoteds">'.number_format(@$userpoints['downvoteds']).'</SPAN>';
		$votegotvalue.=(@$userpoints['downvoteds']==1) ? qa_lang_sub_html('profile/1_down_vote', $innervalue, '1')
			: qa_lang_sub_html('profile/x_down_votes', $innervalue);

		$qa_content['form_2']['fields']['votegot']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/received'),
			'value' => $votegotvalue,
		);
	}
	
	if (@$userpoints['points'])
		$qa_content['form_2']['fields']['points']['value'].=
			qa_lang_sub_html('profile/ranked_x', '<SPAN CLASS="qa-uf-user-rank">'.number_format($userrank).'</SPAN>');
	
	if (@$userpoints['aselects'])
		$qa_content['form_2']['fields']['questions']['value'].=($userpoints['aselects']==1)
			? qa_lang_sub_html('profile/1_with_best_chosen', '<SPAN CLASS="qa-uf-user-q-selects">1</SPAN>', '1')
			: qa_lang_sub_html('profile/x_with_best_chosen', '<SPAN CLASS="qa-uf-user-q-selects">'.number_format($userpoints['aselects']).'</SPAN>');
	
	if (@$userpoints['aselecteds'])
		$qa_content['form_2']['fields']['answers']['value'].=($userpoints['aselecteds']==1)
			? qa_lang_sub_html('profile/1_chosen_as_best', '<SPAN CLASS="qa-uf-user-a-selecteds">1</SPAN>', '1')
			: qa_lang_sub_html('profile/x_chosen_as_best', '<SPAN CLASS="qa-uf-user-a-selecteds">'.number_format($userpoints['aselecteds']).'</SPAN>');

	if ($pagesize_qs>0) {
		if (count($questions))
			$qa_content['q_list']['title']=qa_lang_sub_html('profile/questions_by_x', $userhtml);
		else
			$qa_content['q_list']['title']=qa_lang_sub_html('profile/no_questions_by_x', $userhtml);
	
		$qa_content['q_list']['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		);
		
		$qa_content['q_list']['qs']=array();
		foreach ($questions as $postid => $question) {
			$question['userid']=$userid;
			$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_get_vote_view($qa_db, 'Q'), false);
		}
	}

	if ($pagesize_as>0) {
		if (count($answerquestions))
			$qa_content['a_list']['title']=qa_lang_sub_html('profile/answers_by_x', $userhtml);
		else
			$qa_content['a_list']['title']=qa_lang_sub_html('profile/no_answers_by_x', $userhtml);
			
		$qa_content['a_list']['qs']=array();
		foreach ($answerquestions as $questionid => $answerquestion)
			$qa_content['a_list']['qs'][]=qa_a_or_c_to_q_html_fields($answerquestion, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_get_vote_view($qa_db, 'Q'), false, 'A', $answerquestion['apostid'], $answerquestion['acreated'], $userid, null, null);
	}

?>