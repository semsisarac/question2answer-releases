<?php
	
/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-post-create.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Database functions for creating a question, answer or comment


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


	function qa_db_post_create($db, $type, $parentid, $userid, $cookieid, $ip, $title, $content, $tagstring, $notify, $categoryid=null)
/*
	Create a new post in the database and return its ID (based on auto-incrementing)
*/
	{
		qa_db_query_sub($db,
			'INSERT INTO ^posts (categoryid, type, parentid, userid, cookieid, createip, title, content, tags, notify, created) VALUES (#, $, #, $, #, INET_ATON($), $, $, $, $, NOW())',
			$categoryid, $type, $parentid, $userid, $cookieid, $ip, $title, $content, $tagstring, $notify
		);
		
		return qa_db_last_insert_id($db);
	}

	
	function qa_db_post_acount_update($db, $questionid)
/*
	Update the cached number of answers for question $questionid in the database
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT COUNT(*) AS acount FROM ^posts WHERE parentid=# AND type=\'A\') AS a SET x.acount=a.acount WHERE x.postid=#',
			$questionid, $questionid
		);
	}
	
	
	function qa_db_ifcategory_qcount_update($db, $categoryid)
/*
	Update the cached number of questions for category $categoryid in the database
*/
	{
		if (isset($categoryid)) {
			qa_db_query_sub($db,
				'UPDATE ^categories SET qcount=(SELECT COUNT(*) FROM ^posts WHERE categoryid=# AND type=\'Q\') WHERE ^categories.categoryid=#',
				$categoryid, $categoryid
			);
		}
	}

	
	function qa_db_posttags_add_post_wordids($db, $postid, $wordids)
/*
	Add rows into the database tags index, where $postid contains the words $wordids
*/
	{
		if (count($wordids))
			qa_db_query_sub($db,
				'INSERT INTO ^posttags (postid, wordid, postcreated) SELECT postid, wordid, created FROM ^words, ^posts WHERE postid=# AND wordid IN ($)',
				$postid, $wordids
			);
	}

	
	function qa_db_titlewords_add_post_wordids($db, $postid, $wordids)
/*
	Add rows into the database title index, where $postid contains the words $wordids - this does the same sort
	of thing as qa_db_posttags_add_post_wordids() in a different way, for no particularly good reason.
*/
	{
		if (count($wordids)) {
			$rowstoadd=array();
			foreach ($wordids as $wordid)
				$rowstoadd[]=array($postid, $wordid);
			
			qa_db_query_sub($db,
				'INSERT INTO ^titlewords (postid, wordid) VALUES #',
				$rowstoadd
			);
		}
	}

	
	function qa_db_contentwords_add_post_wordidcounts($db, $postid, $type, $questionid, $wordidcounts)
/*
	Add rows into the database content index, where $postid (of $type, with the antecedent $questionid)
	has words as per the keys of $wordidcounts, and the corresponding number of those words in the values.
*/
	{
		if (count($wordidcounts)) {
			$rowstoadd=array();
			foreach ($wordidcounts as $wordid => $count)
				$rowstoadd[]=array($postid, $wordid, $count, $type, $questionid);

			qa_db_query_sub($db,
				'INSERT INTO ^contentwords (postid, wordid, count, type, questionid) VALUES #',
				$rowstoadd
			);
		}
	}

	
	function qa_db_word_mapto_ids($db, $words)
/*
	Return an array mapping each word in $words to its corresponding wordid in the database
*/
	{
		if (count($words))
			return qa_db_read_all_assoc(qa_db_query_sub($db,
				'SELECT wordid, BINARY word AS word FROM ^words WHERE word IN ($)', $words
			), 'word', 'wordid');
		else
			return array();
	}

	
	function qa_db_word_mapto_ids_add($db, $words)
/*
	Return an array mapping each word in $words to its corresponding wordid in the database, adding any that are missing
*/
	{
		$wordtoid=qa_db_word_mapto_ids($db, $words);
		
		$wordstoadd=array();
		foreach ($words as $word)
			if (!isset($wordtoid[$word]))
				$wordstoadd[]=$word;
		
		if (count($wordstoadd)) {
			qa_db_query_sub($db, 'LOCK TABLES ^words WRITE'); // to prevent two requests adding the same word
			
			$wordtoid=qa_db_word_mapto_ids($db, $words); // map it again in case table content changed before it was locked
			
			$rowstoadd=array();
			foreach ($words as $word)
				if (!isset($wordtoid[$word]))
					$rowstoadd[]=array($word);
				
			qa_db_query_sub($db, 'INSERT INTO ^words (word) VALUES $', $rowstoadd);
			
			qa_db_query_sub($db, 'UNLOCK TABLES');
			
			$wordtoid=qa_db_word_mapto_ids($db, $words); // do it one last time
		}
		
		return $wordtoid;
	}
	

	function qa_db_word_titlecount_update($db, $wordids)
/*
	Update the titlecount column in the database for the words in $wordids, based on how many posts they appear in the title of
*/
	{
		if (count($wordids))
			qa_db_query_sub($db,
				'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^titlewords.wordid) AS titlecount FROM ^words LEFT JOIN ^titlewords ON ^titlewords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
				$wordids
			);
	}

	
	function qa_db_word_tagcount_update($db, $wordids)
/*
	Update the tagcount column in the database for the words in $wordids, based on how many posts they appear in the tags of
*/
	{
		if (count($wordids))
			qa_db_query_sub($db,
				'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^posttags.wordid) AS tagcount FROM ^words LEFT JOIN ^posttags ON ^posttags.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
				$wordids
			);
	}

	
	function qa_db_word_contentcount_update($db, $wordids)
/*
	Update the contentcount column in the database for the words in $wordids, based on how many posts they appear in the content of
*/
	{
		if (count($wordids))
			qa_db_query_sub($db,
				'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^contentwords.wordid) AS contentcount FROM ^words LEFT JOIN ^contentwords ON ^contentwords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
				$wordids
			);
	}

	
	function qa_db_qcount_update($db)
/*
	Updated the cached count in the database of the number of questions (excluding hidden)
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_qcount', COUNT(*) FROM ^posts WHERE type='Q'");
	}


	function qa_db_acount_update($db)
/*
	Updated the cached count in the database of the number of answers (excluding hidden)
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_acount', COUNT(*) FROM ^posts WHERE type='A'");
	}


	function qa_db_ccount_update($db)
/*
	Updated the cached count in the database of the number of comments (excluding hidden)
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_ccount', COUNT(*) FROM ^posts WHERE type='C'");
	}


	function qa_db_tagcount_update($db)
/*
	Updated the cached count in the database of the number of different tags used
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_tagcount', COUNT(*) FROM ^words WHERE tagcount>0");
	}

	
	function qa_db_unaqcount_update($db)
/*
	Updated the cached count in the database of the number of unanswered questions (excluding hidden)
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_unaqcount', COUNT(*) FROM ^posts WHERE type='Q' AND acount=0");
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/