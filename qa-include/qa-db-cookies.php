<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-cookies.php
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

	function qa_db_cookie_create($db, $ip)
	{	
		for ($attempt=0; $attempt<10; $attempt++) {
			$cookieid=qa_db_random_bigint();
			
			if (qa_db_cookie_exists($db, $cookieid))
				continue;

			qa_db_query_sub($db,
				'INSERT INTO ^cookies (cookieid, created, createip) '.
					'VALUES (#, NOW(), COALESCE(INET_ATON($), 0))',
				$cookieid, $ip
			);
		
			return $cookieid;
		}
		
		return null;
	}
	
	function qa_db_cookie_written($db, $cookieid, $ip)
	{
		qa_db_query_sub($db,
			'UPDATE ^cookies SET written=NOW(), writeip=COALESCE(INET_ATON($), 0) WHERE cookieid=#',
			$ip, $cookieid
		);
	}
	
	function qa_db_cookie_exists($db, $cookieid)
	{
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(*) FROM ^cookies WHERE cookieid=#',
			$cookieid
		)) > 0;
	}

?>