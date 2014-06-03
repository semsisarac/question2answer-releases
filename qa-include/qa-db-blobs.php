<?php
	
/*
	Question2Answer 1.3-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-blobs.php
	Version: 1.3-beta-1
	Date: 2010-11-04 12:12:11 GMT
	Description: Database-level access to blobs table


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


	function qa_db_blob_create($content, $format)
/*
	
*/
	{
		for ($attempt=0; $attempt<10; $attempt++) {
			$blobid=qa_db_random_bigint();
			
			if (qa_db_blob_exists($blobid))
				continue;

			qa_db_query_sub(
				'INSERT INTO ^blobs (blobid, format, content) VALUES (#, $, $)',
				$blobid, $format, $content
			);
		
			return $blobid;
		}
		
		return null;
	}
	
	
	function qa_db_blob_read($blobid)
/*

*/
	{
		return qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT content, format FROM ^blobs WHERE blobid=#',
			$blobid
		), true);
	}
	
	
	function qa_db_blob_delete($blobid)
	{
		qa_db_query_sub(
			'DELETE FROM ^blobs WHERE blobid=#',
			$blobid
		);
	}

	
	function qa_db_blob_exists($blobid)
/*

*/
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^blobs WHERE blobid=#',
			$blobid
		)) > 0;
	}

	

/*
	Omit PHP closing tag to help avoid accidental output
*/