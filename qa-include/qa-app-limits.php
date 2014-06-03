<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-limits.php
	Version: 1.0-beta-1
	Date: 2010-02-04 14:10:15 GMT


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

	require_once QA_INCLUDE_DIR.'qa-db-limits.php';
	
	function qa_limits_remaining($db, $userid, $action)
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$period=(int)(time()/3600);
		$dblimits=qa_db_limits_get($db, $userid, $_SERVER['REMOTE_ADDR'], $action);
		
		switch ($action) {
			case 'Q':
				$options=qa_get_options($db, array('max_rate_user_qs', 'max_rate_ip_qs'));
				$userlimit=$options['max_rate_user_qs'];
				$iplimit=$options['max_rate_ip_qs'];
				break;
				
			case 'A':
				$options=qa_get_options($db, array('max_rate_user_as', 'max_rate_ip_as'));
				$userlimit=$options['max_rate_user_as'];
				$iplimit=$options['max_rate_ip_as'];
				break;
				
			case 'V':
				$options=qa_get_options($db, array('max_rate_user_votes', 'max_rate_ip_votes'));
				$userlimit=$options['max_rate_user_votes'];
				$iplimit=$options['max_rate_ip_votes'];
				break;
		}
		
		return max(0, min(
			$userlimit-((@$dblimits['user']['period']==$period) ? $dblimits['user']['count'] : 0),
			$iplimit-((@$dblimits['ip']['period']==$period) ? $dblimits['ip']['count'] : 0)
		));
	}
	
	function qa_limits_increment($db, $userid, $action)
	{
		$period=(int)(time()/3600);
		
		if (isset($userid))
			qa_db_limits_user_add($db, $userid, $action, $period, 1);
		
		qa_db_limits_ip_add($db, $_SERVER['REMOTE_ADDR'], $action, $period, 1);
	}
	
	function qa_report_write_action($db, $userid, $cookieid, $action, $questionid, $answerid)
	{		
		switch ($action) {
			case 'q_post':
				qa_limits_increment($db, $userid, 'Q');
				break;
			
			case 'a_post':
				qa_limits_increment($db, $userid, 'A');
				break;
			
			case 'q_vote_up':
			case 'q_vote_down':
			case 'q_vote_nil':
			case 'a_vote_up':
			case 'a_vote_down':
			case 'a_vote_nil':
				qa_limits_increment($db, $userid, 'V');
				break;
		}

		if (isset($userid)) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			qa_user_report_action($db, $userid, $action, $questionid, $answerid);
		}
		
		if (isset($cookieid)) {
			require_once QA_INCLUDE_DIR.'qa-app-cookies.php';

			qa_cookie_report_action($db, $cookieid, $action, $questionid, $answerid);
		}
	}

?>