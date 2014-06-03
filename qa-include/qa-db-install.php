<?php

/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-install.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Database-level functions for installation and upgrading


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

	define('QA_DB_VERSION_CURRENT', 8);


	function qa_db_user_column_type_verify()
/*
	Return the column type for user ids after verifying it is one of the legal options
*/
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
/*
	Return an array of table definitions. For each element of the array, the key is the table name (without prefix)
	and the value is an array of column definitions, [column name] => [definition]. The column name is omitted for indexes.
*/
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


		Note: In MySQL versions prior to 5.0.3, VARCHAR(x) columns will be silently converted to TEXT where x>255
		
		
		See box at top of qa-app-recalc.php for a list of redundant (non-normal) information in the database
	*/
	
		$useridcoltype=qa_db_user_column_type_verify();

		$tables=array(
			'users' => array(
				'userid' => $useridcoltype.' NOT NULL AUTO_INCREMENT',
				'created' => 'DATETIME NOT NULL',
				'createip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address when created
				'email' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.') NOT NULL',
				'handle' => 'VARCHAR('.QA_DB_MAX_HANDLE_LENGTH.') NOT NULL', // username
				'passsalt' => 'BINARY(16) NOT NULL', // salt used to calculate passcheck
				'passcheck' => 'BINARY(20) NOT NULL', // checksum from password and passsalt
				'level' => 'TINYINT UNSIGNED NOT NULL', // basic, editor, admin, etc...
				'loggedin' => 'DATETIME NOT NULL', // time of last login
				'loginip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address of last login
				'written' => 'DATETIME', // time of last write action done by user
				'writeip' => 'INT UNSIGNED', // INET_ATON of IP address of last write action done by user
				'resetcode' => 'BINARY(8) NOT NULL', // for resetting passwords
				'sessioncode' => 'BINARY(8) NOT NULL', // for comparing against session cookie in browser
				'PRIMARY KEY (userid)',
				'KEY (email)',
				'KEY (handle)',
				'KEY (level)',
			),
			
			'userprofile' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'title' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.') NOT NULL', // profile field name
				'content' => 'VARCHAR('.QA_DB_MAX_PROFILE_CONTENT_LENGTH.') NOT NULL', // profile field value
				'UNIQUE(userid,title)',
			),

			'cookies' => array(
				'cookieid' => 'BIGINT UNSIGNED NOT NULL',
				'created' => 'DATETIME NOT NULL',
				'createip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address when cookie created
				'written' => 'DATETIME', // time of last write action done by anon user with cookie
				'writeip' => 'INT UNSIGNED', // INET_ATON of IP address of last write action done by anon user with cookie
				'PRIMARY KEY (cookieid)',
			),
			
			'posts' => array(
				'postid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'type' => "ENUM('Q', 'A', 'C', 'Q_HIDDEN', 'A_HIDDEN', 'C_HIDDEN') NOT NULL",
				'parentid' => 'INT UNSIGNED', // for follow on questions, all answers and comments
				'acount' => 'SMALLINT UNSIGNED NOT NULL', // number of answers (for questions)
				'selchildid' => 'INT UNSIGNED', // selected answer (for questions)
				'userid' => $useridcoltype, // which user wrote it
				'cookieid' => 'BIGINT UNSIGNED', // which cookie wrote it, if an anonymous post
				'lastuserid' => $useridcoltype, // which user last modified it
				'upvotes' => 'SMALLINT UNSIGNED NOT NULL',
				'downvotes' => 'SMALLINT UNSIGNED NOT NULL',
				'format' => "CHAR(1) CHARACTER SET ascii NOT NULL", // for future use, ignored for now
				'created' => 'DATETIME NOT NULL',
				'updated' => 'DATETIME', // time of last update
				'title' => 'VARCHAR('.QA_DB_MAX_TITLE_LENGTH.')',
				'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.')',
				'tags' => 'VARCHAR('.QA_DB_MAX_TAGS_LENGTH.')', // string of tags separated by commas
				'notify' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.')', // email address, or @ to get from user, or NULL for none
				'PRIMARY KEY (postid)',
				'KEY (type, created)', // for getting recent questions, answers, comments
				'KEY (parentid, type)', // for getting a question's answers, any post's comments and follow-on questions
				'KEY (userid, type, created)', // for recent questions, answers or comments by a user
				'KEY (selchildid)', // for counting how many of a user's answers have been selected
				'KEY (type, acount, created)', // for getting unanswered questions
			),
			
			'words' => array(
				'wordid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'word' => 'VARCHAR('.QA_DB_MAX_WORD_LENGTH.') NOT NULL',
				'titlecount' => 'INT UNSIGNED NOT NULL', // only counts one per post
				'contentcount' => 'INT UNSIGNED NOT NULL', // only counts one per post
				'tagcount' => 'INT UNSIGNED NOT NULL', // only counts one per post (though no duplicate tags anyway)
				'PRIMARY KEY (wordid)',
				'KEY (word)',
				'KEY (tagcount)', // for sorting by most popular tags
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
				'count' => 'TINYINT UNSIGNED NOT NULL', // how many times word apperas in the post - anything over 255 can be ignored
				'type' => "ENUM('Q', 'A', 'C') NOT NULL", // the post's type (copied here for quick searching)
				'questionid' => 'INT UNSIGNED NOT NULL', // the id of the post's antecedent parent (here for quick searching)
				'KEY (postid)',
				'KEY (wordid)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
				
			'posttags' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'postcreated' => 'DATETIME NOT NULL', // created time of post (copied here for tag page's list of recent questions)
				'KEY (postid)',
				'KEY (wordid,postcreated)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
			
			'uservotes' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'userid' => $useridcoltype.' NOT NULL',
				'vote' => 'TINYINT NOT NULL', // -1, 0 or 1
				'UNIQUE (userid, postid)',
				'KEY (postid)',
				'FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
			),
			
			// many userpoints columns could be unsigned but MySQL appears to mess up points calculations that go negative as a result
			
			'userpoints' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'points' => 'INT NOT NULL', // user's points as displayed, after final multiple
				'qposts' => 'MEDIUMINT NOT NULL', // number of questions by user (excluding hidden)
				'aposts' => 'MEDIUMINT NOT NULL', // number of answers by user (excluding hidden)
				'cposts' => 'MEDIUMINT NOT NULL', // number of comments by user (excluding hidden)
				'aselects' => 'MEDIUMINT NOT NULL', // number of questions by user where they've selected an answer
				'aselecteds' => 'MEDIUMINT NOT NULL', // number of answers by user that have been selected as the best
				'qvotes' => 'MEDIUMINT NOT NULL', // number of questions the user has voted on
				'avotes' => 'MEDIUMINT NOT NULL', // number of answers the user has voted on
				'qvoteds' => 'INT NOT NULL', // points from votes on this user's questions (applying per-question limits), before final multiple
				'avoteds' => 'INT NOT NULL', // points from votes on this user's answers (applying per-answer limits), before final multiple
				'upvoteds' => 'INT NOT NULL', // number of up votes received on questions or answers
				'downvoteds' => 'INT NOT NULL', // number of down votes received on questions or answers
				'PRIMARY KEY (userid)',
				'KEY(points)',
			),
				
			'userlimits' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'action' => "CHAR(1) CHARACTER SET ascii NOT NULL", // Q/A/C = post question/answer/comment, V=vote
				'period' => 'INT UNSIGNED NOT NULL', // integer representing hour of last action
				'count' => 'SMALLINT UNSIGNED NOT NULL', // how many of this action has been performed within that hour
				'UNIQUE (userid, action)',
			),
			
			// most columns in iplimits have the same meaning as those in userlimits
			
			'iplimits' => array(
				'ip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address
				'action' => "CHAR(1) CHARACTER SET ascii NOT NULL",
				'period' => 'INT UNSIGNED NOT NULL',
				'count' => 'SMALLINT UNSIGNED NOT NULL',
				'UNIQUE (ip, action)',
			),
				
			'options' => array(
				'title' => 'VARCHAR('.QA_DB_MAX_OPTION_TITLE_LENGTH.') NOT NULL', // name of option
				'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.') NOT NULL', // value of option
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
/*
	Return $array with all keys converted to lower case
*/
	{
		$keyarray=array();

		foreach ($array as $value)
			$keyarray[strtolower($value)]=true;
			
		return $keyarray;
	}

	
	function qa_db_missing_tables($db, $definitions)
