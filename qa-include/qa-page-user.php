<?php
	
/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-user.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Controller for user profile page


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
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	

	function qa_page_user_not_found()
/*
	Set up $qa_content to show a message that the user was not found
*/
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
		
		$usershtml=qa_get_users_html($qa_db, array($userid), false, $qa_root_url_relative, true);
		$userhtml=@$usershtml[$userid];

	} else {
		$handle=$pass_handle; // picked up from qa-page.php
		$userhtml=qa_html($handle);
	}

	
//	Find the user profile and questions and answers for this handle
	
	qa_options_set_pending(array('page_size_user_qs', 'page_size_user_as', 'show_when_created', 'points_per_q_voted', 'points_per_a_voted',
		'voting_on_qs', 'voting_on_as', 'votes_separated', 'comment_on_qs', 'comment_on_as', 'confirm_user_emails', 'block_bad_words'));
	
	$identifier=QA_EXTERNAL_USERS ? $userid : $handle;

	@list($useraccount, $userprofile, $userpoints, $userrank, $questions, $answerquestions, $categories)=qa_db_select_with_pending($qa_db,
		QA_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
		QA_EXTERNAL_USERS ? null : qa_db_user_profile_selectspec($handle, false),
		qa_db_user_points_selectspec($identifier),
		qa_db_user_rank_selectspec($identifier),
		qa_db_user_recent_qs_selectspec($qa_login_userid, $identifier),
		qa_db_user_recent_a_qs_selectspec($qa_login_userid, $identifier),
		qa_db_categories_selectspec()
	);
	

//	Check the user exists and work out what can and can't be set (if not using single sign-on)
	
	if (!QA_EXTERNAL_USERS) { // if we're using integrated user management, we can know and show more
		if ((!is_array($userpoints)) && !is_array($useraccount))
			return qa_page_user_not_found();
	
		$userid=$useraccount['userid'];
		$loginlevel=qa_get_logged_in_level($qa_db);

		$fieldseditable=false;
		$maxlevelassign=null;
		
		if (
			$qa_login_userid &&
			($qa_login_userid!=$userid) &&
			(($loginlevel>=QA_USER_LEVEL_SUPER) || ($loginlevel>$useraccount['level'])) &&
			(!qa_user_permit_error($qa_db))
		) { // can't change self - or someone on your level (or higher, obviously) unless you're a super admin
		
			if ($loginlevel>=QA_USER_LEVEL_SUPER)
				$maxlevelassign=QA_USER_LEVEL_SUPER;

			elseif ($loginlevel>=QA_USER_LEVEL_ADMIN)
				$maxlevelassign=QA_USER_LEVEL_MODERATOR;

			elseif ($loginlevel>=QA_USER_LEVEL_MODERATOR)
				$maxlevelassign=QA_USER_LEVEL_EXPERT;
				
			if ($loginlevel>=QA_USER_LEVEL_ADMIN)
				$fieldseditable=true;
			
			if (isset($maxlevelassign) && ($useraccount['flags'] & QA_USER_FLAGS_USER_BLOCKED))
				$maxlevelassign=min($maxlevelassign, QA_USER_LEVEL_EDITOR); // if blocked, can't promote too high
		}
		
		$usereditbutton=$fieldseditable || isset($maxlevelassign);
		$userediting=false;
	}


