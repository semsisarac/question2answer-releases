<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-feed.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Handles all requests to RSS feeds, first checking if they should be available


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


	@ini_set('display_errors', 0); // we don't want to show PHP errors to RSS readers


	require_once QA_INCLUDE_DIR.'qa-app-options.php';


//	Functions used within this file

	function qa_feed_db_fail_handler($type, $errno=null, $error=null, $query=null)
/*
	Database failure handler function for RSS feeds - outputs HTTP and text errors
*/

	{
		header('HTTP/1.1 500 Internal Server Error');
		echo qa_lang_html('main/general_error');
		exit;
	}

	
	function qa_feed_not_found()
/*
	Common function called when a non-existent feed is requested - outputs HTTP and text errors
*/
	{
		header('HTTP/1.0 404 Not Found');
		echo qa_lang_html('misc/feed_not_found');
		exit;
	}

	
	function qa_feed_load_ifcategory($allkey, $catkey, $questionselectspec1=null, $questionselectspec2=null, $questionselectspec3=null)
/*
	Common function to load appropriate set of questions for requested feed, check category exists, and set up page title
*/
	{
		global $qa_db, $categoryslug, $categories, $categoryid, $title, $questions;
		
		@list($questions1, $questions2, $questions3, $categories, $categoryid)=qa_db_select_with_pending($qa_db,
			$questionselectspec1,
			$questionselectspec2,
			$questionselectspec3,
			qa_using_categories($qa_db) ? qa_db_categories_selectspec() : null,
			isset($categoryslug) ? qa_db_slug_to_category_id_selectspec($categoryslug) : null
		);

		if (isset($categoryslug) && !isset($categoryid))
			qa_feed_not_found();

		$questions=array_merge(
			is_array($questions1) ? $questions1 : array(),
			is_array($questions2) ? $questions2 : array(),
			is_array($questions3) ? $questions3 : array()
		);

		if (isset($allkey) && isset($catkey))
			$title=isset($categoryid) ? qa_lang_sub($catkey, $categories[$categoryid]['title']) : qa_lang($allkey);
	}


//	Connect to database and get the type of feed and category requested (in some cases these are overridden later)

	qa_base_db_connect('qa_feed_db_fail_handler');

	$feedtype=@$qa_request_lc_parts[1];
	$feedparam=@$qa_request_lc_parts[2];
	
	if ((substr($feedtype, -4)=='.rss') || (substr($feedtype, -4)=='.xml')) // remove suffixes which are optional
		$feedtype=substr($feedtype, 0, -4);

	if ((substr($feedparam, -4)=='.rss') || (substr($feedparam, -4)=='.xml'))
		$feedparam=substr($feedparam, 0, -4);


//	Choose which option needs to be checked to determine if this feed can be requested, and stop if no matches

	$feedoption=null;
	$categoryslug=$feedparam;
	
	switch ($feedtype) {
		case 'questions':
			$feedoption='feed_for_questions';
			break;
			
		case 'unanswered':
			$feedoption='feed_for_unanswered';
			break;
			
		case 'answers':
		case 'comments':
		case 'activity':
			$feedoption='feed_for_activity';
			break;
			
		case 'qa':
			$feedoption='feed_for_qa';
			break;
		
		case 'tag':
			if (strlen($feedparam)) {
				$feedoption='feed_for_tag_qs';
				$categoryslug=null;
			}
			break;
			
		case 'search':
			if (strlen($feedparam)) {
				$feedoption='feed_for_search';
				$categoryslug=null;
			}
			break;
	}
	
	if (!isset($feedoption))
		qa_feed_not_found();
	

//	Queue the chosen option and some others that will be needed
	
	qa_options_set_pending(array($feedoption, 'feed_per_category', 'feed_number_items', 'feed_full_text', 'show_url_links',
		'tags_or_categories', 'site_url', 'site_title', 'site_language', 'neat_urls', 'block_bad_words'));
	

//	Check that all the appropriate options are in place to allow this feed to be retrieved

	if (!(
		(qa_get_option($qa_db, $feedoption)) &&
		(isset($categoryslug) ? (qa_using_categories($qa_db) && qa_get_option($qa_db, 'feed_per_category')) : true)
	))
		qa_feed_not_found();


