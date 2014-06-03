<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-recalc.php
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

	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	
//	For reindexing questions and answers...	
	
	function qa_db_posts_get_for_reindexing($db, $startpostid, $count)
	{
		return qa_db_read_all_assoc(qa_db_query_sub($db,
			'SELECT ^posts.postid, BINARY ^posts.title AS title, BINARY ^posts.content AS content, BINARY ^posts.tags AS tags FROM ^posts LEFT JOIN ^posts AS parentpost ON ^posts.parentid=parentpost.postid WHERE ^posts.postid>=# AND (^posts.type=\'Q\' OR ^posts.type=\'A\') AND NOT (parentpost.type<=>\'Q_HIDDEN\') ORDER BY postid LIMIT #',
			$startpostid, $count
		), 'postid');
	}
	
	function qa_db_prepare_for_reindexing($db, $firstpostid, $lastpostid)
	{
		qa_db_query_sub($db,
			'DELETE FROM ^titlewords WHERE postid>=# AND postid<=#',
			$firstpostid, $lastpostid
		);

		qa_db_query_sub($db,
			'DELETE FROM ^contentwords WHERE postid>=# AND postid<=#',
			$firstpostid, $lastpostid
		);

		qa_db_query_sub($db,
			'DELETE FROM ^posttags WHERE postid>=# AND postid<=#',
			$firstpostid, $lastpostid
		);
	}
	
	function qa_db_truncate_indexes($db, $firstpostid)
	{
		qa_db_query_sub($db,
			'DELETE FROM ^titlewords WHERE postid>=#',
			$firstpostid
		);

		qa_db_query_sub($db,
			'DELETE FROM ^contentwords WHERE postid>=#',
			$firstpostid
		);

		qa_db_query_sub($db,
			'DELETE FROM ^posttags WHERE postid>=#',
			$firstpostid
		);
	}
	
	function qa_db_count_words($db)
	{
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(*) FROM ^words'
		));
	}
	
	function qa_db_words_prepare_for_recounting($db, $startwordid, $count)
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT wordid FROM ^words WHERE wordid>=# ORDER BY wordid LIMIT #',
			$startwordid, $count
		));
	}
	
	function qa_db_words_recount($db, $firstwordid, $lastwordid)
	{
		qa_db_query_sub($db,
			'UPDATE ^words SET tagcount=0, titlecount=0, contentcount=0 WHERE wordid>=# AND wordid<=#',
			$firstwordid, $lastwordid
		);
		
		qa_db_query_sub($db,
			'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS titlecount FROM ^titlewords WHERE wordid>=# AND wordid<=# GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
			$firstwordid, $lastwordid		
		);

		qa_db_query_sub($db,
			'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS contentcount FROM ^contentwords WHERE wordid>=# AND wordid<=# GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
			$firstwordid, $lastwordid	
		);

		qa_db_query_sub($db,
			'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS tagcount FROM ^posttags WHERE wordid>=# AND wordid<=# GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
			$firstwordid, $lastwordid
		);
		
		qa_db_query_sub($db,
			'DELETE FROM ^words WHERE wordid>=# AND wordid<=# AND titlecount=0 AND contentcount=0 AND tagcount=0',
			$firstwordid, $lastwordid
		);
	}

//	For recalculating numbers of votes and answers for questions

	function qa_db_posts_get_for_recounting($db, $startpostid, $count)
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT postid FROM ^posts WHERE postid>=# ORDER BY postid LIMIT #',
			$startpostid, $count
		));
	}
	
	function qa_db_posts_recount($db, $firstpostid, $lastpostid)
	{
		qa_db_query_sub($db,
			'UPDATE ^posts SET votes=0, acount=0 WHERE postid>=# AND postid<=#',
			$firstpostid, $lastpostid
		);
		
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT postid, SUM(vote) AS votes FROM ^uservotes WHERE postid>=# AND postid<=# GROUP BY postid) AS a SET x.votes=a.votes WHERE x.postid=a.postid',
			$firstpostid, $lastpostid
		);
		
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT parentid, COUNT(*) AS acount FROM ^posts WHERE parentid>=# AND parentid<=# AND type=\'A\' GROUP BY parentid) AS a SET x.acount=a.acount WHERE x.postid=a.parentid',
			$firstpostid, $lastpostid
		);
	}
	