//	Process edit or save button for user

	if (!QA_EXTERNAL_USERS) {
		$reloaduser=false;
		
		if ($usereditbutton) {
			if (qa_clicked('docancel'))
				;
			
			elseif (qa_clicked('doedit'))
				$userediting=true;
				
			elseif (qa_clicked('dosave')) {
				require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
				
				if ($fieldseditable) {
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
						if ($inemail != $useraccount['email']) {
							qa_db_user_set($qa_db, $userid, 'email', $inemail);
							qa_db_user_set_flag($qa_db, $userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
						}
		
					if (!isset($errors['name']))
						qa_db_user_profile_set($qa_db, $userid, 'name', $inname);
			
					if (!isset($errors['location']))
						qa_db_user_profile_set($qa_db, $userid, 'location', $inlocation);
			
					if (!isset($errors['website']))
						qa_db_user_profile_set($qa_db, $userid, 'website', $inwebsite);
			
					if (!isset($errors['about']))
						qa_db_user_profile_set($qa_db, $userid, 'about', $inabout);
	
					if (count($errors))
						$userediting=true;
				}
	
				if (isset($maxlevelassign))
					qa_db_user_set($qa_db, $userid, 'level', min($maxlevelassign, (int)qa_post_text('level')));
						// constrain based on maximum permitted to prevent simple browser-based attack
				
				$reloaduser=true;
			}
		}
		
		if (isset($maxlevelassign) && ($useraccount['level']<QA_USER_LEVEL_MODERATOR)) {
			if (qa_clicked('doblock')) {
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
				
				qa_db_user_set_flag($qa_db, $userid, QA_USER_FLAGS_USER_BLOCKED, true);
				$reloaduser=true;
			}

			if (qa_clicked('dounblock')) {
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
				
				qa_db_user_set_flag($qa_db, $userid, QA_USER_FLAGS_USER_BLOCKED, false);
				$reloaduser=true;
			}
		}
		
		if ($reloaduser)
			list($useraccount, $userprofile)=qa_db_select_with_pending($qa_db,
				qa_db_user_account_selectspec($userid, true),
				qa_db_user_profile_selectspec($userid, true)
			);
	}


//	Get information on user references in answers and other stuff need for page
	
	$pagesize_qs=qa_get_option($qa_db, 'page_size_user_qs');
	$pagesize_as=qa_get_option($qa_db, 'page_size_user_as');

	$questions=array_slice($questions, 0, $pagesize_qs);
	$answerquestions=array_slice($answerquestions, 0, $pagesize_as);
	$usershtml=qa_userids_handles_html($qa_db, $answerquestions);
	$usershtml[$userid]=$userhtml;

	
//	Prepare content for theme
	
	qa_content_prepare(true);
	
	$qa_content['title']=qa_lang_html_sub('profile/user_x', $userhtml);


//	General information about the user, only available if we're using internal user management
	
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
					'tags' => ' NAME="level" ',
					'value' => qa_html(qa_user_level_string($useraccount['level'])),
					'note' => (($useraccount['flags'] & QA_USER_FLAGS_USER_BLOCKED) && isset($maxlevelassign)) ? qa_lang_html('users/user_blocked') : '',
				),
				
				// these are placed here already to get the order right
				
				'email' => null,
				
				'lastlogin' => null,
				
				'lastwrite' => null, 
				
				'name' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/full_name'),
					'tags' => ' NAME="name" ',
					'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
					'error' => qa_html(@$errors['name']),
				),
			
				'location' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/location'),
					'tags' => ' NAME="location" ',
					'value' => qa_html(isset($inlocation) ? $inlocation : @$userprofile['location']),
					'error' => qa_html(@$errors['location']),
				),
	
				'website' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/website'),
					'tags' => ' NAME="website" ',
					'value' => qa_url_to_html_link(@$userprofile['website']),
					'error' => qa_html(@$errors['website']),
				),
	
				'about' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/about'),
					'tags' => ' NAME="about" ',
					'value' => qa_html(isset($inabout) ? $inabout : @$userprofile['about']),
					'error' => qa_html(@$errors['about']),
					'rows' => 8,
				),
			),
		);
		
	
	//	Show email address only if we're an administrator
		
		if (($loginlevel>=QA_USER_LEVEL_ADMIN) && !qa_user_permit_error($qa_db)) {
			$doconfirms=qa_get_option($qa_db, 'confirm_user_emails') && ($useraccount['level']<QA_USER_LEVEL_EXPERT);
			$isconfirmed=($useraccount['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;
	
			$qa_content['form']['fields']['email']=array(
				'type' => $userediting ? 'text' : 'static',
				'label' => qa_lang_html('users/email_label'),
				'tags' => ' NAME="email" ',
				'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
				'error' => qa_html(@$errors['email']),
				'note' => ($doconfirms ? (qa_lang_html($isconfirmed ? 'users/email_confirmed' : 'users/email_not_confirmed').' ') : '').
					qa_lang_html('users/only_shown_admins'),
			);

		} else
			unset($qa_content['form']['fields']['email']);
			
	
	//	Show IP addresses and times for last login or write - only if we're a moderator or higher
	
		if (($loginlevel>=QA_USER_LEVEL_MODERATOR) && !qa_user_permit_error($qa_db)) {
			$qa_content['form']['fields']['lastlogin']=array(
				'type' => 'static',
				'label' => qa_lang_html('users/last_login_label'),
				'value' =>
					strtr(qa_lang_html('users/x_ago_from_y'), array(
						'^1' => qa_time_to_string(time()-$useraccount['loggedin']),
						'^2' => qa_ip_anchor_html($useraccount['loginip']),
					)),
				'note' => qa_lang_html('users/only_shown_moderators'),
			);

			if (isset($useraccount['written']))
				$qa_content['form']['fields']['lastwrite']=array(
					'type' => 'static',
					'label' => qa_lang_html('users/last_write_label'),
					'value' =>
						strtr(qa_lang_html('users/x_ago_from_y'), array(
							'^1' => qa_time_to_string(time()-$useraccount['written']),
							'^2' => qa_ip_anchor_html($useraccount['writeip']),
						)),
					'note' => qa_lang_html('users/only_shown_moderators'),
				);
				else
					unset($qa_content['form']['fields']['lastwrite']);

		} else {
			unset($qa_content['form']['fields']['lastlogin']);
			unset($qa_content['form']['fields']['lastwrite']);
		}


	//	Edit form or button, if appropriate
		
		if ($usereditbutton) {

			if ($userediting) {

				if (isset($maxlevelassign)) {
					$qa_content['form']['fields']['level']['type']='select';
		
					$leveloptions=array(QA_USER_LEVEL_BASIC, QA_USER_LEVEL_EXPERT, QA_USER_LEVEL_EDITOR, QA_USER_LEVEL_MODERATOR, QA_USER_LEVEL_ADMIN, QA_USER_LEVEL_SUPER);
	
					foreach ($leveloptions as $leveloption)
						if ($leveloption<=$maxlevelassign)
							$qa_content['form']['fields']['level']['options'][$leveloption]=qa_html(qa_user_level_string($leveloption));
				}
				
				if ($fieldseditable) {
					$qa_content['form']['fields']['name']['type']='text';
					$qa_content['form']['fields']['location']['type']='text';
					$qa_content['form']['fields']['website']['type']='text';
					$qa_content['form']['fields']['about']['type']='text';
					
					$qa_content['form']['fields']['website']['value']=qa_html(isset($inwebsite) ? $inwebsite : @$userprofile['website']);
				}
		
				$qa_content['form']['buttons']=array(
					'save' => array(
						'label' => qa_lang_html('users/save_user'),
					),
					
					'cancel' => array(
						'tags' => ' NAME="docancel" ',
						'label' => qa_lang_html('main/cancel_button'),
					),
				);
				
				$qa_content['form']['hidden']=array(
					'dosave' => '1',
				);

			} else {
				$qa_content['form']['buttons']=array(
					'edit' => array(
						'tags' => ' NAME="doedit" ',
						'label' => qa_lang_html($fieldseditable ? 'users/edit_user_button' : 'users/edit_level_button'),
					),
				);
				
				if (isset($maxlevelassign) && ($useraccount['level']<QA_USER_LEVEL_MODERATOR)) {
					if ($useraccount['flags'] & QA_USER_FLAGS_USER_BLOCKED)
						$qa_content['form']['buttons']['unblock']=array(
							'tags' => ' NAME="dounblock" ',
							'label' => qa_lang_html('users/unblock_user_button'),
						);
					else
						$qa_content['form']['buttons']['block']=array(
							'tags' => ' NAME="doblock" ',
							'label' => qa_lang_html('users/block_user_button'),
						);
				}
			}
		}
	}
	

//	Information about user activity, available also with single sign-on integration

	$netvotesin=number_format(round(@$userpoints['qvoteds']/qa_get_option($qa_db, 'points_per_q_voted')+@$userpoints['avoteds']/qa_get_option($qa_db, 'points_per_a_voted')));
	if ($netvotesin>0)
		$netvotesin='+'.$netvotesin;
	
	$qa_content['form_2']=array(
		'title' => qa_lang_html_sub('profile/activity_by_x', $userhtml),
		
		'style' => 'wide',
		
		'fields' => array(
			'points' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/score'),
				'value' => (@$userpoints['points']==1)
					? qa_lang_html_sub('main/1_point', '<SPAN CLASS="qa-uf-user-points">1</SPAN>', '1')
					: qa_lang_html_sub('main/x_points', '<SPAN CLASS="qa-uf-user-points">'.qa_html(number_format(@$userpoints['points'])).'</SPAN>')
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
	
	if (qa_get_option($qa_db, 'comment_on_qs') || qa_get_option($qa_db, 'comment_on_as')) { // only show comment count if comments are enabled
		$qa_content['form_2']['fields']['comments']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/comments'),
			'value' => '<SPAN CLASS="qa-uf-user-c-posts">'.qa_html(number_format(@$userpoints['cposts'])).'</SPAN>',
		);
	}
	
	if (qa_get_option($qa_db, 'voting_on_qs') || qa_get_option($qa_db, 'voting_on_as')) { // only show vote record if voting is enabled
		$votedonvalue='';
		
		if (qa_get_option($qa_db, 'voting_on_qs')) {
			$qvotes=@$userpoints['qupvotes']+@$userpoints['qdownvotes'];

			$innervalue='<SPAN CLASS="qa-uf-user-q-votes">'.number_format($qvotes).'</SPAN>';
			$votedonvalue.=($qvotes==1) ? qa_lang_html_sub('main/1_question', $innervalue, '1')
				: qa_lang_html_sub('main/x_questions', $innervalue);
				
			if (qa_get_option($qa_db, 'voting_on_as'))
				$votedonvalue.=', ';
		}
		
		if (qa_get_option($qa_db, 'voting_on_as')) {
			$avotes=@$userpoints['aupvotes']+@$userpoints['adownvotes'];
			
			$innervalue='<SPAN CLASS="qa-uf-user-a-votes">'.number_format($avotes).'</SPAN>';
			$votedonvalue.=($avotes==1) ? qa_lang_html_sub('main/1_answer', $innervalue, '1')
				: qa_lang_html_sub('main/x_answers', $innervalue);
		}
		
		$qa_content['form_2']['fields']['votedon']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/voted_on'),
			'value' => $votedonvalue,
		);
		
		$upvotes=@$userpoints['qupvotes']+@$userpoints['aupvotes'];
		$innervalue='<SPAN CLASS="qa-uf-user-upvotes">'.number_format($upvotes).'</SPAN>';
		$votegavevalue=(($upvotes==1) ? qa_lang_html_sub('profile/1_up_vote', $innervalue, '1') : qa_lang_html_sub('profile/x_up_votes', $innervalue)).', ';
		
		$downvotes=@$userpoints['qdownvotes']+@$userpoints['adownvotes'];
		$innervalue='<SPAN CLASS="qa-uf-user-downvotes">'.number_format($downvotes).'</SPAN>';
		$votegavevalue.=($downvotes==1) ? qa_lang_html_sub('profile/1_down_vote', $innervalue, '1') : qa_lang_html_sub('profile/x_down_votes', $innervalue);
		
		$qa_content['form_2']['fields']['votegave']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/gave_out'),
			'value' => $votegavevalue,
		);

		$innervalue='<SPAN CLASS="qa-uf-user-upvoteds">'.number_format(@$userpoints['upvoteds']).'</SPAN>';
		$votegotvalue=((@$userpoints['upvoteds']==1) ? qa_lang_html_sub('profile/1_up_vote', $innervalue, '1')
			: qa_lang_html_sub('profile/x_up_votes', $innervalue)).', ';
			
		$innervalue='<SPAN CLASS="qa-uf-user-downvoteds">'.number_format(@$userpoints['downvoteds']).'</SPAN>';
		$votegotvalue.=(@$userpoints['downvoteds']==1) ? qa_lang_html_sub('profile/1_down_vote', $innervalue, '1')
			: qa_lang_html_sub('profile/x_down_votes', $innervalue);

		$qa_content['form_2']['fields']['votegot']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/received'),
			'value' => $votegotvalue,
		);
	}
	
	if (@$userpoints['points'])
		$qa_content['form_2']['fields']['points']['value'].=
			qa_lang_html_sub('profile/ranked_x', '<SPAN CLASS="qa-uf-user-rank">'.number_format($userrank).'</SPAN>');
	
	if (@$userpoints['aselects'])
		$qa_content['form_2']['fields']['questions']['value'].=($userpoints['aselects']==1)
			? qa_lang_html_sub('profile/1_with_best_chosen', '<SPAN CLASS="qa-uf-user-q-selects">1</SPAN>', '1')
			: qa_lang_html_sub('profile/x_with_best_chosen', '<SPAN CLASS="qa-uf-user-q-selects">'.number_format($userpoints['aselects']).'</SPAN>');
	
	if (@$userpoints['aselecteds'])
		$qa_content['form_2']['fields']['answers']['value'].=($userpoints['aselecteds']==1)
			? qa_lang_html_sub('profile/1_chosen_as_best', '<SPAN CLASS="qa-uf-user-a-selecteds">1</SPAN>', '1')
			: qa_lang_html_sub('profile/x_chosen_as_best', '<SPAN CLASS="qa-uf-user-a-selecteds">'.number_format($userpoints['aselecteds']).'</SPAN>');