/*
	Return a list of tables missing from the database, [table name] => [column/index definitions]
*/
	{
		$keydbtables=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_raw($db, 'SHOW TABLES')));
		
		$missing=array();
		
		foreach ($definitions as $rawname => $definition)
			if (!isset($keydbtables[strtolower(QA_MYSQL_TABLE_PREFIX.$rawname)]))
				$missing[$rawname]=$definition;
		
		return $missing;
	}

	
	function qa_db_missing_columns($db, $table, $definition)
/*
	Return a list of columns missing from $table in the database, given the full definition set in $definition
*/
	{
		$keycolumns=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_sub($db, 'SHOW COLUMNS FROM ^'.$table)));
		
		$missing=array();
		
		foreach ($definition as $colname => $coldefn)
			if ( (!is_int($colname)) && !isset($keycolumns[strtolower($colname)]) )
				$missing[$colname]=$coldefn;
				
		return $missing;
	}

	
	function qa_db_get_db_version($db)
/*
	Return the current version of the database, to determine need for DB upgrades
*/
	{
		$definitions=qa_db_table_definitions();
		
		if (count(qa_db_missing_columns($db, 'options', $definitions['options']))==0) {
			$version=(int)qa_db_read_one_value(qa_db_query_sub($db, "SELECT content FROM ^options WHERE title='db_version'"), true);
			
			if ($version>0)
				return $version;
		}
			
		return null;
	}

	
	function qa_db_set_db_version($db, $version)
