<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-votes.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Database-level access to votes tables


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


	function qa_db_uservote_set($db, $postid, $userid, $vote)
/*
	Set the vote for $userid on $postid to $vote in the database
*/
	{
		$vote=max(min(($vote), 1), -1);
		
		qa_db_query_sub($db,
			'INSERT INTO ^uservotes (postid, userid, vote) VALUES (#, #, #) ON DUPLICATE KEY UPDATE vote=#',
			$postid, $userid, $vote, $vote
		);
	}

	
	function qa_db_uservote_get($db, $postid, $userid)
/*
	Get the vote for $userid on $postid from the database (or NULL if none)
*/
	{
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT vote FROM ^uservotes WHERE postid=# AND userid=#',
			$postid, $userid
		), true);
	}
	
	
	function qa_db_post_recount_votes($db, $postid)
/*
	Recalculate the cached count of upvotes and downvotes for $postid
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(GREATEST(0,vote)),0) AS upvotes, -COALESCE(SUM(LEAST(0,vote)),0) AS downvotes FROM ^uservotes WHERE postid=#) AS a SET x.upvotes=a.upvotes, x.downvotes=a.downvotes WHERE x.postid=#',
			$postid, $postid
		);
	}
	
	
	function qa_db_uservote_post_get($db, $postid)
/*
	Returns all non-zero votes on post $postid, array of userid => vote
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub($db,
			'SELECT userid, vote FROM ^uservotes WHERE postid=# AND vote!=0',
			$postid
		), 'userid', 'vote');
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/