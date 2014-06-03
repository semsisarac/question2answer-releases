<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-points.php
	Version: 1.0-beta-2
	Date: 2010-03-08 13:08:01 GMT


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

	function qa_db_points_option_names()
	{
		return array(
			'points_post_q', 'points_select_a', 'points_per_q_voted', 'points_q_voted_max_gain', 'points_q_voted_max_loss', 
			'points_post_a', 'points_a_selected', 'points_per_a_voted', 'points_a_voted_max_gain', 'points_a_voted_max_loss',
			'points_vote_on_q', 'points_vote_on_a',
			
			'points_multiple', 'points_base',
		);
	}
	
	function qa_db_points_calculations($db)
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
			
			'aselects' => array(
				'multiple' => $options['points_multiple']*$options['points_select_a'],
				'formula' => "COUNT(*) AS aselects FROM ^posts AS userid_src WHERE userid~ AND type='Q' AND selchildid IS NOT NULL",
			),
			
			'aselecteds' => array(
				'multiple' => $options['points_multiple']*$options['points_a_selected'],
				'formula' => "COUNT(*) AS aselecteds FROM ^posts AS userid_src JOIN ^posts AS questions ON questions.selchildid=userid_src.postid WHERE userid_src.userid~ AND userid_src.type='A' AND NOT (questions.userid<=>userid_src.userid)",
			),
			
			'qvotes' => array(
				'multiple' => $options['points_multiple']*$options['points_vote_on_q'],
				'formula' => "COUNT(*) AS qvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND (^posts.type='Q' OR ^posts.type='Q_HIDDEN') AND userid_src.vote!=0",
			),
			
			'avotes' => array(
				'multiple' => $options['points_multiple']*$options['points_vote_on_a'],
				'formula' => "COUNT(*) AS avotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND (^posts.type='A' OR ^posts.type='A_HIDDEN') AND userid_src.vote!=0",
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
	
	function qa_db_points_update_ifuser($db, $userid, $fields)
	{
		if (isset($userid)) {
			require_once QA_INCLUDE_DIR.'qa-app-options.php';

			$calculations=qa_db_points_calculations($db);
			
			if ($fields===true)
				$keyfields=$calculations;
			elseif (empty($fields))
				$keyfields=array();
			elseif (is_array($fields))
				$keyfields=array_flip($fields);
			else
				$keyfields=array($fields => true);
			
			$insertfields='userid, ';
			$insertvalues='$, ';
			$insertpoints=(int)qa_get_option($db, 'points_base');

			$updates='';
			$updatepoints=$insertpoints;
			
			foreach ($calculations as $field => $calculation) {
				$multiple=(int)$calculation['multiple'];
				
				if (isset($keyfields[$field])) {
					$insertfields.=$field.', ';
					$insertvalues.='@_'.$field.':=(SELECT '.$calculation['formula'].'), ';
					$updates.=$field.'=@_'.$field.', ';
					$insertpoints.='+('.$multiple.'*@_'.$field.')';
				}
				
				$updatepoints.='+('.$multiple.'*'.(isset($keyfields[$field]) ? '@_' : '').$field.')';
			}
			
			$query='INSERT INTO ^userpoints ('.$insertfields.'points) VALUES ('.$insertvalues.$insertpoints.') '.
				'ON DUPLICATE KEY UPDATE '.$updates.'points='.$updatepoints;
			
			qa_db_query_sub($db, str_replace('~', "=_utf8 '".mysql_real_escape_string($userid, $db)."'", $query), $userid);
			
			if (qa_db_insert_on_duplicate_inserted($db))
				qa_db_userpointscount_update($db);
		}
	}

	function qa_db_userpointscount_update($db)
	{
		qa_db_query_sub($db, "REPLACE ^options (title, content) SELECT 'cache_userpointscount', COUNT(*) FROM ^userpoints");
	}

?>