<?php
	
/*
	Question2Answer 1.3.3 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-post-create.php
	Version: 1.3.3
	Date: 2011-03-16 12:46:02 GMT
	Description: Database functions for creating a question, answer or comment


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_db_post_create($type, $parentid, $userid, $cookieid, $ip, $title, $content, $format, $tagstring, $notify, $categoryid=null)
/*
	Create a new post in the database and return its ID (based on auto-incrementing)
*/
	{
		qa_db_query_sub(
			'INSERT INTO ^posts (categoryid, type, parentid, userid, cookieid, createip, title, content, format, tags, notify, created) VALUES (#, $, #, $, #, INET_ATON($), $, $, $, $, $, NOW())',
			$categoryid, $type, $parentid, $userid, $cookieid, $ip, $title, $content, $format, $tagstring, $notify
		);
		
		return qa_db_last_insert_id();
	}

	
	function qa_db_post_acount_update($questionid)
/*
	Update the cached number of answers for question $questionid in the database
*/
	{
		qa_db_query_sub(
			'UPDATE ^posts AS x, (SELECT COUNT(*) AS acount FROM ^posts WHERE parentid=# AND type=\'A\') AS a SET x.acount=a.acount WHERE x.postid=#',
			$questionid, $questionid
		);
	}
	
	
	function qa_db_ifcategory_qcount_update($categoryid)
/*
	Update the cached number of questions for category $categoryid in the database
*/
	{
		if (isset($categoryid)) {
			qa_db_query_sub(
				'UPDATE ^categories SET qcount=(SELECT COUNT(*) FROM ^posts WHERE categoryid=# AND type=\'Q\') WHERE ^categories.categoryid=#',
				$categoryid, $categoryid
			);
		}
	}

	
	function qa_db_posttags_add_post_wordids($postid, $wordids)
/*
	Add rows into the database tags index, where $postid contains the words $wordids
*/
	{
		if (count($wordids))
			qa_db_query_sub(
				'INSERT INTO ^posttags (postid, wordid, postcreated) SELECT postid, wordid, created FROM ^words, ^posts WHERE postid=# AND wordid IN ($)',
				$postid, $wordids
			);
	}

	
	function qa_db_titlewords_add_post_wordids($postid, $wordids)
/*
	Add rows into the database title index, where $postid contains the words $wordids - this does the same sort
	of thing as qa_db_posttags_add_post_wordids() in a different way, for no particularly good reason.
*/
	{
		if (count($wordids)) {
			$rowstoadd=array();
			foreach ($wordids as $wordid)
				$rowstoadd[]=array($postid, $wordid);
			
			qa_db_query_sub(
				'INSERT INTO ^titlewords (postid, wordid) VALUES #',
				$rowstoadd
			);
		}
	}

	
	function qa_db_contentwords_add_post_wordidcounts($postid, $type, $questionid, $wordidcounts)
/*
	Add rows into the database content index, where $postid (of $type, with the antecedent $questionid)
	has words as per the keys of $wordidcounts, and the corresponding number of those words in the values.
*/
	{
		if (count($wordidcounts)) {
			$rowstoadd=array();
			foreach ($wordidcounts as $wordid => $count)
				$rowstoadd[]=array($postid, $wordid, $count, $type, $questionid);

			qa_db_query_sub(
				'INSERT INTO ^contentwords (postid, wordid, count, type, questionid) VALUES #',
				$rowstoadd
			);
		}
	}

	
	function qa_db_word_mapto_ids($words)
/*
	Return an array mapping each word in $words to its corresponding wordid in the database
*/
	{
		if (count($words))
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT wordid, BINARY word AS word FROM ^words WHERE word IN ($)', $words
			), 'word', 'wordid');
		else
			return array();
	}

	
	function qa_db_word_mapto_ids_add($words)
/*
	Return an array mapping each word in $words to its corresponding wordid in the database, adding any that are missing
*/
	{
		$wordtoid=qa_db_word_mapto_ids($words);
		
		$wordstoadd=array();
		foreach ($words as $word)
			if (!isset($wordtoid[$word]))
				$wordstoadd[]=$word;
		
		if (count($wordstoadd)) {
			qa_db_query_sub('LOCK TABLES ^words WRITE'); // to prevent two requests adding the same word
			
			$wordtoid=qa_db_word_mapto_ids($words); // map it again in case table content changed before it was locked
			
			$rowstoadd=array();
			foreach ($words as $word)
				if (!isset($wordtoid[$word]))
					$rowstoadd[]=array($word);
				
			qa_db_query_sub('INSERT INTO ^words (word) VALUES $', $rowstoadd);
			
			qa_db_query_sub('UNLOCK TABLES');
			
			$wordtoid=qa_db_word_mapto_ids($words); // do it one last time
		}
		
		return $wordtoid;
	}
	

	function qa_db_word_titlecount_update($wordids)
/*
	Update the titlecount column in the database for the words in $wordids, based on how many posts they appear in the title of
*/
	{
		if (count($wordids))
			qa_db_query_sub(
				'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^titlewords.wordid) AS titlecount FROM ^words LEFT JOIN ^titlewords ON ^titlewords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
				$wordids
			);
	}

	
	function qa_db_word_tagcount_update($wordids)
/*
	Update the tagcount column in the database for the words in $wordids, based on how many posts they appear in the tags of
*/
	{
		if (count($wordids))
			qa_db_query_sub(
				'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^posttags.wordid) AS tagcount FROM ^words LEFT JOIN ^posttags ON ^posttags.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
				$wordids
			);
	}

	
	function qa_db_word_contentcount_update($wordids)
/*
	Update the contentcount column in the database for the words in $wordids, based on how many posts they appear in the content of
*/
	{
		if (count($wordids))
			qa_db_query_sub(
				'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^contentwords.wordid) AS contentcount FROM ^words LEFT JOIN ^contentwords ON ^contentwords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
				$wordids
			);
	}

	
	function qa_db_qcount_update()
/*
	Updated the cached count in the database of the number of questions (excluding hidden)
*/
	{
		qa_db_query_sub("REPLACE ^options (title, content) SELECT 'cache_qcount', COUNT(*) FROM ^posts WHERE type='Q'");
	}


	function qa_db_acount_update()
/*
	Updated the cached count in the database of the number of answers (excluding hidden)
*/
	{
		qa_db_query_sub("REPLACE ^options (title, content) SELECT 'cache_acount', COUNT(*) FROM ^posts WHERE type='A'");
	}


	function qa_db_ccount_update()
/*
	Updated the cached count in the database of the number of comments (excluding hidden)
*/
	{
		qa_db_query_sub("REPLACE ^options (title, content) SELECT 'cache_ccount', COUNT(*) FROM ^posts WHERE type='C'");
	}


	function qa_db_tagcount_update()
/*
	Updated the cached count in the database of the number of different tags used
*/
	{
		qa_db_query_sub("REPLACE ^options (title, content) SELECT 'cache_tagcount', COUNT(*) FROM ^words WHERE tagcount>0");
	}

	
	function qa_db_unaqcount_update()
/*
	Updated the cached count in the database of the number of unanswered questions (excluding hidden)
*/
	{
		qa_db_query_sub("REPLACE ^options (title, content) SELECT 'cache_unaqcount', COUNT(*) FROM ^posts WHERE type='Q' AND acount=0");
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/