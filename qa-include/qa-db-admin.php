<?php
	
/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-admin.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Database access functions which are specific to the admin center


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


	function qa_db_count_posts($db, $type, $fromuser=null)
/*
	Return count of number of posts of $type in database.
	Set $fromuser to true to only count non-anonymous posts, false to only count anonymous posts
*/
	{
		$otherparams='';
		
		if (isset($fromuser))
			$otherparams.=' AND userid '.($fromuser ? 'IS NOT' : 'IS').' NULL';
		
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(*) FROM ^posts WHERE type=$'.$otherparams,
			$type
		));
	}


	function qa_db_count_users($db)
/*
	Return number of registered users in database.
*/
	{
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(*) FROM ^users'
		));
	}
	

	function qa_db_count_active_users($db, $table)
/*
	Return number of active users in database $table
*/
	{
		switch ($table) {
			case 'posts':
			case 'uservotes':
			case 'userpoints':
				break;
				
			default:
				qa_fatal_error('qa_db_count_active_users() called for unknown table');
				break;
		}
		
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(DISTINCT(userid)) FROM ^'.$table
		));
	}
	
	
	function qa_db_category_create($db, $title, $tags)
/*
	Create a new category with $title and $tags
*/
	{
		$position=qa_db_read_one_value(qa_db_query_sub($db, 'SELECT 1+COALESCE(MAX(position), 0) FROM ^categories'));

		qa_db_query_sub($db,
			'INSERT INTO ^categories (title, tags, position) VALUES ($, $, #)',
			$title, $tags, $position
		);
		
		return qa_db_last_insert_id($db);
	}
	
	
	function qa_db_category_rename($db, $categoryid, $title, $tags)
/*
	Set the name of $categoryid to $name
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^categories SET title=$, tags=$ WHERE categoryid=#',
			$title, $tags, $categoryid
		);
	}
	
	
	function qa_db_category_move($db, $categoryid, $newposition)
/*
	Move the category $categoryid into position $position
*/
	{
		qa_db_ordered_move($db, 'categories', 'categoryid', $categoryid, $newposition);
	}
	
	
	function qa_db_category_delete($db, $categoryid, $reassignid)
/*
	Delete the category $categoryid and reassign its posts to category $reassignid (which can also be null)
*/
	{
		qa_db_query_sub($db, 'UPDATE ^posts SET categoryid=# WHERE categoryid=#', $reassignid, $categoryid);
		qa_db_ordered_delete($db, 'categories', 'categoryid', $categoryid);
	}
	
	
	function qa_db_page_create($db, $title, $flags, $tags, $heading, $content)
/*
	Create a new page with $title, $tags, $heading and $content
*/
	{
		$position=qa_db_read_one_value(qa_db_query_sub($db, 'SELECT 1+COALESCE(MAX(position), 0) FROM ^pages'));
		
		qa_db_query_sub($db,
			'INSERT INTO ^pages (title, flags, tags, heading, content, position) VALUES ($, #, $, $, $, #)',
			$title, $flags, $tags, $heading, $content, $position
		);
		
		return qa_db_last_insert_id($db);
	}
	
	
	function qa_db_page_set_fields($db, $pageid, $title, $flags, $tags, $heading, $content)
/*
	Set the text fields of $pageid to $title, $tags, $heading, $content
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^pages SET title=$, flags=#, tags=$, heading=$, content=$ WHERE pageid=#',
			$title, $flags, $tags, $heading, $content, $pageid
		);
	}
	
	
	function qa_db_page_move($db, $pageid, $nav, $newposition)
/*
	Move the page $pageid into navigation menu $nav and position $newposition
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^pages SET nav=$ WHERE pageid=#',
			$nav, $pageid
		);

		qa_db_ordered_move($db, 'pages', 'pageid', $pageid, $newposition);
	}
	
	
	function qa_db_page_delete($db, $pageid)
/*
	Delete the page $pageid
*/
	{
		qa_db_ordered_delete($db, 'pages', 'pageid', $pageid);
	}
	
	
	function qa_db_ordered_move($db, $table, $idcolumn, $id, $newposition)
/*
	Move the entity identified by $idcolumn=$id into position $newposition in $table
*/
	{
		qa_db_query_sub($db, 'LOCK TABLES ^'.$table.' WRITE');
		
		$oldposition=qa_db_read_one_value(qa_db_query_sub($db, 'SELECT position FROM ^'.$table.' WHERE '.$idcolumn.'=#', $id));
		
		$tempposition=qa_db_read_one_value(qa_db_query_sub($db, 'SELECT 1+MAX(position) FROM ^'.$table));
		
		qa_db_query_sub($db, 'UPDATE ^'.$table.' SET position=# WHERE '.$idcolumn.'=#', $tempposition, $id);
			// move it temporarily off the top because we have a unique key on the position column
		
		if ($newposition<$oldposition)
			qa_db_query_sub($db, 'UPDATE ^'.$table.' SET position=position+1 WHERE position BETWEEN # AND # ORDER BY position DESC', $newposition, $oldposition);
		else
			qa_db_query_sub($db, 'UPDATE ^'.$table.' SET position=position-1 WHERE position BETWEEN # AND # ORDER BY position', $oldposition, $newposition);

		qa_db_query_sub($db, 'UPDATE ^'.$table.' SET position=# WHERE '.$idcolumn.'=#', $newposition, $id);
		
		qa_db_query_sub($db, 'UNLOCK TABLES');	
	}
	
	
	function qa_db_ordered_delete($db, $table, $idcolumn, $id)
/*
	Delete the entity identified by $idcolumn=$id in $table
*/
	{
		qa_db_query_sub($db, 'LOCK TABLES ^'.$table.' WRITE');
		
		$oldposition=qa_db_read_one_value(qa_db_query_sub($db, 'SELECT position FROM ^'.$table.' WHERE '.$idcolumn.'=#', $id));
		
		qa_db_query_sub($db, 'DELETE FROM ^'.$table.' WHERE '.$idcolumn.'=#', $id);
		
		qa_db_query_sub($db, 'UPDATE ^'.$table.' SET position=position-1 WHERE position># ORDER BY position', $oldposition);
		
		qa_db_query_sub($db, 'UNLOCK TABLES');
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/