//	Recent questions by this user

	if ($pagesize_qs>0) {
		if (count($questions))
			$qa_content['q_list']['title']=qa_lang_html_sub('profile/questions_by_x', $userhtml);
		else
			$qa_content['q_list']['title']=qa_lang_html_sub('profile/no_questions_by_x', $userhtml);
	
		$qa_content['q_list']['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
		);
		
		$qa_content['q_list']['qs']=array();
		foreach ($questions as $postid => $question) {
			$question['userid']=$userid;
			$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml,
				qa_using_tags($qa_db), qa_using_categories($qa_db) ? $categories : null,
				qa_get_vote_view($qa_db, 'Q'), qa_get_option($qa_db, 'show_when_created'),
				false, false, qa_get_block_words_preg($qa_db));
		}
	}


//	Recent answers by this user

	if ($pagesize_as>0) {
		if (count($answerquestions))
			$qa_content['a_list']['title']=qa_lang_html_sub('profile/answers_by_x', $userhtml);
		else
			$qa_content['a_list']['title']=qa_lang_html_sub('profile/no_answers_by_x', $userhtml);
			
		$qa_content['a_list']['qs']=array();
		foreach ($answerquestions as $questionid => $answerquestion)
			$qa_content['a_list']['qs'][]=qa_a_or_c_to_q_html_fields($answerquestion, $qa_login_userid, $qa_cookieid, $usershtml,
				false, null, qa_get_vote_view($qa_db, 'Q'), qa_get_option($qa_db, 'show_when_created'), false, false,
				qa_get_block_words_preg($qa_db), 'A', $answerquestion['apostid'], $answerquestion['acreated'], $userid, null, null, null);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/