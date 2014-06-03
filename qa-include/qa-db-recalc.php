<?php
	
/*
	Question2Answer 1.0.1-beta (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-recalc.php
	Version: 1.0.1-beta
	Date: 2010-05-11 12:36:30 GMT
	Description: Database functions for recalculations (clean-up operations)


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

	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	
//	For reindexing posts...
	
	function qa_db_posts_get_for_reindexing($db, $startpostid, $count)
/*
	Return the information required to reindex up to $count posts starting from $startpostid in the database
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub($db,
			"SELECT ^posts.postid, BINARY ^posts.title AS title, BINARY ^posts.content AS content, BINARY ^posts.tags AS tags, ^posts.type, IF (^posts.type='Q', ^posts.postid, IF(parent.type='Q', parent.postid, grandparent.postid)) AS questionid FROM ^posts LEFT JOIN ^posts AS parent ON ^posts.parentid=parent.postid LEFT JOIN ^posts as grandparent ON parent.parentid=grandparent.postid WHERE ^posts.postid>=# AND ( (^posts.type='Q') OR (^posts.type='A' AND parent.type<=>'Q') OR (^posts.type='C' AND parent.type<=>'Q') OR (^posts.type='C' AND parent.type<=>'A' AND grandparent.type<=>'Q') ) ORDER BY postid LIMIT #",
			$startpostid, $count
		), 'postid');
	}

	
	function qa_db_prepare_for_reindexing($db, $firstpostid, $lastpostid)
/*
	Prepare posts $firstpostid to $lastpostid for reindexing in the database by removing their prior index entries
*/
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
/*
	Remove any rows in the database word indexes with postid from $firstpostid upwards
*/
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
/*
	Return the number of words currently referenced in the database
*/
	{
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(*) FROM ^words'
		));
	}

	
	function qa_db_words_prepare_for_recounting($db, $startwordid, $count)
/*
	Return the ids of up to $count words in the database starting from $startwordid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT wordid FROM ^words WHERE wordid>=# ORDER BY wordid LIMIT #',
			$startwordid, $count
		));
	}

	
	function qa_db_words_recount($db, $firstwordid, $lastwordid)
/*
	Recalculate the cached counts for words $firstwordid to $lastwordid in the database
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^titlewords.wordid) AS titlecount FROM ^words LEFT JOIN ^titlewords ON ^titlewords.wordid=^words.wordid WHERE ^words.wordid>=# AND ^words.wordid<=# GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
			$firstwordid, $lastwordid
		);

		qa_db_query_sub($db,
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^contentwords.wordid) AS contentcount FROM ^words LEFT JOIN ^contentwords ON ^contentwords.wordid=^words.wordid WHERE ^words.wordid>=# AND ^words.wordid<=# GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
			$firstwordid, $lastwordid
		);

		qa_db_query_sub($db,
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^posttags.wordid) AS tagcount FROM ^words LEFT JOIN ^posttags ON ^posttags.wordid=^words.wordid WHERE ^words.wordid>=# AND ^words.wordid<=# GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
			$firstwordid, $lastwordid
		);
		
		qa_db_query_sub($db,
			'DELETE FROM ^words WHERE wordid>=# AND wordid<=# AND titlecount=0 AND contentcount=0 AND tagcount=0',
			$firstwordid, $lastwordid
		);
	}


//	For recalculating numbers of votes and answers for questions

	function qa_db_posts_get_for_recounting($db, $startpostid, $count)
/*
	Return the ids of up to $count posts in the database starting from $startpostid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT postid FROM ^posts WHERE postid>=# ORDER BY postid LIMIT #',
			$startpostid, $count
		));
	}

	
	function qa_db_posts_recount($db, $firstpostid, $lastpostid)
/*
	Recalculate the cached counts for posts $firstpostid to $lastpostid in the database
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT ^posts.postid, COALESCE(SUM(GREATEST(0,^uservotes.vote)),0) AS upvotes, -COALESCE(SUM(LEAST(0,^uservotes.vote)),0) AS downvotes FROM ^posts LEFT JOIN ^uservotes ON ^uservotes.postid=^posts.postid WHERE ^posts.postid>=# AND ^posts.postid<=# GROUP BY postid) AS a SET x.upvotes=a.upvotes, x.downvotes=a.downvotes WHERE x.postid=a.postid',
			$firstpostid, $lastpostid
		);
		
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT parents.postid, COUNT(children.postid) AS acount FROM ^posts AS parents LEFT JOIN ^posts AS children ON parents.postid=children.parentid AND children.type=\'A\' WHERE parents.postid>=# AND parents.postid<=# GROUP BY postid) AS a SET x.acount=a.acount WHERE x.postid=a.postid',
			$firstpostid, $lastpostid
		);
	}

	
//	For recalculating user points

	function qa_db_users_get_for_recalc_points($db, $startuserid, $count)
/*
	Return the ids of up to $count users in the database starting from $startuserid
	If using single sign-on integration, base this on user activity rather than the users table which we don't have
*/
	{
		if (QA_EXTERNAL_USERS)
			return qa_db_read_all_values(qa_db_query_sub($db,
				'(SELECT DISTINCT userid FROM ^posts WHERE userid>=# ORDER BY userid LIMIT #) UNION (SELECT DISTINCT userid FROM ^uservotes WHERE userid>=# ORDER BY userid LIMIT #)',
				$startuserid, $count, $startuserid, $count
			));
		else
			return qa_db_read_all_values(qa_db_query_sub($db,
				'SELECT DISTINCT userid FROM ^users WHERE userid>=# ORDER BY userid LIMIT #',
				$startuserid, $count
			));
	}
	
	function qa_db_users_recalc_points($db, $firstuserid, $lastuserid)
/*
	Recalculate all userpoints columns for users $firstuserid to $lastuserid in the database
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
	
		$qa_userpoints_calculations=qa_db_points_calculations($db);
				
		qa_db_query_sub($db,
			'DELETE FROM ^userpoints WHERE userid>=# AND userid<=#',
			$firstuserid, $lastuserid
		);
		
		if (QA_EXTERNAL_USERS)
			qa_db_query_sub($db,
				'INSERT INTO ^userpoints (userid) SELECT DISTINCT userid FROM ^posts WHERE userid>=# AND userid<=# UNION SELECT DISTINCT userid FROM ^uservotes WHERE userid>=# AND userid<=#',
				$firstuserid, $lastuserid, $firstuserid, $lastuserid
			);
		else
			qa_db_query_sub($db,
				'INSERT INTO ^userpoints (userid) SELECT DISTINCT userid FROM ^users WHERE userid>=# AND userid<=#',
				$firstuserid, $lastuserid
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
/*
	Remove any rows in the userpoints table from $firstuserid upwards
*/
	{
		qa_db_query_sub($db,
			'DELETE FROM ^userpoints WHERE userid>=#',
			$firstuserid
		);
	}

?>