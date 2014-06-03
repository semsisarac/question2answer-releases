<?php

/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db.php
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

	function qa_db_connect($failhandler)
	{
		global $qa_db_fail_handler;
		
		$qa_db_fail_handler=$failhandler;
		
		if (QA_PERSISTENT_CONN_DB)
			$connection=mysql_pconnect(QA_MYSQL_HOSTNAME, QA_MYSQL_USERNAME, QA_MYSQL_PASSWORD);
		else
			$connection=mysql_connect(QA_MYSQL_HOSTNAME, QA_MYSQL_USERNAME, QA_MYSQL_PASSWORD);
		
		if (is_resource($connection)) {
			if (!mysql_select_db(QA_MYSQL_DATABASE, $connection))
				$qa_db_fail_handler('select');
				
		} else
			$qa_db_fail_handler('connect');
		
		return $connection;
	}
	
	function qa_db_disconnect($db)
	{
		if (!QA_PERSISTENT_CONN_DB) {
			if (is_resource($db)) {
	
				if (!mysql_close($db))
					qa_fatal_error('Database disconnect failed');
				
			} else
				qa_fatal_error('Database connection invalid');
		}
	}
	
	function qa_db_query_raw($db, $query)
	{
		global $qa_db_fail_handler;
		
		//error_log($query);
		
		if (QA_DEBUG_PERFORMANCE) {
			global $qa_database_usage, $qa_database_queries;
			
			$oldtime=array_sum(explode(' ', microtime()));
			$result=mysql_query($query, $db);
			$usedtime=array_sum(explode(' ', microtime()))-$oldtime;
			$qa_database_usage['clock']+=$usedtime;
	
			if (strlen($qa_database_queries)<1048576) // don't keep track of big tests
				$qa_database_queries.=$query."\n\n".sprintf('%.2f ms', $usedtime*1000)."\n\n";
		
			$qa_database_usage['queries']++;
		
		} else
			$result=mysql_query($query, $db);
	
		if ($result===false)
			$qa_db_fail_handler('query', mysql_errno($db), mysql_error($db), $query);
			
		return $result;
	}
	
	function qa_db_argument_to_mysql($db, $argument, $alwaysquote, $arraybrackets=false)
	{
		if (is_array($argument)) {
			$parts=array();
			
			foreach ($argument as $subargument)
				$parts[]=qa_db_argument_to_mysql($db, $subargument, $alwaysquote, true);
			
			if ($arraybrackets)
				$result='('.implode(',', $parts).')';
			else
				$result=implode(',', $parts);
		
		} elseif (isset($argument)) {
			if ($alwaysquote || !is_numeric($argument)) // use _utf8 introducer to save having to set charset of connection
				$result="_utf8 '".mysql_real_escape_string($argument, $db)."'";
			else
				$result=mysql_real_escape_string($argument, $db);
		
		} else
			$result='NULL';
		
		return $result;
	}
	
	function qa_db_apply_sub($db, $query, $arguments)
	{
		$query=strtr($query, array('^' => QA_MYSQL_TABLE_PREFIX));
		
		$countargs=count($arguments);
		$offset=0;
		
		for ($argument=0; $argument<$countargs; $argument++) {
			$stringpos=strpos($query, '$', $offset);
			$numberpos=strpos($query, '#', $offset);
			
			if ( ($stringpos===false) || ( ($numberpos!==false) && ($numberpos<$stringpos) ) ) {
				$alwaysquote=false;
				$position=$numberpos;
			
			} else {
				$alwaysquote=true;
				$position=$stringpos;
			}
			
			if (!is_numeric($position))
				qa_fatal_error('Insufficient parameters in query: '.$query);
			
			$value=qa_db_argument_to_mysql($db, $arguments[$argument], $alwaysquote);
			$query=substr_replace($query, $value, $position, 1);
			$offset=$position+strlen($value); // allows inserting strings which contain #/$ character
		}
		
		return $query;
	}
	
	function qa_db_query_sub($db, $query)
	{
		$funcargs=func_get_args();

		return qa_db_query_raw($db, qa_db_apply_sub($db, $query, array_slice($funcargs, 2)));
	}
	
	function qa_db_last_insert_id($db)
	{
		return qa_db_read_one_value(qa_db_query_raw($db, 'SELECT LAST_INSERT_ID()'));
	}
	
	function qa_db_insert_on_duplicate_inserted($db)
	{
		return mysql_affected_rows($db)==1;
	}
	
	function qa_db_random_bigint()
	{
		// Actual limit is 18,446,744,073,709,551,615 - we aim for 18,446,743,999,999,999,999

		return sprintf('%d%06d%06d', mt_rand(1,18446743), mt_rand(0,999999), mt_rand(0,999999));
	}
	
	function qa_db_single_select($db, $selectspec)
	{
		$query='SELECT ';
		
		foreach ($selectspec['columns'] as $columnas => $columnfrom)
			$query.=$columnfrom.(is_int($columnas) ? '' : (' AS '.$columnas)).', ';
		
		$results=qa_db_read_all_assoc(qa_db_query_raw($db, qa_db_apply_sub($db,
			substr($query, 0, -2).' FROM '.$selectspec['source'], $selectspec['arguments'])
		), @$selectspec['arraykey']); // arrayvalue is applied in qa_db_post_select()
		
		qa_db_post_select($results, $selectspec);

		return $results;
	}
	
	function qa_db_multi_select($db, $selectspecs)
	{
	/*
		Why all this craziness? Why combine possibly unrelated SELECT statements into a single query?
		
		Because if the database and web servers are on different computers, there will be latency.
		This way we ensure that every read pageview on the site requires only a single DB query, so
		that we pay for this latency only one time.
		
		For writes we worry less, since the user is more likely to be expecting a delay.
		
		If QA_OPTIMIZE_LOCAL_DB is set in qa-config.php, we assume zero latency and go back to
		simple queries, since this will allow both MySQL and PHP to provide quicker results.
	*/
	
	//	Perform simple queries if the database is local or there are only 0 or 1 selectspecs

		if (QA_OPTIMIZE_LOCAL_DB || (count($selectspecs)<=1)) {
			$outresults=array();			
		
			foreach ($selectspecs as $selectkey => $selectspec)
				$outresults[$selectkey]=qa_db_single_select($db, $selectspec);
				
			return $outresults;
		}

	//	Parse columns for each spec to deal with columns without an 'AS' specification
	
		foreach ($selectspecs as $selectkey => $selectspec) {
			$selectspecs[$selectkey]['outcolumns']=array();
			$selectspecs[$selectkey]['autocolumn']=array();
			
			foreach ($selectspec['columns'] as $columnas => $columnfrom) {
				if (is_int($columnas)) {
					$periodpos=strpos($columnfrom, '.');
					$columnas=is_numeric($periodpos) ? substr($columnfrom, $periodpos+1) : $columnfrom;
					$selectspecs[$selectkey]['autocolumn'][$columnas]=true;
				}
				
				if (isset($selectspecs[$selectkey]['outcolumns'][$columnas]))
					qa_fatal_error('Duplicate column name in qa_db_multi_select()');
				
				$selectspecs[$selectkey]['outcolumns'][$columnas]=$columnfrom;
			}
			
			if (isset($selectspec['arraykey']))
				if (!isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arraykey']]))
					qa_fatal_error('Used arraykey not in columns in qa_db_multi_select()');

			if (isset($selectspec['arrayvalue']))
				if (!isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arrayvalue']]))
					qa_fatal_error('Used arrayvalue not in columns in qa_db_multi_select()');
		}
			
	//	Work out the full list of columns used
		
		$outcolumns=array();
		foreach ($selectspecs as $selectspec)
			$outcolumns=array_unique(array_merge($outcolumns, array_keys($selectspec['outcolumns'])));
	
	//	Build the query based on this full list
	
		$query='';
		foreach ($selectspecs as $selectkey => $selectspec) {
			$subquery="(SELECT '".mysql_real_escape_string($selectkey, $db)."'".(empty($query) ? ' AS selectkey' : '');
			
			foreach ($outcolumns as $columnas) {
				$subquery.=', '.(isset($selectspec['outcolumns'][$columnas]) ? $selectspec['outcolumns'][$columnas] : 'NULL');
				
				if (empty($query) && !isset($selectspec['autocolumn'][$columnas]))
					$subquery.=' AS '.$columnas;
			}
			
			$subquery.=' FROM '.$selectspec['source'].')';
			
			if (strlen($query))
				$query.=' UNION ALL ';
				
			$query.=qa_db_apply_sub($db, $subquery, isset($selectspec['arguments']) ? $selectspec['arguments'] : array());
		}
		
	//	Perform query and extract results
		
		$rawresults=qa_db_read_all_assoc(qa_db_query_raw($db, $query));
		
		$outresults=array();
		foreach ($selectspecs as $selectkey => $selectspec)
			$outresults[$selectkey]=array();
			
		foreach ($rawresults as $rawresult) {
			$selectkey=$rawresult['selectkey'];
			$selectspec=$selectspecs[$selectkey];

			foreach ($rawresult as $columnas => $columnvalue)
				if (!isset($selectspec['outcolumns'][$columnas]))
					unset($rawresult[$columnas]);
			
			if (isset($selectspec['arraykey']))
				$outresults[$selectkey][$rawresult[$selectspec['arraykey']]]=$rawresult;
			else
				$outresults[$selectkey][]=$rawresult;
		}
		
	//	Post-processing to apply sorting request
		
		foreach ($selectspecs as $selectkey => $selectspec) // can't rely on ORDER BY due to UNION
			qa_db_post_select($outresults[$selectkey], $selectspec);
			
	//	Return results
	
		return $outresults;
	}
	
	function qa_db_post_select(&$outresult, $selectspec)
	{
		if (isset($selectspec['sortdesc'])) {
			// PHP's sorting algorithm is not 'stable', so we use _order_ to keep stability.
			// By contrast, MySQL's ORDER BY seems to be stable. For example when retrieving
			// most popular tags, we get the same results each time we run query, even if many
			// of the tags have the same popularity.
			
			$index=count($outresult);
			foreach ($outresult as $key => $value)
				$outresult[$key]['_order_']=$index--;
				
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';
			
			qa_sort_by($outresult, $selectspec['sortdesc'], '_order_');
			$outresult=array_reverse($outresult, true);
		}
		
		if (isset($selectspec['arrayvalue']))
			foreach ($outresult as $key => $value)
				$outresult[$key]=$value[$selectspec['arrayvalue']];
		
		if (@$selectspec['single'])
			$outresult=count($outresult) ? reset($outresult) : null;
	}
	
	function qa_db_read_all_assoc($result, $key=null, $value=null)
	{
		if (!is_resource($result))
			qa_fatal_error('Reading all assoc from invalid result');
			
		$assocs=array();
		
		while ($assoc=mysql_fetch_assoc($result)) {
			if (isset($key))
				$assocs[$assoc[$key]]=isset($value) ? $assoc[$value] : $assoc;
			else
				$assocs[]=isset($value) ? $assoc[$value] : $assoc;
		}
			
		return $assocs;
	}
	
	function qa_db_read_one_assoc($result, $allowempty=false)
	{
		if (!is_resource($result))
			qa_fatal_error('Reading one assoc from invalid result');

		$assoc=mysql_fetch_assoc($result);
		
		if (!is_array($assoc)) {
			if ($allowempty)
				return null;
			else
				qa_fatal_error('Reading one assoc from empty results');
		}
		
		return $assoc;
	}
	
	function qa_db_read_all_values($result)
	{
		if (!is_resource($result))
			qa_fatal_error('Reading column from invalid result');
			
		$output=array();
		
		while ($row=mysql_fetch_row($result))
			$output[]=$row[0];
		
		return $output;
	}
	
	function qa_db_read_one_value($result, $allowempty=false)
	{
		if (!is_resource($result))
			qa_fatal_error('Reading one value from invalid result');

		$row=mysql_fetch_row($result);
		
		if (!is_array($row)) {
			if ($allowempty)
				return null;
			else
				qa_fatal_error('Reading one value from empty results');
		}
			
		return $row[0];
	}
	
?>