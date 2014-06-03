<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-format.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Common functions for creating theme-ready structures from data


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


	define('QA_PAGE_FLAGS_EXTERNAL', 1);
	define('QA_PAGE_FLAGS_NEW_WINDOW', 2);


	function qa_time_to_string($seconds)
/*
	Return textual representation of $seconds
*/
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
/*
	Check if $post is by user $userid, or if post is anonymous and $userid not specified, then
	check if $post is by anonymous user identified by $cookieid
*/
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
/*
	Return array which maps the ['userid'] and/or ['lastuserid'] in each element of
	$useridhandles to its HTML representation. For internal user management, corresponding
	['handle'] and/or ['lasthandle'] are required in each element.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		global $qa_root_url_relative;
			
		if (QA_EXTERNAL_USERS) {
			$keyuserids=array();
	
			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']))
					$keyuserids[$useridhandle['userid']]=true;

				if (isset($useridhandle['lastuserid']))
					$keyuserids[$useridhandle['lastuserid']]=true;
			}
	
			if (count($keyuserids))
				return qa_get_users_html($db, array_keys($keyuserids), true, $qa_root_url_relative, $microformats);
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
/*
	Convert textual $tag to HTML representation
*/
	{
		return '<A HREF="'.qa_path_html('tag/'.$tag).'"'.($microformats ? ' rel="tag"' : '').' CLASS="qa-tag-link">'.qa_html($tag).'</A>';
	}

	
	function qa_category_html($category)
/*
	Return HTML to use for $category (full row retrieved from database)
*/
	{
		return '<A HREF="'.qa_path_html($category['tags']).'" CLASS="qa-category-link">'.qa_html($category['title']).'</A>';
	}
	
	
	function qa_ip_anchor_html($ip, $anchorhtml=null)
/*
	Return HTML to use for $ip address, which links to appropriate page with $anchorhtml
*/
	{
		if (!strlen($anchorhtml))
			$anchorhtml=qa_html($ip);
		
		return '<A HREF="'.qa_path_html('ip/'.$ip).'" TITLE="'.qa_lang_html_sub('main/ip_address_x', qa_html($ip)).'" CLASS="qa-ip-link">'.$anchorhtml.'</A>';
	}

	
	function qa_post_html_fields($post, $userid, $cookieid, $usershtml, $tagsview=false, $categories=null, $voteview=false,
		$whenview=false, $ipview=false, $pointsview=false, $blockwordspreg=null, $showurllinks=false, $microformats=false, $isselected=false)
