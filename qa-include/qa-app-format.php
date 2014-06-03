<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-format.php
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

	function qa_time_to_string($seconds)
	{
		$seconds=max($seconds, 1);
		
		$scales=array(
			31557600 => array( 'main/1_year'   , 'main/x_years'   ),
			 2629800 => array( 'main/1_month'  , 'main/x_months'  ),
			  604800 => array( 'main/1_week'   , 'main/x_weeks'   ),
			   86400 => array( 'main/1_day'    , 'main/x_days'    ),
			    3600 => array( 'main/1_hour'   , 'main/x_hours'   ),
			      60 => array( 'main/1_minute' , 'main/x_minutes' ),
			       1 => array( 'main/1_second' , 'main/x_seconds' ),
		);
		
		foreach ($scales as $scale => $phrases)
			if ($seconds>=$scale) {
				$count=floor($seconds/$scale);
			
				if ($count==1)
					$string=qa_lang($phrases[0]);
				else
					$string=qa_lang_sub($phrases[1], $count);
					
				break;
			}
			
		return $string;
	}	

	function qa_post_is_by_user($post, $userid, $cookieid)
	{
		// In theory we should only test against NULL here, i.e. use isset($post['userid'])
		// but the risk of doing so is so high (if a bug creeps in that allows userid=0)
		// that I'm doing a tougher test. This will break under a zero user or cookie id.
		
		if (@$post['userid'] || $userid)
			return @$post['userid']==$userid;
		elseif (@$post['cookieid'])
			return @$post['cookieid']==$cookieid;
		
		return false;
	}
	
	function qa_userids_handles_html($db, $useridhandles, $microformats=false)
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
		if (QA_EXTERNAL_USERS) {
			$keyuserids=array();
	
			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']))
					$keyuserids[$useridhandle['userid']]=true;

				if (isset($useridhandle['lastuserid']))
					$keyuserids[$useridhandle['lastuserid']]=true;
			}
	
			if (count($keyuserids))
				return qa_get_users_html($db, array_keys($keyuserids), true, qa_path(''), $microformats);
			else
				return array();
		
		} else {
			$usershtml=array();

			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']) && $useridhandle['handle'])
					$usershtml[$useridhandle['userid']]=qa_get_one_user_html($useridhandle['handle'], $microformats);

				if (isset($useridhandle['lastuserid']) && $useridhandle['lasthandle'])
					$usershtml[$useridhandle['lastuserid']]=qa_get_one_user_html($useridhandle['lasthandle'], $microformats);
			}
		
			return $usershtml;
		}
	}
	
	function qa_tag_html($tag, $microformats=false)
	{
		return '<A HREF="'.qa_path_html('tag/'.urlencode($tag)).'"'.($microformats ? ' rel="tag"' : '').' CLASS="qa-tag-link">'.qa_html($tag).'</A>';
	}
	
	function qa_post_html_fields($post, $userid, $cookieid, $usershtml, $voteview=false, $pointsview=false, $showurllinks=false, $microformats=false, $isselected=false)
	{
		$fields=array();
		
	//	Useful stuff used throughout function

		$postid=$post['postid'];
		$isquestion=($post['basetype']=='Q');
		$isanswer=($post['basetype']=='A');
		$isbyuser=qa_post_is_by_user($post, $userid, $cookieid);
		
	//	High level information

		$fields['hidden']=$post['hidden'];
		$fields['tags']=' ID="'.qa_html($postid).'" ';
		
		if ($microformats)
			$fields['classes']=' hentry '.($isquestion ? 'question' : ($isanswer ? ($isselected ? 'answer answer-selected' : 'answer') : 'comment'));
	
	//	Question-specific stuff (title, URL, tags, answer count)
	
		if ($isquestion) {
			if (isset($post['title'])) {
				$fields['title']=qa_html($post['title']);
				if ($microformats)
					$fields['title']='<SPAN CLASS="entry-title">'.$fields['title'].'</SPAN>';
					
				$fields['url']=qa_path_html(qa_q_request($postid, $post['title']));
				
				/*if (isset($post['score'])) // useful for setting match thresholds
					$fields['title'].=' <SMALL>('.$post['score'].')</SMALL>';*/
			}
				
			if (isset($post['tags'])) {
				$fields['q_tags']=array();
				
				$tags=qa_tagstring_to_tags($post['tags']);
				foreach ($tags as $tag)
					$fields['q_tags'][]=qa_tag_html($tag, $microformats);
			}
		
			if (isset($post['acount']))
				$fields['answers']=($post['acount']==1) ? qa_lang_sub_split_html('main/1_answer', '1', '1')
					: qa_lang_sub_split_html('main/x_answers', number_format($post['acount']));
		}
		
	//	Answer-specific stuff (selection)
		
		if ($isanswer) {
			$fields['selected']=$isselected;
			
			if ($isselected)
				$fields['select_text']=qa_lang_html('question/select_text');
		}				

	//	Post content
		
		if (!empty($post['content'])) {
			$fields['content']=qa_html($post['content'], true); // also used for rendering content when asking follow-on q
			
			if ($showurllinks)
				$fields['content']=qa_html_convert_urls($fields['content']);
			
			if ($microformats)
				$fields['content']='<SPAN CLASS="entry-content">'.$fields['content'].'</SPAN>';
		}
		
	//	Voting stuff
			
		if ($voteview) {
		
		//	Calculate raw values and pass through
		
			$upvotes=(int)@$post['upvotes'];
			$downvotes=(int)@$post['downvotes'];
			$netvotes=(int)($upvotes-$downvotes);
			
			$fields['upvotes_raw']=$upvotes;
			$fields['downvotes_raw']=$downvotes;
			$fields['netvotes_raw']=$netvotes;

		//	Create HTML versions...
			
			$upvoteshtml=qa_html($upvotes);
			$downvoteshtml=qa_html($downvotes);

			if ($netvotes>=1)
				$netvoteshtml='+'.qa_html($netvotes);
			elseif ($netvotes<=-1)
				$netvoteshtml='&ndash;'.qa_html(-$netvotes);
			else
				$netvoteshtml='0';
				
		//	...with microformats if appropriate

			if ($microformats) {
				$netvoteshtml.='<SPAN CLASS="votes-up"><SPAN CLASS="value-title" TITLE="'.$upvoteshtml.'"></SPAN></SPAN>'.
					'<SPAN CLASS="votes-down"><SPAN CLASS="value-title" TITLE="'.$downvoteshtml.'"></SPAN></SPAN>';
				$upvoteshtml='<SPAN CLASS="votes-up">'.$upvoteshtml.'</SPAN>';
				$downvoteshtml='<SPAN CLASS="votes-down">'.$downvoteshtml.'</SPAN>';
			}
			
		//	Pass information on vote viewing
				
			$fields['vote_view']=$voteview;
			
			$fields['upvotes_view']=($upvotes==1) ? qa_lang_sub_split_html('main/1_liked', $upvoteshtml, '1')
				: qa_lang_sub_split_html('main/x_liked', $upvoteshtml);
	
			$fields['downvotes_view']=($downvotes==1) ? qa_lang_sub_split_html('main/1_disliked', $downvoteshtml, '1')
				: qa_lang_sub_split_html('main/x_disliked', $downvoteshtml);			
			
			$fields['netvotes_view']=(abs($netvotes)==1) ? qa_lang_sub_split_html('main/1_vote', $netvoteshtml, '1')
				: qa_lang_sub_split_html('main/x_votes', $netvoteshtml);
		
		//	Voting buttons
		
			$fields['vote_tags']=' ID="voting_'.qa_html($postid).'" ';		
			$onclick='onClick="return qa_vote_click(this);" ';
			
			if ($fields['hidden']) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html($isanswer ? 'main/vote_disabled_hidden_a' : 'main/vote_disabled_hidden_q').'" DISABLED="disabled" ';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
			
			} elseif ($isbyuser) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html($isanswer ? 'main/vote_disabled_my_a' : 'main/vote_disabled_my_q').'" DISABLED="disabled" ';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
				
			} elseif (@$post['uservote']>0) {
				$fields['vote_state']='voted_up';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html('main/voted_up_popup').'" NAME="vote_'.qa_html($postid).'_0" '.$onclick;
				$fields['vote_down_tags']=' DISABLED="disabled" ';

			} elseif (@$post['uservote']<0) {
				$fields['vote_state']='voted_down';
				$fields['vote_up_tags']=' DISABLED="disabled" ';
				$fields['vote_down_tags']=' TITLE="'.qa_lang_html('main/voted_down_popup').'" NAME="vote_'.qa_html($postid).'_0" '.$onclick;
				
			} else {
				$fields['vote_state']='enabled';			
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html('main/vote_up_popup').'" NAME="vote_'.qa_html($postid).'_1" '.$onclick;
				$fields['vote_down_tags']=' TITLE="'.qa_lang_html('main/vote_down_popup').'" NAME="vote_'.qa_html($postid).'_-1" '.$onclick;
			}
		}
		
	//	Created when and by whom
		
		if (isset($post['created'])) {
			$whenhtml=qa_html(qa_time_to_string(time()-$post['created']));		
			if ($microformats)
				$whenhtml='<SPAN CLASS="published"><SPAN CLASS="value-title" TITLE="'.gmdate('Y-m-d\TH:i:sO', $post['created']).'"></SPAN>'.$whenhtml.'</SPAN>';
			
			$fields['when']=qa_lang_sub_split_html($isquestion ? 'main/asked_x_ago' : ($isanswer ? 'main/answered_x_ago' : 'main/x_ago'), $whenhtml);
		}
		
		$fields['who']=qa_who_to_html($isbyuser, @$post['userid'], $usershtml, $microformats);
		
		if ($pointsview && isset($post['points']))
			$fields['points']=($post['points']==1) ? qa_lang_sub_split_html('main/1_point', '1', '1')
				: qa_lang_sub_split_html('main/x_points', qa_html(number_format($post['points'])));

	//	Updated when and by whom
		
		if (isset($post['updated']) && ( // show the time/user who updated if...
			(!isset($post['created'])) || // ... we didn't show the created time (should never happen in practice)
			($post['hidden']) || // ... the post was actually hidden
			(abs($post['updated']-$post['created'])>300) || // ... or over 5 minutes passed between create and update times
			($post['lastuserid']!=$post['userid']) // ... or it was updated by a different user
		)) {
			$whenhtml=qa_html(qa_time_to_string(time()-$post['updated']));
			if ($microformats)
				$whenhtml='<SPAN CLASS="updated"><SPAN CLASS="value-title" TITLE="'.gmdate('Y-m-d\TH:i:sO', $post['updated']).'"></SPAN>'.$whenhtml.'</SPAN>';
			
			$fields['when_2']=qa_lang_sub_split_html($fields['hidden'] ? 'question/hidden_x_ago' : 'question/edited_x_ago', $whenhtml);			
			$fields['who_2']=qa_who_to_html($post['lastuserid']==$userid, $post['lastuserid'], $usershtml, false);
		}
		
	//	That's it!

		return $fields;
	}
	
	function qa_who_to_html($isbyuser, $postuserid, $usershtml, $microformats)
	{
		if ($isbyuser)
			$whohtml=qa_lang_html('main/me');

		elseif (isset($postuserid) && isset($usershtml[$postuserid])) {
			$whohtml=$usershtml[$postuserid];
			if ($microformats)
				$whohtml='<SPAN CLASS="vcard author">'.$whohtml.'</SPAN>';

		} else
			$whohtml=qa_lang_html('main/anonymous');
			
		return qa_lang_sub_split_html('main/by_x', $whohtml);
	}
	
	function qa_a_or_c_to_q_html_fields($question, $userid, $cookieid, $usershtml, $voteview=false, $pointsview=false, $basetype, $acpostid, $accreated, $acuserid, $accookieid, $acpoints)
	{	
		$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, $voteview, $pointsview);
		
		$fields['url'].='#'.qa_html(urlencode($acpostid));

		$fields['when']=qa_lang_sub_split_html(
			($basetype=='C') ? 'main/commented_x_ago' : ($basetype=='A' ? 'main/answered_x_ago' : 'main/x_ago'),
			qa_html(qa_time_to_string(time()-$accreated))
		);
		
		$isbyuser=qa_post_is_by_user(array('userid' => $acuserid, 'cookieid' => $accookieid), $userid, $cookieid);
		
		$fields['who']=qa_who_to_html($isbyuser, $acuserid, $usershtml, false);

		if ($pointsview && isset($acpoints))
			$fields['points']=($acpoints==1) ? qa_lang_sub_split_html('main/1_point', '1', '1')
				: qa_lang_sub_split_html('main/x_points', qa_html(number_format($acpoints)));
		else
			unset($fields['points']);
		
		return $fields;
	}
	
	function qa_any_to_q_html_fields($question, $userid, $cookieid, $usershtml, $voteview=false, $pointsview=false)
	{	
		if (isset($question['cpostid']))
			$fields=qa_a_or_c_to_q_html_fields($question, $userid, $cookieid, $usershtml, $voteview, $pointsview, 'C',
				$question['cpostid'], @$question['ccreated'], @$question['cuserid'], @$question['ccookieid'], @$question['cpoints']);

		elseif (isset($question['apostid']))
			$fields=qa_a_or_c_to_q_html_fields($question, $userid, $cookieid, $usershtml, $voteview, $pointsview, 'A',
				$question['apostid'], @$question['acreated'], @$question['auserid'], @$question['acookieid'], @$question['apoints']);

		else
			$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, $voteview, $pointsview);

		return $fields;
	}
	
	function qa_any_sort_and_dedupe($questions)
	{
		require_once QA_INCLUDE_DIR.'qa-util-sort.php';
		
		foreach ($questions as $key => $question) { // sort by appropriate created date
			if (isset($question['cpostid']))
				$created=$question['ccreated'];
			elseif (isset($question['apostid']))
				$created=$question['acreated'];
			else
				$created=$question['created'];
				
			$questions[$key]['sort']=-$created;
		}
		
		qa_sort_by($questions, 'sort');
		
		$keyseenq=array(); // now remove duplicate references to same question
		foreach ($questions as $key => $question)
			if (isset($keyseenq[$question['postid']]))
				unset($questions[$key]);
			else
				$keyseenq[$question['postid']]=true;
				
		return $questions;
	}
	
	function qa_any_get_userids_handles($questions)
	{
		$userids_handles=array();
		
		foreach ($questions as $question)
			if (isset($question['cpostid']))
				$userids_handles[]=array(
					'userid' => $question['cuserid'],
					'handle' => @$question['chandle'],
				);
			
			elseif (isset($question['apostid']))
				$userids_handles[]=array(
					'userid' => $question['auserid'],
					'handle' => @$question['ahandle'],
				);
			
			else
				$userids_handles[]=array(
					'userid' => $question['userid'],
					'handle' => @$question['handle'],
				);
			
		return $userids_handles;
	}

	function qa_html_convert_urls($html)
	{
		// URL regular expressions can get crazy: http://internet.ls-la.net/folklore/url-regexpr.html
		// This is something quick and dirty that should do the trick in most cases
		
		return trim(preg_replace('/([^A-Za-z0-9])((http|https|ftp):\/\/\S+\.[^\s<>]+)/i', '\1<A HREF="\2" rel="nofollow">\2</A>', ' '.$html.' '));
	}
	
	function qa_url_to_html_link($url)
	{
		if (is_numeric(strpos($url, '.'))) {
			$linkurl=$url;
			if (!is_numeric(strpos($linkurl, ':/')))
				$linkurl='http://'.$linkurl;
				
			return '<A HREF="'.qa_html($linkurl).'" rel="nofollow">'.qa_html($url).'</A>';
		
		} else
			return $url;
	}
	
	function qa_insert_login_links($htmlmessage, $topage)
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		$userlinks=qa_get_login_links(qa_path(''), qa_path($topage, null, ''));
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => empty($userlinks['login']) ? '' : '<A HREF="'.qa_html($userlinks['login']).'">',
				'^2' => empty($userlinks['login']) ? '' : '</A>',
				'^3' => empty($userlinks['register']) ? '' : '<A HREF="'.qa_html(@$userlinks['register']).'">',
				'^4' => empty($userlinks['register']) ? '' : '</A>',
			)
		);
	}
	
	function qa_html_page_links($path, $start, $pagesize, $count, $prevnext, $params=array(), $hasmore=false)
	{
		$thispage=1+floor($start/$pagesize);
		$lastpage=ceil(min($count, 1+QA_MAX_LIMIT_START)/$pagesize);
		
		if (($thispage>1) || ($lastpage>$thispage)) {
			$links=array('label' => qa_lang_html('main/page_label'), 'items' => array());
			
			$keypages[1]=true;
			
			for ($page=max(2, min($thispage, $lastpage)-$prevnext); $page<=min($thispage+$prevnext, $lastpage); $page++)
				$keypages[$page]=true;
				
			$keypages[$lastpage]=true;
			
			if ($thispage>1)
				$links['items'][]=array(
					'type' => 'prev',
					'label' => qa_lang_html('main/page_prev'),
					'page' => $thispage-1,
					'ellipsis' => false,
				);
				
			foreach (array_keys($keypages) as $page)
				$links['items'][]=array(
					'type' => ($page==$thispage) ? 'this' : 'jump',
					'label' => $page,
					'page' => $page,
					'ellipsis' => (($page<$lastpage) || $hasmore) && (!isset($keypages[$page+1])),
				);
				
			if ($thispage<$lastpage)
				$links['items'][]=array(
					'type' => 'next',
					'label' => qa_lang_html('main/page_next'),
					'page' => $thispage+1,
					'ellipsis' => false,
				);
				
			foreach ($links['items'] as $key => $link)
				if ($link['page']!=$thispage) {
					$params['start']=$pagesize*($link['page']-1);
					$links['items'][$key]['url']=qa_path_html($path, $params);
				}
				
		} else
			$links=null;
		
		return $links;
	}
	
	function qa_html_suggest_qs_tags()
	{
		$htmlmessage=qa_lang_html('main/suggest_qs_tags');
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<A HREF="'.qa_path_html('questions').'">',
				'^2' => '</A>',
				'^3' => '<A HREF="'.qa_path_html('tags').'">',
				'^4' => '</A>',
			)
		);
	}
	
	function qa_html_suggest_ask()
	{
		$htmlmessage=qa_lang_html('main/suggest_ask');
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<A HREF="'.qa_path_html('ask').'">',
				'^2' => '</A>',
			)
		);
	}

	function qa_match_to_min_score($match)
	{
		return 10-2*$match;
	}
	
	function qa_checkbox_to_display(&$qa_content, $effects)
	{
		$function='qa_checkbox_display_'.count(@$qa_content['script_lines']);
		
		$keysourceids=array();
		
		foreach ($effects as $target => $sources) {
			$elements=preg_split('/([^A-Za-z0-9_]+)/', $sources, -1, PREG_SPLIT_NO_EMPTY); // element names must be legal JS variable names
			foreach ($elements as $element)
				$keysourceids[$element]=true;
		}
		
		$funcscript=array("function ".$function."() {"); // build the Javascripts
		$loadscript=array();
		
		foreach ($keysourceids as $key => $dummy) {
			$funcscript[]="\tvar e=document.getElementById(".qa_js($key).");";
			$funcscript[]="\tvar ".$key."=e && e.checked;";
			$loadscript[]="var e=document.getElementById(".qa_js($key).");";
			$loadscript[]="if (e) e.onclick=".$function.";";
		}
			
		foreach ($effects as $target => $sources) {
			$funcscript[]="\tvar e=document.getElementById(".qa_js($target).");";
			$funcscript[]="\tif (e) e.style.display=(".$sources.") ? '' : 'none';";
		}
		
		$funcscript[]="}";
		$loadscript[]=$function."();";
		
		$qa_content['script_lines'][]=$funcscript;
		$qa_content['script_onloads'][]=$loadscript;
	}
	
	function qa_set_up_tag_field(&$qa_content, &$field, $fieldname, $exampletags, $completetags, $maxtags)
	{
		$template='<A HREF="#" CLASS="qa-tag-link" onClick="return qa_tag_click(this);">^</A>';

		$qa_content['script_src'][]='qa-ask.js?'.QA_VERSION;
		$qa_content['script_var']['qa_tag_template']=$template;
		$qa_content['script_var']['qa_tags_examples']=qa_html(implode(' ', $exampletags));
		$qa_content['script_var']['qa_tags_complete']=qa_html(implode(' ', $completetags));
		$qa_content['script_var']['qa_tags_max']=(int)$maxtags;
		
		$field['tags']=' NAME="'.$fieldname.'" ID="tags" AUTOCOMPLETE="off" onKeyUp="qa_tag_hints();" onMouseUp="qa_tag_hints();" ';
		
		$sdn=' STYLE="display:none;"';
		
		$field['note']=
			'<SPAN ID="tag_examples_title"'.(count($exampletags) ? '' : $sdn).'>'.qa_lang_html('question/example_tags').'</SPAN>'.
			'<SPAN ID="tag_complete_title"'.$sdn.'>'.qa_lang_html('question/matching_tags').'</SPAN><SPAN ID="tag_hints">';

		foreach ($exampletags as $tag)
			$field['note'].=str_replace('^', qa_html($tag), $template).' ';

		$field['note'].='</SPAN>';
	}
	
	function qa_set_up_notify_fields(&$qa_content, &$fields, $basetype, $login_email, $innotify, $inemail, $errors_email)
	{
		$fields['notify']=array(
			'tags' => ' NAME="notify" ',
			'type' => 'checkbox',
			'value' => qa_html($innotify),
		);

		switch ($basetype) {
			case 'Q':
				$labelaskemail=qa_lang_html('question/q_notify_email');
				$labelonly=qa_lang_html('question/q_notify_label');
				$labelgotemail=qa_lang_html('question/q_notify_x_label');
				break;
				
			case 'A':
				$labelaskemail=qa_lang_html('question/a_notify_email');
				$labelonly=qa_lang_html('question/a_notify_label');
				$labelgotemail=qa_lang_html('question/a_notify_x_label');
				break;
				
			case 'C':
				$labelaskemail=qa_lang_html('question/c_notify_email');
				$labelonly=qa_lang_html('question/c_notify_label');
				$labelgotemail=qa_lang_html('question/c_notify_x_label');
				break;
		}
			
		if (empty($login_email)) {
			$fields['notify']['label']=
				'<SPAN ID="email_shown">'.$labelaskemail.'</SPAN>'.
				'<SPAN ID="email_hidden" STYLE="display:none;">'.$labelonly.'</SPAN>';
			
			$fields['notify']['tags'].=' ID="notify" ';
			$fields['notify']['tight']=true;
			
			$fields['email']=array(
				'id' => 'email',
				'tags' => ' NAME="email" ',
				'value' => qa_html($inemail),
				'note' => qa_lang_html('question/notify_email_note'),
				'error' => qa_html($errors_email),
			);
			
			qa_checkbox_to_display($qa_content, array(
				'email' => 'notify',
				'email_shown' => 'notify',
				'email_hidden' => '!notify',
			));
		
		} else {
			$fields['notify']['label']=str_replace('^', qa_html($login_email), $labelgotemail);
		}
	}
	
	function qa_load_theme_class($theme, $template, $content)
	{
		global $qa_root_url_relative;
		
		require_once QA_INCLUDE_DIR.'qa-theme-base.php';
		
		$themephpfile=QA_THEME_DIR.$theme.'/qa-theme.php';
		$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/'.$theme.'/');
		
		if (file_exists($themephpfile)) {
			require_once QA_THEME_DIR.$theme.'/qa-theme.php';
	
			if (class_exists('qa_html_theme'))
				$themeclass=new qa_html_theme($template, $content, $themeroothtml);
		}
		
		if (!isset($themeclass)) {
			if (!file_exists(QA_THEME_DIR.$theme.'/qa-styles.css'))
				$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/Default/');
				
			$themeclass=new qa_html_theme_base($template, $content, $themeroothtml);
		}
		
		return $themeclass;
	}
	
	
?>