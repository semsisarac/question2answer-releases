<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-cookies.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Database access functions for user cookies


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


	function qa_db_cookie_create($db, $ipaddress)
/*
	Create a new random cookie for $ipaddress and insert into database, returning it
*/
	{
		for ($attempt=0; $attempt<10; $attempt++) {
			$cookieid=qa_db_random_bigint();
			
			if (qa_db_cookie_exists($db, $cookieid))
				continue;

			qa_db_query_sub($db,
				'INSERT INTO ^cookies (cookieid, created, createip) '.
					'VALUES (#, NOW(), COALESCE(INET_ATON($), 0))',
				$cookieid, $ipaddress
			);
		
			return $cookieid;
		}
		
		return null;
	}

	
	function qa_db_cookie_written($db, $cookieid, $ipaddress)
/*
	Note in database that a write operation has been done by user identified by $cookieid and from $ipaddress
*/
	{
		qa_db_query_sub($db,
			'UPDATE ^cookies SET written=NOW(), writeip=COALESCE(INET_ATON($), 0) WHERE cookieid=#',
			$ipaddress, $cookieid
		);
	}

	
	function qa_db_cookie_exists($db, $cookieid)
/*
	Return whether $cookieid exists in database
*/
	{
		return qa_db_read_one_value(qa_db_query_sub($db,
			'SELECT COUNT(*) FROM ^cookies WHERE cookieid=#',
			$cookieid
		)) > 0;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/