/*
	Given $post retrieved from database, return array of mostly HTML to be passed to theme layer.
	$userid and $cookieid refer to the user *viewing* the page.
	$usershtml is an array of [user id] => [HTML representation of user] built ahead of time.
	Remaining parameters determine what is shown - most are true/false, $voteview is 'updown'/'net'/false.
	If something is missing from $post (e.g. ['content']), correponding HTML also omitted.
*/
	{
		if (isset($blockwordspreg))
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$fields=array();
		
	//	Useful stuff used throughout function

		$postid=$post['postid'];
		$isquestion=($post['basetype']=='Q');
		$isanswer=($post['basetype']=='A');
		$isbyuser=qa_post_is_by_user($post, $userid, $cookieid);
		$anchor=urlencode(qa_anchor($post['basetype'], $postid));
		
	//	High level information

		$fields['hidden']=$post['hidden'];
		$fields['tags']=' ID="'.$anchor.'" ';
		
		if ($microformats)
			$fields['classes']=' hentry '.($isquestion ? 'question' : ($isanswer ? ($isselected ? 'answer answer-selected' : 'answer') : 'comment'));
	
	//	Question-specific stuff (title, URL, tags, answer count, category)
	
		if ($isquestion) {
			if (isset($post['title'])) {
				if (isset($blockwordspreg))
					$post['title']=qa_block_words_replace($post['title'], $blockwordspreg);
				
				$fields['title']=qa_html($post['title']);
				if ($microformats)
					$fields['title']='<SPAN CLASS="entry-title">'.$fields['title'].'</SPAN>';
					
				$fields['url']=qa_path_html(qa_q_request($postid, $post['title']));
				
				/*if (isset($post['score'])) // useful for setting match thresholds
					$fields['title'].=' <SMALL>('.$post['score'].')</SMALL>';*/
			}
				
			if ($tagsview && isset($post['tags'])) {
				$fields['q_tags']=array();
				
				$tags=qa_tagstring_to_tags($post['tags']);
				foreach ($tags as $tag) {
					if (isset($blockwordspreg) && count(qa_block_words_match_all($tag, $blockwordspreg))) // skip censored tags
						continue;
						
					$fields['q_tags'][]=qa_tag_html($tag, $microformats);
				}
			}
		
			if (isset($post['acount']))
				$fields['answers']=($post['acount']==1) ? qa_lang_html_sub_split('main/1_answer', '1', '1')
					: qa_lang_html_sub_split('main/x_answers', number_format($post['acount']));

			if (isset($post['categoryid'])) {
				$category=@$categories[$post['categoryid']];
				if (isset($category))
					$fields['where']=qa_lang_html_sub_split('main/in_category_x', qa_category_html($category));
			}
		}
		
	//	Answer-specific stuff (selection)
		
		if ($isanswer) {
			$fields['selected']=$isselected;
			
			if ($isselected)
				$fields['select_text']=qa_lang_html('question/select_text');
		}

	//	Post content
		
		if (!empty($post['content'])) {
			if (isset($blockwordspreg))
				$post['content']=qa_block_words_replace($post['content'], $blockwordspreg);
			
			$fields['content']=qa_html($post['content'], true); // also used for rendering content when asking follow-on q
			
			if ($showurllinks)
				$fields['content']=qa_html_convert_urls($fields['content']);
			
			if ($microformats)
				$fields['content']='<SPAN CLASS="entry-content">'.$fields['content'].'</SPAN>';
			
			$fields['content']='<A NAME="'.qa_html($postid).'"></A>'.$fields['content'];
				// this is for backwards compatibility with any existing links using the old style of anchor
				// that contained the post id only (changed to be valid under W3C specifications)
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
			
			$fields['upvotes_view']=($upvotes==1) ? qa_lang_html_sub_split('main/1_liked', $upvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_liked', $upvoteshtml);
	
			$fields['downvotes_view']=($downvotes==1) ? qa_lang_html_sub_split('main/1_disliked', $downvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_disliked', $downvoteshtml);
			
			$fields['netvotes_view']=(abs($netvotes)==1) ? qa_lang_html_sub_split('main/1_vote', $netvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_votes', $netvoteshtml);
		
		//	Voting buttons
		
			$fields['vote_tags']=' ID="voting_'.qa_html($postid).'" ';
			$onclick='onClick="return qa_vote_click(this);" ';
			
			if ($fields['hidden']) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html($isanswer ? 'main/vote_disabled_hidden_a' : 'main/vote_disabled_hidden_q').'" ';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
			
			} elseif ($isbyuser) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html($isanswer ? 'main/vote_disabled_my_a' : 'main/vote_disabled_my_q').'" ';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
				
			} elseif (@$post['uservote']>0) {
				$fields['vote_state']='voted_up';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html('main/voted_up_popup').'" NAME="'.qa_html('vote_'.$postid.'_0_'.$anchor).'" '.$onclick;
				$fields['vote_down_tags']=' ';

			} elseif (@$post['uservote']<0) {
				$fields['vote_state']='voted_down';
				$fields['vote_up_tags']=' ';
				$fields['vote_down_tags']=' TITLE="'.qa_lang_html('main/voted_down_popup').'" NAME="'.qa_html('vote_'.$postid.'_0_'.$anchor).'" '.$onclick;
				
			} else {
				$fields['vote_state']='enabled';
				$fields['vote_up_tags']=' TITLE="'.qa_lang_html('main/vote_up_popup').'" NAME="'.qa_html('vote_'.$postid.'_1_'.$anchor).'" '.$onclick;
				$fields['vote_down_tags']=' TITLE="'.qa_lang_html('main/vote_down_popup').'" NAME="'.qa_html('vote_'.$postid.'_-1_'.$anchor).'" '.$onclick;
			}
		}
		
	//	Created when and by whom
		
		$fields['meta_order']=qa_lang_html('main/meta_order'); // sets ordering of meta elements which can be language-specific
		
		if ($isquestion || $isanswer)
			$fields['what']=qa_lang_html($isquestion ? 'main/asked' : 'main/answered');
		
		if (isset($post['created']) && $whenview) {
			$whenhtml=qa_html(qa_time_to_string(time()-$post['created']));
			if ($microformats)
				$whenhtml='<SPAN CLASS="published"><SPAN CLASS="value-title" TITLE="'.gmdate('Y-m-d\TH:i:sO', $post['created']).'"></SPAN>'.$whenhtml.'</SPAN>';
			
			$fields['when']=qa_lang_html_sub_split('main/x_ago', $whenhtml);
		}
		
		$fields['who']=qa_who_to_html($isbyuser, @$post['userid'], $usershtml, $ipview ? $post['createip'] : null, $microformats);
		
		if ($pointsview && isset($post['points']))
			$fields['points']=($post['points']==1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
				: qa_lang_html_sub_split('main/x_points', qa_html(number_format($post['points'])));

	//	Updated when and by whom
		
		if (isset($post['updated']) && ( // show the time/user who updated if...
			(!isset($post['created'])) || // ... we didn't show the created time (should never happen in practice)
			($post['hidden']) || // ... the post was actually hidden
			(abs($post['updated']-$post['created'])>300) || // ... or over 5 minutes passed between create and update times
			($post['lastuserid']!=$post['userid']) // ... or it was updated by a different user
		)) {
			if ($whenview) {
				$whenhtml=qa_html(qa_time_to_string(time()-$post['updated']));
				if ($microformats)
					$whenhtml='<SPAN CLASS="updated"><SPAN CLASS="value-title" TITLE="'.gmdate('Y-m-d\TH:i:sO', $post['updated']).'"></SPAN>'.$whenhtml.'</SPAN>';
				
				$fields['when_2']=qa_lang_html_sub_split($fields['hidden'] ? 'question/hidden_x_ago' : 'question/edited_x_ago', $whenhtml);
			
			} else
				$fields['when_2']['prefix']=qa_lang_html($fields['hidden'] ? 'question/hidden' : 'question/edited');
				
			$fields['who_2']=qa_who_to_html($post['lastuserid']==$userid, $post['lastuserid'], $usershtml, null, false);
		}
		
	//	That's it!

		return $fields;
	}
	

	function qa_who_to_html($isbyuser, $postuserid, $usershtml, $ip, $microformats)
/*
	Return array of split HTML (prefix, data, suffix) to represent author of post
*/
	{
		if ($isbyuser)
			$whohtml=qa_lang_html('main/me');

		elseif (isset($postuserid) && isset($usershtml[$postuserid])) {
			$whohtml=$usershtml[$postuserid];
			if ($microformats)
				$whohtml='<SPAN CLASS="vcard author">'.$whohtml.'</SPAN>';

		} else {
			$whohtml=qa_lang_html('main/anonymous');
			
			if (isset($ip))
				$whohtml=qa_ip_anchor_html($ip, $whohtml);
		}
			
		return qa_lang_html_sub_split('main/by_x', $whohtml);
	}
	

	function qa_a_or_c_to_q_html_fields($question, $userid, $cookieid, $usershtml, $tagsview=false, $categories=null, $voteview=false, $whenview=false, $ipview=false, $pointsview=false, $blockwordspreg=null, $basetype, $acpostid, $accreated, $acuserid, $accookieid, $accreateip, $acpoints)
/*
	Return array of mostly HTML to be passed to theme layer, to *link* to an answer or comment
	on $question retrieved from database. $basetype is 'A' for answer or 'C' for comment.
	$userid, $cookieid, $usershtml, $voteview, $pointsview are passed through to qa_post_html_fields().
	$acpostid, $accreated, $acuserid, $accookieid, $acpoints relate to the answer or comment and its author.
*/
	{
		$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, $tagsview, $categories, $voteview, $whenview, $ipview, $pointsview, $blockwordspreg);
		
		if ( ($basetype=='C') || ($basetype=='A') )
			$fields['what']=qa_lang_html(($basetype=='A') ? 'main/answered' : 'main/commented');
			
		$fields['what_url']=$fields['url'].'#'.qa_html(urlencode(qa_anchor($basetype, $acpostid)));

		if ($whenview)
			$fields['when']=qa_lang_html_sub_split('main/x_ago', qa_html(qa_time_to_string(time()-$accreated)));
		
		$isbyuser=qa_post_is_by_user(array('userid' => $acuserid, 'cookieid' => $accookieid), $userid, $cookieid);
		
		$fields['who']=qa_who_to_html($isbyuser, $acuserid, $usershtml, $ipview ? $accreateip : null, false);

		if ($pointsview && isset($acpoints))
			$fields['points']=($acpoints==1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
				: qa_lang_html_sub_split('main/x_points', qa_html(number_format($acpoints)));
		else
			unset($fields['points']);
		
		return $fields;
	}

	
	function qa_any_to_q_html_fields($question, $userid, $cookieid, $usershtml, $tagsview=false, $categories=nul, $voteview=false, $whenview=false, $ipview=false, $pointsview=false, $blockwordspreg=null)
/*
	Based on the elements in $question, return HTML to be passed to theme layer to link
	to the question, or to an answer or comment thereon.
*/
	{
		if (isset($question['cpostid']))
			$fields=qa_a_or_c_to_q_html_fields($question, $userid, $cookieid, $usershtml, $tagsview, $categories, $voteview, $whenview, $ipview, $pointsview, $blockwordspreg, 'C',
				$question['cpostid'], @$question['ccreated'], @$question['cuserid'], @$question['ccookieid'], @$question['ccreateip'], @$question['cpoints']);

		elseif (isset($question['apostid']))
			$fields=qa_a_or_c_to_q_html_fields($question, $userid, $cookieid, $usershtml, $tagsview, $categories, $voteview, $whenview, $ipview, $pointsview, $blockwordspreg, 'A',
				$question['apostid'], @$question['acreated'], @$question['auserid'], @$question['acookieid'], @$question['acreateip'], @$question['apoints']);

		else
			$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, $tagsview, $categories, $voteview, $whenview, $ipview, $pointsview, $blockwordspreg);

		return $fields;
	}
	

	function qa_any_sort_and_dedupe($questions)
/*
	Each element in $questions represents a question or an answer or comment thereon, as retrieved from database.
	Return it sorted by the date appropriate for each element, and keep only the first item related to each question.
*/
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
/*
	Each element in $questions represents a question or an answer or comment thereon, as retrieved from database.
	Return an array of elements (userid,handle), with the appropriate author for each element.
*/
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
/*
	Return $html with any URLs converted into links (with nofollow)
	URL regular expressions can get crazy: http://internet.ls-la.net/folklore/url-regexpr.html
	So this is something quick and dirty that should do the trick in most cases
*/
	{
		return trim(preg_replace('/([^A-Za-z0-9])((http|https|ftp):\/\/\S+\.[^\s<>]+)/i', '\1<A HREF="\2" rel="nofollow">\2</A>', ' '.$html.' '));
	}

	
	function qa_url_to_html_link($url)
/*
	Return HTML representation of $url, linked with nofollow if we could see an URL in there
*/
	{
		if (is_numeric(strpos($url, '.'))) {
			$linkurl=$url;
			if (!is_numeric(strpos($linkurl, ':/')))
				$linkurl='http://'.$linkurl;
				
			return '<A HREF="'.qa_html($linkurl).'" rel="nofollow">'.qa_html($url).'</A>';
		
		} else
			return qa_html($url);
	}

	
	function qa_insert_login_links($htmlmessage, $topage=null, $params=null)
/*
	Return $htmlmessage with ^1...^4 substituted for links to log in or register and come back to $topage
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		global $qa_root_url_relative;
		
		$userlinks=qa_get_login_links($qa_root_url_relative, isset($topage) ? qa_path($topage, $params, '') : null);
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => empty($userlinks['login']) ? '' : '<A HREF="'.qa_html($userlinks['login']).'">',
				'^2' => empty($userlinks['login']) ? '' : '</A>',
				'^3' => empty($userlinks['register']) ? '' : '<A HREF="'.qa_html($userlinks['register']).'">',
				'^4' => empty($userlinks['register']) ? '' : '</A>',
				'^5' => empty($userlinks['confirm']) ? '' : '<A HREF="'.qa_html($userlinks['confirm']).'">',
				'^6' => empty($userlinks['confirm']) ? '' : '</A>',
			)
		);
	}

	
	function qa_html_page_links($request, $start, $pagesize, $count, $prevnext, $params=array(), $hasmore=false)
/*
	Return structure to pass through to theme layer to show linked page numbers for $request.
	QA uses offset-based paging, i.e. pages are referenced in the URL by a 'start' parameter.
	$start is current offset, there are $pagesize items per page and $count items in total
	(unless $hasmore is true in which case there are at least $count items).
	Show links to $prevnext pages before and after this one and include $params in the URLs.
*/
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
					$links['items'][$key]['url']=qa_path_html($request, $params);
				}
				
		} else
			$links=null;
		
		return $links;
	}

	
	function qa_html_suggest_qs_tags($usingtags=false, $categoryslug=null)
