<?php
	
/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-post-update.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description:  Database functions for changing a question, answer or comment


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


	function qa_db_post_set_selchildid($db, $questionid, $selchildid)
/*
	Update the selected answer in the database for $questionid to $selchildid
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts SET selchildid=# WHERE postid=#',
			$selchildid, $questionid
		);
	}

	
	function qa_db_post_set_type($db, $postid, $type, $lastuserid)
/*
	Set the type in the database of $postid to $type, and record that $lastuserid did it
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts SET type=$, updated=NOW(), lastuserid=$ WHERE postid=#',
			$type, $lastuserid, $postid
		);
	}

	
	function qa_db_post_set_parent($db, $postid, $parentid, $lastuserid)
/*
	Set the parent in the database of $postid to $parentid, and record that $lastuserid did it
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts SET parentid=#, updated=NOW(), lastuserid=$ WHERE postid=#',
			$parentid, $lastuserid, $postid
		);
	}

	
	function qa_db_post_set_text($db, $postid, $title, $content, $tagstring, $notify, $lastuserid)
/*
	Set the text fields in the database of $postid to $title, $content, $tagstring and $notify, and record that $lastuserid did it
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts SET title=$, content=$, tags=$, updated=NOW(), notify=$, lastuserid=$ WHERE postid=#',
			$title, $content, $tagstring, $notify, $lastuserid, $postid
		);
	}

	
	function qa_db_post_set_userid($db, $postid, $userid)
/*
	Set the author in the database of $postid to $userid
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^posts SET userid=$ WHERE postid=#',
			$userid, $postid
		);
	}

	
	function qa_db_posttags_get_post_wordids($db, $postid)
/*
	Return an array of wordids that were indexed in the database for the tags of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT wordid FROM ^posttags WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_posttags_delete_post($db, $postid)
/*
	Remove all entries in the database index of post tags for $postid
*/
	{
		qa_db_query_sub($db,
			'DELETE FROM ^posttags WHERE postid=#',
			$postid
		);
	}


	function qa_db_titlewords_get_post_wordids($db, $postid)
/*
	Return an array of wordids that were indexed in the database for the title of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT wordid FROM ^titlewords WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_titlewords_delete_post($db, $postid)
/*
	Remove all entries in the database index of title words for $postid
*/
	{
		qa_db_query_sub($db,
			'DELETE FROM ^titlewords WHERE postid=#',
			$postid
		);
	}


	function qa_db_contentwords_get_post_wordids($db, $postid)
/*
	Return an array of wordids that were indexed in the database for the content of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub($db,
			'SELECT wordid FROM ^contentwords WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_contentwords_delete_post($db, $postid)
/*
	Remove all entries in the database index of content words for $postid
*/
	{
		qa_db_query_sub($db,
			'DELETE FROM ^contentwords WHERE postid=#',
			$postid
		);
	}

?>