/*
	Set the current version in the database
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title,content) VALUES ('db_version', #)", $version);
	}

	
	function qa_db_check_tables($db)
/*
	Return a string describing what is wrong with the database, or false if everything is just fine
*/
	{
		$version=qa_db_read_one_value(qa_db_query_raw($db, 'SELECT VERSION()'));
		
		if (((float)$version)<4.1)
			qa_fatal_error('MySQL version 4.1 or later is required - you appear to be running MySQL '.$version);
		
		$definitions=qa_db_table_definitions();
		$missing=qa_db_missing_tables($db, $definitions);
		
		if (count($missing) == count($definitions))
			return 'none';
		
		else {
			if (!isset($missing['options'])) {
				$version=qa_db_get_db_version($db);
				
				if (isset($version) && ($version<QA_DB_VERSION_CURRENT))
					return 'old-version';
			}
		
			if (count($missing))
				return 'table-missing';
				
			else
				foreach ($definitions as $table => $definition)
					if (count(qa_db_missing_columns($db, $table, $definition)))
						return 'column-missing';
		}
				
		return false;
	}

	
	function qa_db_install_tables($db)
/*
	Install any missing database tables and/or columns and automatically set version as latest.
	This is not suitable for use if the database needs upgrading.
*/
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
		
		qa_db_set_db_version($db, QA_DB_VERSION_CURRENT);
	}

	
	function qa_db_upgrade_tables($db)