//	Retrieve the appropriate questions and other information for this feed

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';

	$sitetitle=qa_get_option($qa_db, 'site_title');
	$siteurl=qa_get_option($qa_db, 'site_url');
	$full=qa_get_option($qa_db, 'feed_full_text');
	$count=qa_get_option($qa_db, 'feed_number_items');
	$showurllinks=qa_get_option($qa_db, 'show_url_links');
	
	$linkrequest=$feedtype.(isset($categoryslug) ? ('/'.$categoryslug) : '');
	$linkparams=null;

	switch ($feedtype) {
		case 'questions':
			qa_feed_load_ifcategory('main/recent_qs_title', 'main/recent_qs_in_x',
				qa_db_recent_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count)
			);
			break;
			
		case 'unanswered':
			qa_feed_load_ifcategory('main/unanswered_qs_title', 'main/unanswered_qs_in_x',
				qa_db_unanswered_qs_selectspec(null, 0, $categoryslug, false, $full, $count)
			);
			break;
			
		case 'answers':
			qa_feed_load_ifcategory('main/recent_as_title', 'main/recent_as_in_x',
				qa_db_recent_a_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count)
			);
			break;

		case 'comments':
			qa_feed_load_ifcategory('main/recent_cs_title', 'main/recent_cs_in_x',
				qa_db_recent_c_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count)
			);
			break;
			
		case 'qa':
			qa_feed_load_ifcategory('main/recent_qs_as_title', 'main/recent_qs_as_in_x',
				qa_db_recent_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count),
				qa_db_recent_a_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count)
			);
			break;
		
		case 'activity':
			qa_feed_load_ifcategory('main/recent_activity_title', 'main/recent_activity_in_x',
				qa_db_recent_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count),
				qa_db_recent_a_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count),
				qa_db_recent_c_qs_selectspec(null, 0, $categoryslug, null, false, $full, $count)
			);
			break;
			
		case 'tag':
			$tag=$feedparam;

			qa_feed_load_ifcategory(null, null,
				qa_db_tag_recent_qs_selectspec(null, $tag, 0, $full, $count)
			);
			
			$title=qa_lang_sub('main/questions_tagged_x', $tag);
			$linkrequest='tag/'.$tag;
			break;
			
		case 'search':
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$query=$feedparam;
			$categoryslug=null;

			$words=qa_string_to_words($query);

			qa_feed_load_ifcategory(null, null,
				qa_db_search_posts_selectspec($qa_db, null, $words, $words, $words, $words, 0, $full, $count)
			);
		
			$title=qa_lang_sub('main/results_for_x', $query);
			$linkrequest='search';
			$linkparams=array('q' => $query);
			break;
	}

	qa_base_db_disconnect(); // disconnect as quickly as possible to free up resources
	

//	Remove duplicate questions (perhaps referenced in an answer and a comment) and cut down to size
	
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';

	if ($feedtype!='search') // leave search results sorted by relevance
		$questions=qa_any_sort_and_dedupe($questions);
	
	$questions=array_slice($questions, 0, $count);
	$blockwordspreg=qa_get_block_words_preg($qa_db);


//	Prepare the XML output

	$lines=array();

	$lines[]='<?xml version="1.0" encoding="UTF-8"?>';
	$lines[]='<rss version="2.0">';
	$lines[]='<channel>';

	$lines[]='<title>'.qa_html($sitetitle.' - '.$title).'</title>';
	$lines[]='<link>'.qa_path_html($linkrequest, $linkparams, $siteurl).'</link>';
	$lines[]='<description>Powered by Question2Answer</description>';
	
	foreach ($questions as $question) {

	//	Determine whether this is a question, answer or comment, and act accordingly
	
		if (isset($question['cpostid'])) {
			$anchor=qa_anchor('C', $question['cpostid']);
			$titleprefix=qa_lang('misc/feed_c_prefix');
			$created=$question['ccreated'];

			if ($full)
				$content=$question['ccontent'];
			
		} elseif (isset($question['apostid'])) {
			$anchor=qa_anchor('A', $question['apostid']);
			$titleprefix=qa_lang('misc/feed_a_prefix');
			$created=$question['acreated'];

			if ($full)
				$content=$question['acontent'];

		} else {
			$anchor=null;
			$titleprefix='';
			$created=$question['created'];

			if ($full)
				$content=$question['content'];
		}
		
		if (isset($blockwordspreg)) {
			$question['title']=qa_block_words_replace($question['title'], $blockwordspreg);
			$content=qa_block_words_replace($content, $blockwordspreg);
		}
		
		$urlhtml=qa_path_html(qa_q_request($question['postid'], $question['title']), null, $siteurl, null, $anchor);
		
	//	Build the inner XML structure for each item
		
		$lines[]='<item>';
		$lines[]='<title>'.qa_html($titleprefix.$question['title']).'</title>';
		$lines[]='<link>'.$urlhtml.'</link>';

		if ($full && isset($content)) {
			$htmlcontent=qa_html($content, true);
			
			if ($showurllinks)
				$htmlcontent=qa_html_convert_urls($htmlcontent);
				
			$lines[]='<description>'.qa_html($htmlcontent).'</description>'; // qa_html() a second time to put HTML code inside XML wrapper
		}
			
		if (isset($question['categoryid']) && isset($categories[$question['categoryid']]))
			$lines[]='<category>'.qa_html($categories[$question['categoryid']]['title']).'</category>';
			
		$lines[]='<guid isPermaLink="true">'.$urlhtml.'</guid>';
		$lines[]='<pubDate>'.qa_html(gmdate('r', $created)).'</pubDate>';
		$lines[]='</item>';
	}
	
	$lines[]='</channel>';
	$lines[]='</rss>';

//	Output the XML - and we're done!
	
	header('Content-type: text/xml; charset=utf-8');
	echo implode("\n", $lines);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/