/*
	Return HTML that suggests browsing all questions or popular tags, as appropriate
*/
	{
		$htmlmessage=strlen($categoryslug) ? qa_lang_html('main/suggest_category_qs') :
			($usingtags ? qa_lang_html('main/suggest_qs_tags') : qa_lang_html('main/suggest_qs'));
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<A HREF="'.qa_path_html('questions'.(strlen($categoryslug) ? ('/'.$categoryslug) : '')).'">',
				'^2' => '</A>',
				'^3' => '<A HREF="'.qa_path_html('tags').'">',
				'^4' => '</A>',
			)
		);
	}

	
	function qa_html_suggest_ask($categoryid=null)
/*
	Return HTML that suggest getting things started by asking a question.
*/
	{
		$htmlmessage=qa_lang_html('main/suggest_ask');
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<A HREF="'.qa_path_html('ask', strlen($categoryid) ? array('cat' => $categoryid) : null).'">',
				'^2' => '</A>',
			)
		);
	}
	
	
	function qa_category_navigation($categories, $categoryid=null, $pathprefix='', $showqcount=true)
/*
	Return the navigation structure for the category menu, with $categoryid selected,
	and links beginning with $pathprefix, and showing question counts if $showqcount
*/
	{
		$navigation=array(
			'all' => array(
				'url' => qa_path_html($pathprefix),
				'label' => qa_lang_html('main/all_categories'),
				'selected' => !isset($categoryid),
			),
		);
		
		foreach ($categories as $category)
			$navigation[$category['tags']]=array(
				'url' => qa_path_html((strlen($pathprefix) ? ($pathprefix.'/') : '').$category['tags']),
				'label' => qa_html($category['title']),
				'selected' => ($categoryid == $category['categoryid']),
				'note' => $showqcount ? qa_html(number_format($category['qcount'])) : null,
			);
		
		return $navigation;
	}
	
	
	function qa_users_sub_navigation($db)
