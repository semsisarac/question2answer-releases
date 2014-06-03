<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-limits.php
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

	function qa_db_limits_get($db, $userid, $ip, $action)
	{
		$selects=array();
		$arguments=array();
		
		if (isset($userid)) {
			$selects[]="(SELECT 'user' AS limitkey, period, count FROM ^userlimits WHERE userid=$ AND action=$)";
			$arguments[]=$userid;
			$arguments[]=$action;
		}
		
		if (isset($ip)) {
			$selects[]="(SELECT 'ip' AS limitkey, period, count FROM ^iplimits WHERE ip=COALESCE(INET_ATON($), 0) AND action=$)";
			$arguments[]=$ip;
			$arguments[]=$action;
		}
		
		if (count($selects)) {
			$query=qa_db_apply_sub($db, implode(' UNION ALL ', $selects), $arguments);
			return qa_db_read_all_assoc(qa_db_query_raw($db, $query), 'limitkey');
			
		} else
			return array();
	}
	
	function qa_db_limits_user_add($db, $userid, $action, $period, $count)
	{
		qa_db_query_sub($db,
			'INSERT INTO ^userlimits (userid, action, period, count) VALUES ($, $, #, #) '.
			'ON DUPLICATE KEY UPDATE count=IF(period=#, count+#, #), period=#',
			$userid, $action, $period, $count, $period, $count, $count, $period
		);
	}
	
	function qa_db_limits_ip_add($db, $ip, $action, $period, $count)
	{
		qa_db_query_sub($db,
			'INSERT INTO ^iplimits (ip, action, period, count) VALUES (COALESCE(INET_ATON($), 0), $, #, #) '.
			'ON DUPLICATE KEY UPDATE count=IF(period=#, count+#, #), period=#',
			$ip, $action, $period, $count, $period, $count, $count, $period
		);
	}

?>