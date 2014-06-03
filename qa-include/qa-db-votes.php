<?php
	
/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-votes.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Database-level access to votes tables


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


	function qa_db_uservote_set($postid, $userid, $vote)
/*
	Set the vote for $userid on $postid to $vote in the database
*/
	{
		$vote=max(min(($vote), 1), -1);
		
		qa_db_query_sub(
			'INSERT INTO ^uservotes (postid, userid, vote) VALUES (#, #, #) ON DUPLICATE KEY UPDATE vote=#',
			$postid, $userid, $vote, $vote
		);
	}

	
	function qa_db_uservote_get($postid, $userid)
/*
	Get the vote for $userid on $postid from the database (or NULL if none)
*/
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT vote FROM ^uservotes WHERE postid=# AND userid=#',
			$postid, $userid
		), true);
	}
	
	
	function qa_db_post_recount_votes($postid)
/*
	Recalculate the cached count of upvotes and downvotes for $postid in the database
*/
	{
		qa_db_query_sub(
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(GREATEST(0,vote)),0) AS upvotes, -COALESCE(SUM(LEAST(0,vote)),0) AS downvotes FROM ^uservotes WHERE postid=#) AS a SET x.upvotes=a.upvotes, x.downvotes=a.downvotes WHERE x.postid=#',
			$postid, $postid
		);
	}
	
	
	function qa_db_uservote_post_get($postid)
/*
	Returns all non-zero votes on post $postid from the database as an array of [userid] => [vote]
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT userid, vote FROM ^uservotes WHERE postid=# AND vote!=0',
			$postid
		), 'userid', 'vote');
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/