/*
	Return the sub navigation structure for user pages
*/
	{
		global $qa_login_userid;
		
		if ((!QA_EXTERNAL_USERS) && isset($qa_login_userid) && (qa_get_logged_in_level($db)>=QA_USER_LEVEL_MODERATOR)) {
			return array(
				'users$' => array(
					'url' => qa_path_html('users'),
					'label' => qa_lang_html('main/highest_users'),
				),
	
				'users/special' => array(
					'label' => qa_lang('users/special_users'),
					'url' => qa_path_html('users/special'),
				),
	
				'users/blocked' => array(
					'label' => qa_lang('users/blocked_users'),
					'url' => qa_path_html('users/blocked'),
				),
			);
			
		} else
			return null;
	}
	
	
	function qa_navigation_add_page(&$navigation, $page)
/*
	Add an element to the $navigation array corresponding to $page retrieved from the database
*/
	{
		global $qa_root_url_relative;
		
		$navigation[($page['flags'] & QA_PAGE_FLAGS_EXTERNAL) ? ('custom-'.$page['pageid']) : $page['tags']]=array(
			'url' => ($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)
				? qa_html(is_numeric(strpos($page['tags'], '://')) ? $page['tags'] : $qa_root_url_relative.$page['tags'])
				: qa_path_html($page['tags']),
			'label' => qa_html($page['title']),
			'opposite' => ($page['nav']=='O'),
			'target' => ($page['flags'] & QA_PAGE_FLAGS_NEW_WINDOW) ? '_blank' : null,
		);
	}


	function qa_match_to_min_score($match)
