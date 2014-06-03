<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-install.php
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

	function qa_db_user_column_type_verify()
	{
		$coltype=strtoupper(qa_get_mysql_user_column_type());

		switch ($coltype) {
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INT':
			case 'BIGINT':
			case 'SMALLINT UNSIGNED':
			case 'MEDIUMINT UNSIGNED':
			case 'INT UNSIGNED':
			case 'BIGINT UNSIGNED':
				// these are all OK
				break;
			
			default:
				if (!preg_match('/VARCHAR\([0-9]+\)/', $coltype))
					qa_fatal_error('Specified user column type is not one of allowed values - please read documentation');
				break;
		}
		
		return $coltype;
	}
	
	function qa_db_table_definitions()
	{
		require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';

	/*
		Important note on character encoding in database and PHP connection to MySQL

		All of our TEXT-style columns get UTF-8 character sets and collation from the default defined on the tables.
		However, when connecting to MySQL from PHP, we don't issue a SET NAMES 'utf8' query or call mysql_set_charset('utf8').
		This saves latency, since it could incur an extra query that needs to go from PHP to MySQL and back.
		But as a result, MySQL assumes by default that our connection from PHP is using LATIN-1 encoding.
		It would therefore convert the character set of both incoming and outgoing text, which would be a disaster.
		We prevent this by retrieving columns using BINARY (e.g. SELECT BINARY name AS name) since binary content is left as is.
		And also we precede any text in our queries with the _utf8 introducer, so that MySQL understands it's already UTF-8.
	*/
	
	/*
		Note: In MySQL versions prior to 5.0.3, VARCHAR(x) columns will be silently converted to TEXT where x>255
	*/
	
		$useridcoltype=qa_db_user_column_type_verify();

		$tables=array(
			'users' => array(
				'userid' => $useridcoltype.' NOT NULL AUTO_INCREMENT',
				'created' => 'DATETIME NOT NULL',
				'createip' => 'INT UNSIGNED NOT NULL',
				'email' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.') NOT NULL',
				'handle' => 'VARCHAR('.QA_DB_MAX_HANDLE_LENGTH.') NOT NULL',
				'passsalt' => 'BINARY(16) NOT NULL',
				'passcheck' => 'BINARY(20) NOT NULL',
				'level' => 'TINYINT UNSIGNED NOT NULL',
				'loggedin' => 'DATETIME NOT NULL',
				'loginip' => 'INT UNSIGNED NOT NULL',
				'written' => 'DATETIME',
				'writeip' => 'INT UNSIGNED',
				'resetcode' => 'BINARY(8) NOT NULL',
				'PRIMARY KEY (userid)',
				'KEY (email)',
				'KEY (handle)',
				'KEY (level)',
			),
			
			'userprofile' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'title' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.') NOT NULL',
				'content' => 'VARCHAR('.QA_DB_MAX_PROFILE_CONTENT_LENGTH.') NOT NULL',
				'UNIQUE(userid,title)',
			),

			'cookies' => array(
				'cookieid' => 'BIGINT UNSIGNED NOT NULL',
				'created' => 'DATETIME NOT NULL',
				'createip' => 'INT UNSIGNED NOT NULL',
				'written' => 'DATETIME',
				'writeip' => 'INT UNSIGNED',
				'PRIMARY KEY (cookieid)',
			),
			
			'posts' => array(
				'postid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'type' => "ENUM('Q', 'A', 'C', 'Q_HIDDEN', 'A_HIDDEN', 'C_HIDDEN') NOT NULL",
				'parentid' => 'INT UNSIGNED',
				'acount' => 'SMALLINT UNSIGNED NOT NULL',
				'selchildid' => 'INT UNSIGNED',
				'userid' => $useridcoltype,
				'cookieid' => 'BIGINT UNSIGNED',
				'votes' => 'INT NOT NULL',
				'title' => 'VARCHAR('.QA_DB_MAX_TITLE_LENGTH.')',
				'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.')',
				'tags' => 'VARCHAR('.QA_DB_MAX_TAGS_LENGTH.')',
				'format' => "CHAR(1) CHARACTER SET ascii NOT NULL", // for future obvious use
				'created' => 'DATETIME NOT NULL',
				'updated' => 'DATETIME NOT NULL',
				'notify' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.')',
				'PRIMARY KEY (postid)',
				'KEY (type, created)',
				'KEY (parentid, type)',
				'KEY (userid, type, created)',
				'KEY (selchildid)',
			),
			
			'words' => array(
				'wordid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'word' => 'VARCHAR('.QA_DB_MAX_WORD_LENGTH.') NOT NULL',
				'titlecount' => 'INT UNSIGNED NOT NULL', // only counts one per post 
				'contentcount' => 'INT UNSIGNED NOT NULL', // only counts one per post 
				'tagcount' => 'INT UNSIGNED NOT NULL',
				'PRIMARY KEY (wordid)',
				'KEY (word)',
				'KEY (tagcount)',
			),
			
			'titlewords' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'KEY (postid)',
				'KEY (wordid)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
				
			'contentwords' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'count' => 'TINYINT UNSIGNED NOT NULL', // anything over 255 can be ignored
				'KEY (postid)',
				'KEY (wordid)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
				
			'posttags' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'postcreated' => 'DATETIME NOT NULL',
				'KEY (postid)',
				'KEY (wordid,postcreated)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
			
			'uservotes' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'userid' => $useridcoltype.' NOT NULL',
				'vote' => 'TINYINT NOT NULL',
				'UNIQUE (userid, postid)',
				'KEY (postid)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
			),
			
			// many userpoints columns could be unsigned but MySQL appears to mess up points calculations that go negative as a result
			
			'userpoints' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'points' => 'INT NOT NULL',
				'qposts' => 'MEDIUMINT NOT NULL',
				'aposts' => 'MEDIUMINT NOT NULL',
				'aselects' => 'MEDIUMINT NOT NULL',
				'aselecteds' => 'MEDIUMINT NOT NULL',
				'qvotes' => 'MEDIUMINT NOT NULL',
				'avotes' => 'MEDIUMINT NOT NULL',
				'qvoteds' => 'INT NOT NULL',
				'avoteds' => 'INT NOT NULL',
				'PRIMARY KEY (userid)',
				'KEY(points)',
			),
				
			'userlimits' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'action' => "CHAR(1) CHARACTER SET ascii NOT NULL",
				'period' => 'INT UNSIGNED NOT NULL',
				'count' => 'SMALLINT UNSIGNED NOT NULL',
				'UNIQUE (userid, action)',
			),
				
			'iplimits' => array(
				'ip' => 'INT UNSIGNED NOT NULL',
				'action' => "CHAR(1) CHARACTER SET ascii NOT NULL",
				'period' => 'INT UNSIGNED NOT NULL',
				'count' => 'SMALLINT UNSIGNED NOT NULL',
				'UNIQUE (ip, action)',
			),
				
			'options' => array(
				'title' => 'VARCHAR('.QA_DB_MAX_OPTION_TITLE_LENGTH.') NOT NULL',
				'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.') NOT NULL',
				'PRIMARY KEY (title)',
			),
		);	
		
		if (QA_EXTERNAL_USERS) {
			unset($tables['users']);
			unset($tables['userprofile']);

		} else {
			$userforeignkey='FOREIGN KEY (userid) REFERENCES ^users(userid)';
			$userforeignsetnull=$userforeigncascade=' ON DELETE ';
			$userforeignsetnull.='SET NULL';
			$userforeigncascade.='CASCADE';
			
			$tables['userprofile'][]=$userforeignkey.' ON DELETE CASCADE';
			$tables['posts'][]=$userforeignkey.' ON DELETE SET NULL';
			$tables['uservotes'][]=$userforeignkey.' ON DELETE CASCADE';
			$tables['userlimits'][]=$userforeignkey.' ON DELETE CASCADE';
		}

		return $tables;
	}
	
	function qa_array_to_lower_keys($array)
	{
		$keyarray=array();

		foreach ($array as $value)
			$keyarray[strtolower($value)]=true;
			
		return $keyarray;
	}
	
	function qa_db_missing_tables($db, $definitions)
	{
		$keydbtables=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_raw($db, 'SHOW TABLES')));
		
		$missing=array();
		
		foreach ($definitions as $rawname => $definition)
			if (!isset($keydbtables[strtolower(QA_MYSQL_TABLE_PREFIX.$rawname)]))
				$missing[$rawname]=$definition;
		
		return $missing;
	}
	
	function qa_db_missing_columns($db, $table, $definition)
	{
		$keycolumns=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_sub($db, 'SHOW COLUMNS FROM ^'.$table)));
		
		$missing=array();
		
		foreach ($definition as $colname => $coldefn)
			if ( (!is_int($colname)) && !isset($keycolumns[strtolower($colname)]) )
				$missing[$colname]=$coldefn;
				
		return $missing;
	}
	
	function qa_db_check_tables($db)
	{
		$version=qa_db_read_one_value(qa_db_query_raw($db, 'SELECT VERSION()'));
		
		if (((float)$version)<4.1)
			qa_fatal_error('MySQL version 4.1 or later is required - you appear to be running MySQL '.$version);
		
		$definitions=qa_db_table_definitions();
		$missing=qa_db_missing_tables($db, $definitions);
		
		if (count($missing) == count($definitions))
			return 'none';
		
		elseif (count($missing))
			return 'table-missing';
			
		else
			foreach ($definitions as $table => $definition)
				if (count(qa_db_missing_columns($db, $table, $definition)))
					return 'column-missing';
				
		return false;
	}
	
	function qa_db_install_tables($db)
	{
		$definitions=qa_db_table_definitions();
		
		$missingtables=qa_db_missing_tables($db, $definitions);
		
		foreach ($missingtables as $rawname => $definition) {
			$querycols='';
			foreach ($definition as $colname => $coldef)
				$querycols.=(strlen($querycols) ? ', ' : '').(is_int($colname) ? $coldef : ($colname.' '.$coldef));
				
			qa_db_query_sub($db, 'CREATE TABLE ^'.$rawname.' ('.$querycols.') ENGINE=InnoDB CHARSET=utf8');
		}
		
		foreach ($definitions as $table => $definition) {
			$missingcolumns=qa_db_missing_columns($db, $table, $definition);
			
			foreach ($missingcolumns as $colname => $coldefn)
				qa_db_query_sub($db, 'ALTER TABLE ^'.$table.' ADD COLUMN '.$colname.' '.$coldefn);
		}
		
		qa_db_query_sub($db, "REPLACE ^options (title,content) VALUES ('db_version', 1)");
	}

?>