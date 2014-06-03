<?php
	
/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-votes.php
	Version: 1.0-beta-3
	Date: 2010-03-31 12:13:41 GMT


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

	function qa_db_uservote_set($db, $postid, $userid, $vote)
	{
		$vote=max(min(($vote), 1), -1);
		
		qa_db_query_sub($db,
			'INSERT INTO ^uservotes (postid, userid, vote) VALUES (#, #, #) ON DUPLICATE KEY UPDATE vote=#',
			$postid, $userid, $vote, $vote
		);
	}
	
	function qa_db_post_recount_votes($db, $postid)
	{
		qa_db_query_sub($db,
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(GREATEST(0,vote)),0) AS upvotes, -COALESCE(SUM(LEAST(0,vote)),0) AS downvotes FROM ^uservotes WHERE postid=#) AS a SET x.upvotes=a.upvotes, x.downvotes=a.downvotes WHERE x.postid=#',
			$postid, $postid
		);
	}
	
?>