/*
	Convert an admin option for matching into a threshold for the score given by database search
*/
	{
		return 10-2*$match;
	}

	
	function qa_checkbox_to_display(&$qa_content, $effects)
/*
	For each [target] => [source] in $effects, set up $qa_content so that the visibility of
	the DOM element ID target is equal to the checked state of the DOM element ID source.
	Each source can also combine multiple DOM IDs using JavaScript(=PHP) Boolean operators.
	This is pretty twisted, but also rather convenient.
*/
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
			$loadscript[]="if (e) {";
			$loadscript[]="\t".$key."_oldonclick=e.onclick;";
			$loadscript[]="\te.onclick=function() {";
			$loadscript[]="\t\t".$function."();";
			$loadscript[]="\t\tif (typeof ".$key."_oldonclick=='function')";
			$loadscript[]="\t\t\t".$key."_oldonclick();";
			$loadscript[]="\t}";
			$loadscript[]="}";
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
/*
	Set up $qa_content and $field (with HTML name $fieldname) for tag auto-completion, where
	$exampletags are suggestions and $completetags are simply the most popular ones. Show up to $maxtags.
*/
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
/*
	Set up $qa_content and add to $fields to allow user to set if they want to be notified regarding their post.
	$basetype is 'Q', 'A' or 'C' for question, answer or comment. $login_email is the email of logged in user,
	or null if this is an anonymous post. $innotify, $inemail and $errors_email are from previous submission/validation.
*/
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
			
			$fields['notify']['tags'].=' ID="notify" onclick="if (document.getElementById(\'notify\').checked) document.getElementById(\'email\').focus();" ';
			$fields['notify']['tight']=true;
			
			$fields['email']=array(
				'id' => 'email_display',
				'tags' => ' NAME="email" ID="email" ',
				'value' => qa_html($inemail),
				'note' => qa_lang_html('question/notify_email_note'),
				'error' => qa_html($errors_email),
			);
			
			qa_checkbox_to_display($qa_content, array(
				'email_display' => 'notify',
				'email_shown' => 'notify',
				'email_hidden' => '!notify',
			));
		
		} else {
			$fields['notify']['label']=str_replace('^', qa_html($login_email), $labelgotemail);
		}
	}

	
	function qa_load_theme_class($theme, $template, $content, $request)
/*
	Return the initialized class for $theme (or the default if it's gone), passing $template, $content and $request
*/
	{
		global $qa_root_url_relative;
		
		require_once QA_INCLUDE_DIR.'qa-theme-base.php';
		
		$themephpfile=QA_THEME_DIR.$theme.'/qa-theme.php';
		$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/'.$theme.'/');
		
		if (file_exists($themephpfile)) {
			require_once QA_THEME_DIR.$theme.'/qa-theme.php';
	
			if (class_exists('qa_html_theme'))
				$themeclass=new qa_html_theme($template, $content, $themeroothtml, $request);
		}
		
		if (!isset($themeclass)) {
			if (!file_exists(QA_THEME_DIR.$theme.'/qa-styles.css'))
				$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/Default/');
				
			$themeclass=new qa_html_theme_base($template, $content, $themeroothtml, $request);
		}
		
		return $themeclass;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/