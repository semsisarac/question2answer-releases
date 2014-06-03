<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-cookies.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: User cookie management (application level) for tracking anonymous posts


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


	function qa_cookie_get()
/*
	Return the user identification cookie sent by the browser for this page request, or null if none
*/
	{
		return isset($_COOKIE['qa_id']) ? qa_gpc_to_string($_COOKIE['qa_id']) : null;
	}

	
	function qa_cookie_get_create($db)
/*
	Return user identification cookie sent by browser if valid, or create a new one if not.
	Either way, extend for another year (this is used when an anonymous post is created)
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-cookies.php';

		$cookieid=qa_cookie_get();
		
		if (isset($cookieid) && qa_db_cookie_exists($db, $cookieid))
			; // cookie is valid
		else
			$cookieid=qa_db_cookie_create($db, @$_SERVER['REMOTE_ADDR']);
		
		setcookie('qa_id', $cookieid, time()+86400*365, '/');
		
		return $cookieid;
	}

	
	function qa_cookie_report_action($db, $cookieid, $action, $questionid, $answerid, $commentid)
/*
	Called after a database write $action performed by a user identified by $cookieid,
	relating to $questionid, $answerid and/or $commentid
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-cookies.php';
		
		qa_db_cookie_written($db, $cookieid, @$_SERVER['REMOTE_ADDR']);
	}

?>