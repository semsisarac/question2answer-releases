<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-points.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Database-level access to user points and statistics


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


	function qa_db_points_option_names()
/*
	Returns an array of option names required to perform calculations in userpoints table
*/
	{
		return array(
			'points_post_q', 'points_select_a', 'points_per_q_voted', 'points_q_voted_max_gain', 'points_q_voted_max_loss',
			'points_post_a', 'points_a_selected', 'points_per_a_voted', 'points_a_voted_max_gain', 'points_a_voted_max_loss',
			'points_vote_up_q', 'points_vote_down_q', 'points_vote_up_a', 'points_vote_down_a',
			
			'points_multiple', 'points_base',
		);
	}

	
	function qa_db_points_calculations($db)
/*
	Returns an array containing all the calculation formulae for the userpoints table. Each element of this
	array is for one column - the key contains the column name, and the value is a further array of two elements.
	The element 'formula' contains the SQL fragment that calculates the columns value for one or more users,
	where the ~ symbol within the fragment is substituted for a constraint on which users we are interested in.
	The element 'multiple' specifies what to multiply each column by to create the final sum in the points column.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		$options=qa_get_options($db, qa_db_points_option_names());
		
		return array(
			'qposts' => array(
				'multiple' => $options['points_multiple']*$options['points_post_q'],
				'formula' => "COUNT(*) AS qposts FROM ^posts AS userid_src WHERE userid~ AND type='Q'",
			),
			
			'aposts' => array(
				'multiple' => $options['points_multiple']*$options['points_post_a'],
				'formula' => "COUNT(*) AS aposts FROM ^posts AS userid_src WHERE userid~ AND type='A'",
			),
			
			'cposts' => array(
				'multiple' => 0,
				'formula' => "COUNT(*) AS cposts FROM ^posts AS userid_src WHERE userid~ AND type='C'",
			),
			
			'aselects' => array(
				'multiple' => $options['points_multiple']*$options['points_select_a'],
				'formula' => "COUNT(*) AS aselects FROM ^posts AS userid_src WHERE userid~ AND type='Q' AND selchildid IS NOT NULL",
			),
			
			'aselecteds' => array(
				'multiple' => $options['points_multiple']*$options['points_a_selected'],
				'formula' => "COUNT(*) AS aselecteds FROM ^posts AS userid_src JOIN ^posts AS questions ON questions.selchildid=userid_src.postid WHERE userid_src.userid~ AND userid_src.type='A' AND NOT (questions.userid<=>userid_src.userid)",
			),
			
			'qupvotes' => array(
				'multiple' => $options['points_multiple']*$options['points_vote_up_q'],
				'formula' => "COUNT(*) AS qupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND (^posts.type='Q' OR ^posts.type='Q_HIDDEN') AND userid_src.vote>0",
			),
			
			'qdownvotes' => array(
				'multiple' => $options['points_multiple']*$options['points_vote_down_q'],
				'formula' => "COUNT(*) AS qdownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND (^posts.type='Q' OR ^posts.type='Q_HIDDEN') AND userid_src.vote<0",
			),
			
			'aupvotes' => array(
				'multiple' => $options['points_multiple']*$options['points_vote_up_a'],
				'formula' => "COUNT(*) AS aupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND (^posts.type='A' OR ^posts.type='A_HIDDEN') AND userid_src.vote>0",
			),
			
			'adownvotes' => array(
				'multiple' => $options['points_multiple']*$options['points_vote_down_a'],
				'formula' => "COUNT(*) AS adownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND (^posts.type='A' OR ^posts.type='A_HIDDEN') AND userid_src.vote<0",
			),
			
			'qvoteds' => array(
				'multiple' => $options['points_multiple'],
				'formula' => "COALESCE(SUM(".
					"LEAST(".((int)$options['points_per_q_voted'])."*upvotes,".((int)$options['points_q_voted_max_gain']).")".
					"-".
					"LEAST(".((int)$options['points_per_q_voted'])."*downvotes,".((int)$options['points_q_voted_max_loss']).")".
					"), 0) AS qvoteds FROM ^posts AS userid_src WHERE (type='Q' OR type='Q_HIDDEN') AND userid~",
			),
			
			'avoteds' => array(
				'multiple' => $options['points_multiple'],
				'formula' => "COALESCE(SUM(".
					"LEAST(".((int)$options['points_per_a_voted'])."*upvotes,".((int)$options['points_a_voted_max_gain']).")".
					"-".
					"LEAST(".((int)$options['points_per_a_voted'])."*downvotes,".((int)$options['points_a_voted_max_loss']).")".
					"), 0) AS avoteds FROM ^posts AS userid_src WHERE (type='A' OR type='A_HIDDEN') AND userid~",
			),
			
			'upvoteds' => array(
				'multiple' => 0,
				'formula' => "COALESCE(SUM(upvotes), 0) AS upvoteds FROM ^posts AS userid_src WHERE userid~",
			),

			'downvoteds' => array(
				'multiple' => 0,
				'formula' => "COALESCE(SUM(downvotes), 0) AS downvoteds FROM ^posts AS userid_src WHERE userid~",
			),
		);
	}

	
	function qa_db_points_update_ifuser($db, $userid, $columns)
/*
	Update the userpoints table in the database for $userid and $columns, plus the summary points column.
	Set $columns to true for all, empty for none, an array for several, or a single value for one.
	This dynamically builds some fairly crazy looking SQL, but it works, and saves repeat calculations.
*/
	{
		if (isset($userid)) {
			require_once QA_INCLUDE_DIR.'qa-app-options.php';

			$calculations=qa_db_points_calculations($db);
			
			if ($columns===true)
				$keycolumns=$calculations;
			elseif (empty($columns))
				$keycolumns=array();
			elseif (is_array($columns))
				$keycolumns=array_flip($columns);
			else
				$keycolumns=array($columns => true);
			
			$insertfields='userid, ';
			$insertvalues='$, ';
			$insertpoints=(int)qa_get_option($db, 'points_base');

			$updates='';
			$updatepoints=$insertpoints;
			
			foreach ($calculations as $field => $calculation) {
				$multiple=(int)$calculation['multiple'];
				
				if (isset($keycolumns[$field])) {
					$insertfields.=$field.', ';
					$insertvalues.='@_'.$field.':=(SELECT '.$calculation['formula'].'), ';
					$updates.=$field.'=@_'.$field.', ';
					$insertpoints.='+('.$multiple.'*@_'.$field.')';
				}
				
				$updatepoints.='+('.$multiple.'*'.(isset($keycolumns[$field]) ? '@_' : '').$field.')';
			}
			
			$query='INSERT INTO ^userpoints ('.$insertfields.'points) VALUES ('.$insertvalues.$insertpoints.') '.
				'ON DUPLICATE KEY UPDATE '.$updates.'points='.$updatepoints;
			
			qa_db_query_sub($db, str_replace('~', "=_utf8 '".mysql_real_escape_string($userid, $db)."'", $query), $userid);
			
			if (qa_db_insert_on_duplicate_inserted($db))
				qa_db_userpointscount_update($db);
		}
	}


	function qa_db_userpointscount_update($db)
/*
	Update the cached count in the database of the number of rows in the userpoints table
*/
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_userpointscount', COUNT(*) FROM ^userpoints");
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/