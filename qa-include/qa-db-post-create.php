<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-post-create.php
	Version: 1.0-beta-1
	Date: 2010-02-04 14:10:15 GMT


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

	function qa_db_post_create($db, $type, $parentid, $userid, $cookieid, $title, $content, $tagstring, $notify)
	{	
		qa_db_query_sub($db,
			'INSERT INTO ^posts (type, parentid, userid, cookieid, title, content, tags, notify, created, updated) VALUES ($, #, $, #, $, $, $, $, NOW(), NOW())',
			$type, $parentid, $userid, $cookieid, $title, $content, $tagstring, $notify
		);
		
		return qa_db_last_insert_id($db);
	}
	
	function qa_db_post_acount_update($db, $postid)
	{
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT COUNT(*) AS acount FROM ^posts WHERE parentid=# AND type=\'A\') AS a SET x.acount=a.acount WHERE x.postid=#',
			$postid, $postid);
	}
	
	function qa_db_posttags_add_post_wordids($db, $postid, $wordids)
	{	
		if (count($wordids))
			qa_db_query_sub($db,
				'INSERT INTO ^posttags (postid, wordid, postcreated) SELECT postid, wordid, created FROM ^words, ^posts WHERE postid=# AND wordid IN ($)',
				$postid, $wordids
			);
	}
	
	function qa_db_titlewords_add_post_wordids($db, $postid, $wordids)
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
	
	function qa_db_contentwords_add_post_wordidcounts($db, $postid, $wordidcounts)
	{
		if (count($wordidcounts)) {
			$rowstoadd=array();
			foreach ($wordidcounts as $wordid => $count)
				$rowstoadd[]=array($postid, $wordid, $count);

			qa_db_query_sub($db,
				'INSERT INTO ^contentwords (postid, wordid, count) VALUES #',
				$rowstoadd
			);
		}
	}
	
	function qa_db_word_mapto_ids($db, $words)
	{	
		if (count($words))
			return qa_db_read_all_assoc(qa_db_query_sub($db,
				'SELECT wordid, BINARY word AS word FROM ^words WHERE word IN ($)', $words
			), 'word', 'wordid');
		else
			return array();
	}
	
	function qa_db_word_mapto_ids_add($db, $words)
	{
		$wordtoid=qa_db_word_mapto_ids($db, $words);
		
		$wordstoadd=array();
		foreach ($words as $word)
			if (!isset($wordtoid[$word]))
				$wordstoadd[]=$word;
		
		if (count($wordstoadd)) {
			qa_db_query_sub($db, 'LOCK TABLES ^words WRITE');
			
			// do it all again in case table content changed before it was locked
			
			$wordtoid=qa_db_word_mapto_ids($db, $words); 
			
			$rowstoadd=array();
			foreach ($words as $word)
				if (!isset($wordtoid[$word]))
					$rowstoadd[]=array($word);
				
			qa_db_query_sub($db, 'INSERT INTO ^words (word) VALUES $', $rowstoadd);
			
			qa_db_query_sub($db, 'UNLOCK TABLES');
			
			$wordtoid=qa_db_word_mapto_ids($db, $words); 
		}
		
		return $wordtoid;
	}
	
	function qa_db_word_titlecount_update($db, $wordids)
	{
		if (count($wordids))
			qa_db_query_sub($db, 'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS titlecount FROM ^titlewords WHERE wordid IN (#) GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid', $wordids);
	}
	
	function qa_db_word_tagcount_update($db, $wordids)
	{
		if (count($wordids))
			qa_db_query_sub($db, 'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS tagcount FROM ^posttags WHERE wordid IN (#) GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid', $wordids);
	}
	
	function qa_db_word_contentcount_update($db, $wordids)
	{
		if (count($wordids))
			qa_db_query_sub($db, 'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS contentcount FROM ^contentwords WHERE wordid IN (#) GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid', $wordids);
	}
	
	function qa_db_qcount_update($db)
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_qcount', COUNT(*) FROM ^posts WHERE type='Q'");
	}

	function qa_db_acount_update($db)
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_acount', COUNT(*) FROM ^posts WHERE type='A'");
	}

	function qa_db_tagcount_update($db)
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_tagcount', COUNT(*) FROM ^words WHERE tagcount>0");
	}
	
?>