/*
	Upgrade the database schema to the latest version, outputting progress to the browser
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-recalc.php';
		
		$definitions=qa_db_table_definitions();
		$keyrecalc=array();
		
	//	Write-lock all QA tables before we start so no one can read or write anything

		$tables=qa_db_read_all_values(qa_db_query_raw($db, 'SHOW TABLES'));

		$locks=array();
		foreach ($tables as $table)
			if (strpos($table, QA_MYSQL_TABLE_PREFIX)===0) // could have other tables not belonging to QA
				$locks[]=$table.' WRITE';
			
		qa_db_upgrade_query($db, 'LOCK TABLES '.implode(', ', $locks));
		
	//	Upgrade it step-by-step until it's up to date

		while (1) {
			$version=qa_db_get_db_version($db);
			
			if ($version>=QA_DB_VERSION_CURRENT)
				break;
			
			$newversion=$version+1;
			
			qa_db_upgrade_progress(QA_DB_VERSION_CURRENT-$version.' upgrade step/s remaining...');
			
			switch ($newversion) {
			
			//	Up to here: Version 1.0 beta 1
				
				case 2:
					qa_db_upgrade_query($db, 'ALTER TABLE ^posts DROP COLUMN votes');
					qa_db_upgrade_query($db, 'ALTER TABLE ^posts ADD COLUMN (upvotes '.$definitions['posts']['upvotes'].
						', downvotes '.$definitions['posts']['downvotes'].')');
					$keyrecalc['dorecountposts']=true;
					break;
					
				case 3:
					qa_db_upgrade_query($db, 'ALTER TABLE ^userpoints ADD COLUMN (upvoteds '.$definitions['userpoints']['upvoteds'].
						', downvoteds '.$definitions['userpoints']['downvoteds'].')');
					$keyrecalc['dorecalcpoints']=true;
					break;
					
				case 4:
					qa_db_upgrade_query($db, 'ALTER TABLE ^posts ADD COLUMN lastuserid '.$definitions['posts']['lastuserid']);
					qa_db_upgrade_query($db, 'ALTER TABLE ^posts CHANGE COLUMN updated updated '.$definitions['posts']['updated']);
					qa_db_upgrade_query($db, 'UPDATE ^posts SET updated=NULL WHERE updated=0 OR updated=created');
					break;
					
				case 5:
					qa_db_upgrade_query($db, 'ALTER TABLE ^contentwords ADD COLUMN (type '.$definitions['contentwords']['type'].', questionid '.$definitions['contentwords']['questionid'].')');
					$keyrecalc['doreindexposts']=true;
					break;
					
			//	Up to here: Version 1.0 beta 2
				
				case 6:
					qa_db_upgrade_query($db, 'ALTER TABLE ^userpoints ADD COLUMN cposts '.$definitions['userpoints']['cposts']);
					$keyrecalc['dorecalcpoints']=true;
					break;
					
				case 7:
					if (!QA_EXTERNAL_USERS)
						qa_db_upgrade_query($db, 'ALTER TABLE ^users ADD COLUMN sessioncode '.$definitions['users']['sessioncode']);
					break;
					
				case 8:
					qa_db_upgrade_query($db, 'ALTER TABLE ^posts ADD KEY (type, acount, created)');
					$keyrecalc['dorecountposts']=true; // for unanswered question count
					break;

			//	Up to here: Version 1.0 beta 3

			}
			
			qa_db_set_db_version($db, $newversion);
			
			if (qa_db_get_db_version($db)!=$newversion)
				qa_fatal_error('Could not increment database version');
		}
		
		qa_db_upgrade_query($db, 'UNLOCK TABLES');

	//	Perform any necessary recalculations, as determined by upgrade steps
		
		foreach ($keyrecalc as $state => $dummy)
			while ($state) {
				set_time_limit(60);
			
				$stoptime=time()+2;
				
				while ( qa_recalc_perform_step($db, $state) && (time()<$stoptime) )
					;
				
				qa_db_upgrade_progress(qa_recalc_get_message($state));
			}
	}

	
	function qa_db_upgrade_query($db, $query)
/*
	Perform upgrade $query and output progress to the browser
*/
	{
		qa_db_upgrade_progress('Running query: '.strtr($query, array('^' => QA_MYSQL_TABLE_PREFIX)).' ...');
		qa_db_query_sub($db, $query);
	}

	
	function qa_db_upgrade_progress($text)
/*
	Output $text to the browser (after converting to HTML) and do all we can to get it displayed
*/
	{
		echo qa_html($text).str_repeat('    ', 1024)."<BR>\n";
		flush();
		sleep(1);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/