//	For recalculating user points

	function qa_db_users_get_for_recalc_points($db, $startuserid, $count)
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'(SELECT DISTINCT userid FROM ^posts WHERE userid>=# ORDER BY userid LIMIT #) UNION (SELECT DISTINCT userid FROM ^uservotes WHERE userid>=# ORDER BY userid LIMIT #)',
			$startuserid, $count, $startuserid, $count
		));
	}
	
	function qa_db_users_recalc_points($db, $firstuserid, $lastuserid)
	{
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
	
		$qa_userpoints_calculations=qa_db_points_calculations($db);
				
		qa_db_query_sub($db,
			'DELETE FROM ^userpoints WHERE userid>=# AND userid<=#',
			$firstuserid, $lastuserid
		);
		
		qa_db_query_sub($db,
			'INSERT INTO ^userpoints (userid) SELECT DISTINCT userid FROM ^posts WHERE userid>=# AND userid<=# UNION SELECT DISTINCT userid FROM ^uservotes WHERE userid>=# AND userid<=#',
			$firstuserid, $lastuserid, $firstuserid, $lastuserid
		);
		
		$updatepoints=(int)qa_get_option($db, 'points_base');
		
		foreach ($qa_userpoints_calculations as $field => $calculation) {
			qa_db_query_sub($db,
				'UPDATE ^userpoints, (SELECT userid_src.userid, '.str_replace('~', ' BETWEEN # AND #', $calculation['formula']).' GROUP BY userid) AS results '.
				'SET ^userpoints.'.$field.'=results.'.$field.' WHERE ^userpoints.userid=results.userid',
				$firstuserid, $lastuserid
			);
			
			$updatepoints.='+('.((int)$calculation['multiple']).'*'.$field.')';
		}
		
		qa_db_query_sub($db,
			'UPDATE ^userpoints SET points='.$updatepoints.' WHERE userid>=# AND userid<=#',
			$firstuserid, $lastuserid
		);
	}
	
	function qa_db_truncate_userpoints($db, $firstuserid)
	{
		qa_db_query_sub($db,
			'DELETE FROM ^userpoints WHERE userid>=#',
			$firstuserid
		);
	}

//	Deprecated monolithic recalc functions	
	
	/*function qa_db_word_recount_all($db)
	{
		qa_db_query_sub($db, 'UPDATE ^words SET tagcount=0, titlecount=0, contentcount=0');
		
		qa_db_query_sub($db, 'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS titlecount FROM ^titlewords GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid');

		qa_db_query_sub($db, 'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS contentcount FROM ^contentwords GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid');

		qa_db_query_sub($db, 'UPDATE ^words AS x, (SELECT wordid, COUNT(*) AS tagcount FROM ^posttags GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid');
		
		qa_db_query_sub($db, 'DELETE FROM ^words WHERE titlecount=0 AND contentcount=0 AND tagcount=0');
	}
	
	function qa_db_post_recount_all($db)
	{
		qa_db_query_sub($db, 'UPDATE ^posts SET votes=0 WHERE postid NOT IN (SELECT DISTINCT(postid) FROM ^uservotes)');

		qa_db_query_sub($db, 'UPDATE ^posts AS x, (SELECT postid, SUM(vote) AS votes FROM ^uservotes GROUP BY postid) AS a '.
			'SET x.votes=a.votes WHERE x.postid=a.postid');

		qa_db_query_sub($db, 'UPDATE ^posts AS y, (SELECT x.postid FROM ^posts AS x LEFT JOIN ^posts AS a ON a.parentid=x.postid '.
			'GROUP BY postid HAVING COUNT(a.postid)>0) z SET y.acount=0 WHERE y.postid=z.postid');

		qa_db_query_sub($db, 'UPDATE ^posts AS x, (SELECT parentid, COUNT(*) AS acount FROM ^posts WHERE type=\'A\' GROUP BY parentid) AS a '.
			'SET x.acount=a.acount WHERE x.postid=a.parentid');
		
		qa_db_qcount_update($db);
		qa_db_acount_update($db);
	}
	
	function qa_db_userpoints_recount_all($db)
	{
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
	
		$qa_userpoints_calculations=qa_db_points_calculations($db);
				
		qa_db_query_sub($db, 'DELETE FROM ^userpoints');
		
		qa_db_query_sub($db, 'INSERT INTO ^userpoints (userid) SELECT DISTINCT userid FROM ^posts WHERE userid IS NOT NULL UNION SELECT DISTINCT userid FROM ^uservotes WHERE userid IS NOT NULL');
		
		$updatepoints=(int)qa_get_option($db, 'points_base');
		
		foreach ($qa_userpoints_calculations as $field => $calculation) {
			qa_db_query_sub($db,
				'UPDATE ^userpoints, (SELECT userid_src.userid, '.str_replace('~', ' IS NOT NULL', $calculation['formula']).' GROUP BY userid) AS results '.
				'SET ^userpoints.'.$field.'=results.'.$field.' WHERE ^userpoints.userid=results.userid');
			
			$updatepoints.='+('.((int)$calculation['multiple']).'*'.$field.')';
		}
		
		qa_db_query_sub($db, 'UPDATE ^userpoints SET points='.$updatepoints);
		
		qa_db_userpointscount_update($db);
	}
	*/
	
?>