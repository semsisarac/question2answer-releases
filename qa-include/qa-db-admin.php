<?php
	
/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-admin.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Database access functions which are specific to the admin center


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


	function qa_db_count_posts($type, $fromuser=null)
/*
	Return count of number of posts of $type in database.
	Set $fromuser to true to only count non-anonymous posts, false to only count anonymous posts
*/
	{
		$otherparams='';
		
		if (isset($fromuser))
			$otherparams.=' AND userid '.($fromuser ? 'IS NOT' : 'IS').' NULL';
		
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^posts WHERE type=$'.$otherparams,
			$type
		));
	}


	function qa_db_count_users()
/*
	Return number of registered users in database.
*/
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^users'
		));
	}
	

	function qa_db_count_active_users($table)
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
		
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(DISTINCT(userid)) FROM ^'.$table
		));
	}
	
	
	function qa_db_category_create($title, $tags)
/*
	Create a new category with $title (=name) and $tags (=slug) in the database
*/
	{
		$position=qa_db_read_one_value(qa_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^categories'));

		qa_db_query_sub(
			'INSERT INTO ^categories (title, tags, position) VALUES ($, $, #)',
			$title, $tags, $position
		);
		
		return qa_db_last_insert_id();
	}
	
	
	function qa_db_category_rename($categoryid, $title, $tags)
/*
	Set the name of $categoryid to $title and its slug to $tags in the database
*/
	{
		qa_db_query_sub(
			'UPDATE ^categories SET title=$, tags=$ WHERE categoryid=#',
			$title, $tags, $categoryid
		);
	}
	
	
	function qa_db_category_move($categoryid, $newposition)
/*
	Move the category $categoryid into position $newposition in the database
*/
	{
		qa_db_ordered_move('categories', 'categoryid', $categoryid, $newposition);
	}
	
	
	function qa_db_category_delete($categoryid, $reassignid)
/*
	Delete the category $categoryid in the database and reassign its posts to category $reassignid (which can also be null)
*/
	{
		qa_db_query_sub('UPDATE ^posts SET categoryid=# WHERE categoryid=#', $reassignid, $categoryid);
		qa_db_ordered_delete('categories', 'categoryid', $categoryid);
	}
	
	
	function qa_db_page_create($title, $flags, $tags, $heading, $content)
/*
	Create a new page with $title, $flags, $tags, $heading and $content in the database
*/
	{
		$position=qa_db_read_one_value(qa_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^pages'));
		
		qa_db_query_sub(
			'INSERT INTO ^pages (title, nav, flags, tags, heading, content, position) VALUES ($, \'\', #, $, $, $, #)',
			$title, $flags, $tags, $heading, $content, $position
		);
		
		return qa_db_last_insert_id();
	}
	
	
	function qa_db_page_set_fields($pageid, $title, $flags, $tags, $heading, $content)
/*
	Set the fields of $pageid to $title, $flags, $tags, $heading, $content in the database
*/
	{
		qa_db_query_sub(
			'UPDATE ^pages SET title=$, flags=#, tags=$, heading=$, content=$ WHERE pageid=#',
			$title, $flags, $tags, $heading, $content, $pageid
		);
	}
	
	
	function qa_db_page_move($pageid, $nav, $newposition)
/*
	Move the page $pageid into navigation menu $nav and position $newposition in the database
*/
	{
		qa_db_query_sub(
			'UPDATE ^pages SET nav=$ WHERE pageid=#',
			$nav, $pageid
		);

		qa_db_ordered_move('pages', 'pageid', $pageid, $newposition);
	}
	
	
	function qa_db_page_delete($pageid)
/*
	Delete the page $pageid in the database
*/
	{
		qa_db_ordered_delete('pages', 'pageid', $pageid);
	}
	
	
	function qa_db_ordered_move($table, $idcolumn, $id, $newposition)
/*
	Move the entity identified by $idcolumn=$id into position $newposition in $table in the database
*/
	{
		qa_db_query_sub('LOCK TABLES ^'.$table.' WRITE');
		
		$oldposition=qa_db_read_one_value(qa_db_query_sub('SELECT position FROM ^'.$table.' WHERE '.$idcolumn.'=#', $id));
		
		$tempposition=qa_db_read_one_value(qa_db_query_sub('SELECT 1+MAX(position) FROM ^'.$table));
		
		qa_db_query_sub('UPDATE ^'.$table.' SET position=# WHERE '.$idcolumn.'=#', $tempposition, $id);
			// move it temporarily off the top because we have a unique key on the position column
		
		if ($newposition<$oldposition)
			qa_db_query_sub('UPDATE ^'.$table.' SET position=position+1 WHERE position BETWEEN # AND # ORDER BY position DESC', $newposition, $oldposition);
		else
			qa_db_query_sub('UPDATE ^'.$table.' SET position=position-1 WHERE position BETWEEN # AND # ORDER BY position', $oldposition, $newposition);

		qa_db_query_sub('UPDATE ^'.$table.' SET position=# WHERE '.$idcolumn.'=#', $newposition, $id);
		
		qa_db_query_sub('UNLOCK TABLES');
	}
	
	
	function qa_db_ordered_delete($table, $idcolumn, $id)
/*
	Delete the entity identified by $idcolumn=$id in $table in the database
*/
	{
		qa_db_query_sub('LOCK TABLES ^'.$table.' WRITE');
		
		$oldposition=qa_db_read_one_value(qa_db_query_sub('SELECT position FROM ^'.$table.' WHERE '.$idcolumn.'=#', $id));
		
		qa_db_query_sub('DELETE FROM ^'.$table.' WHERE '.$idcolumn.'=#', $id);
		
		qa_db_query_sub('UPDATE ^'.$table.' SET position=position-1 WHERE position># ORDER BY position', $oldposition);
		
		qa_db_query_sub('UNLOCK TABLES');
	}
	
	
	function qa_db_userfield_create($title, $content, $flags)
/*
	Create a new user field with (internal) tag $title, label $content, and $flags in the database
*/
	{
		$position=qa_db_read_one_value(qa_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^userfields'));
		
		qa_db_query_sub(
			'INSERT INTO ^userfields (title, content, position, flags) VALUES ($, $, #, #)',
			$title, $content, $position, $flags
		);

		return qa_db_last_insert_id();
	}
	
	
	function qa_db_userfield_set_fields($fieldid, $content, $flags)
/*
	Change the user field $fieldid to have label $content and $flags in the database (the title column cannot be changed once set)
*/
	{
		qa_db_query_sub(
			'UPDATE ^userfields SET content=$, flags=# WHERE fieldid=#',
			$content, $flags, $fieldid
		);
	}
	
	
	function qa_db_userfield_move($fieldid, $newposition)
/*
	Move the user field $fieldid into position $newposition in the database
*/
	{
		qa_db_ordered_move('userfields', 'fieldid', $fieldid, $newposition);
	}

	
	function qa_db_userfield_delete($fieldid)
/*
	Delete the user field $fieldid in the database
*/
	{
		qa_db_ordered_delete('userfields', 'fieldid', $fieldid);
	}
	
	
	function qa_db_widget_create($title, $tags)
/*
	Return the ID of a new widget, to be displayed by the widget module named $title on templates within $tags (comma-separated list)
*/
	{
		$position=qa_db_read_one_value(qa_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^widgets'));
		
		qa_db_query_sub(
			'INSERT INTO ^widgets (place, position, tags, title) VALUES (\'\', #, $, $)',
			$position, $tags, $title
		);
		
		return qa_db_last_insert_id();
	}
	
	
	function qa_db_widget_set_fields($widgetid, $tags)
/*
	Set the comma-separated list of templates for $widgetid to $tags
*/
	{
		qa_db_query_sub(
			'UPDATE ^widgets SET tags=$ WHERE widgetid=#',
			$tags, $widgetid
		);
	}
	
	
	function qa_db_widget_move($widgetid, $place, $newposition)
/*
	Move the widget $widgetit into position $position in the database's order, and show it in $place on the page
*/
	{
		qa_db_query_sub(
			'UPDATE ^widgets SET place=$ WHERE widgetid=#',
			$place, $widgetid
		);

		qa_db_ordered_move('widgets', 'widgetid', $widgetid, $newposition);
	}
	
	
	function qa_db_widget_delete($widgetid)
/*
	Delete the widget $widgetid in the database
*/
	{
		qa_db_ordered_delete('widgets', 'widgetid', $widgetid);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/