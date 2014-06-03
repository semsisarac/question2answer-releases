<?php
	
/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-selects.php
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

	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	
	function qa_db_select_with_pending($db)
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		$selectspecs=array_slice(func_get_args(), 1);
		
		foreach ($selectspecs as $key => $selectspec) // can pass null parameters
			if (empty($selectspec))
				unset($selectspecs[$key]);
		
		$singleresult=(count($selectspecs)==1);
		
		$optionselectspec=qa_options_pending_selectspec();
		if (is_array($optionselectspec))
			$selectspecs['_options']=$optionselectspec;
		
		$outresults=qa_db_multi_select($db, $selectspecs);
		
		if (is_array($optionselectspec))
			qa_options_load_options($outresults['_options']);
		
		return $singleresult ? $outresults[0] : $outresults;
	}
	
	function qa_db_posts_basic_selectspec($uservote=false, $full=false, $user=true)
	{
		$selectspec=array(
			'columns' => array(
				'^posts.postid', '^posts.type', 'basetype' => 'LEFT(^posts.type,1)', 'hidden' => "INSTR(^posts.type, '_HIDDEN')>0",
				'^posts.acount', '^posts.upvotes', '^posts.downvotes',
				'title' => 'BINARY ^posts.title', 'tags' => 'BINARY ^posts.tags', 'created' => 'UNIX_TIMESTAMP(^posts.created)',
			),
			
			'arraykey' => 'postid',			
			'source' => '^posts',
		);
		
		if ($uservote) {
			$selectspec['columns']['uservote']='^uservotes.vote';
			$selectspec['source'].=' LEFT JOIN ^uservotes ON ^posts.postid=^uservotes.postid AND ^uservotes.userid=$';
		}
		
		if ($full) {
			$selectspec['columns']['content']='BINARY ^posts.content';
			$selectspec['columns']['notify']='BINARY ^posts.notify';
			$selectspec['columns']['updated']='UNIX_TIMESTAMP(^posts.updated)';
			$selectspec['columns'][]='^posts.format';
			$selectspec['columns'][]='^posts.lastuserid';
			$selectspec['columns'][]='^posts.parentid';
			$selectspec['columns'][]='^posts.selchildid';
		};
				
		if ($user) {
			$selectspec['columns'][]='^posts.userid';
			$selectspec['columns'][]='^posts.cookieid';
			$selectspec['columns'][]='^userpoints.points';

			if (!QA_EXTERNAL_USERS) {
				$selectspec['columns']['handle']='BINARY ^users.handle';
				$selectspec['source'].=' LEFT JOIN ^users ON ^posts.userid=^users.userid';
				
				if ($full) {
					$selectspec['columns']['lasthandle']='BINARY lastusers.handle';
					$selectspec['source'].=' LEFT JOIN ^users AS lastusers ON ^posts.lastuserid=lastusers.userid';
				}
			}
				
			$selectspec['source'].=' LEFT JOIN ^userpoints ON ^posts.userid=^userpoints.userid';
		}
		
		return $selectspec;
	}
	
	function qa_db_recent_qs_selectspec($voteuserid, $start, $count=QA_DB_RETRIEVE_QS_AS)
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posts WHERE type='Q' ORDER BY ^posts.created DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		$selectspec['arguments']=array($voteuserid, $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}
	
	function qa_db_recent_a_qs_selectspec($voteuserid, $start, $count=QA_DB_RETRIEVE_QS_AS)
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['arraykey']='apostid';

		$selectspec['columns']['apostid']='aposts.postid';
		$selectspec['columns']['auserid']='aposts.userid';
		$selectspec['columns']['acookieid']='aposts.cookieid';
		$selectspec['columns']['acreated']='UNIX_TIMESTAMP(aposts.created)';
		$selectspec['columns']['apoints']='auserpoints.points';
		
		if (!QA_EXTERNAL_USERS)
			$selectspec['columns']['ahandle']='BINARY ausers.handle';
		
		$selectspec['source'].=" JOIN ^posts AS aposts ON ^posts.postid=aposts.parentid".
			(QA_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS ausers ON aposts.userid=ausers.userid").
			" LEFT JOIN ^userpoints AS auserpoints ON aposts.userid=auserpoints.userid".
			" JOIN (SELECT postid FROM ^posts WHERE type='A' ORDER BY ^posts.created DESC LIMIT #,#) y ON aposts.postid=y.postid WHERE ^posts.type!='Q_HIDDEN'";
			
		$selectspec['arguments']=array($voteuserid, $start, $count);
		$selectspec['sortdesc']='acreated';
		
		return $selectspec;
	}

	function qa_db_full_post_selectspec($voteuserid, $postid)
	{
		$selectspec=qa_db_posts_basic_selectspec(true, true);

		$selectspec['source'].=" WHERE ^posts.postid=#";
		$selectspec['arguments']=array($voteuserid, $postid);
		$selectspec['single']=true;

		return $selectspec;
	}
	
	function qa_db_full_child_posts_selectspec($voteuserid, $postid)
	{
		$selectspec=qa_db_posts_basic_selectspec(true, true);
		
		$selectspec['source'].=" WHERE ^posts.parentid=#";
		$selectspec['arguments']=array($voteuserid, $postid);
		
		return $selectspec;
	}

	function qa_db_full_a_child_posts_selectspec($voteuserid, $postid)
	{
		$selectspec=qa_db_posts_basic_selectspec(true, true);
		
		$selectspec['source'].=" JOIN ^posts AS parents ON ^posts.parentid=parents.postid WHERE parents.parentid=# AND (parents.type='A' OR parents.type='A_HIDDEN')" ;
		$selectspec['arguments']=array($voteuserid, $postid);
		
		return $selectspec;
	}
	
	function qa_db_post_parent_q_selectspec($postid)
	{
		$selectspec=qa_db_posts_basic_selectspec(false, false);
		
		$selectspec['source'].=" WHERE ^posts.postid=(SELECT IF((parent.type='A') OR (parent.type='A_HIDDEN'), parent.parentid, parent.postid) FROM ^posts AS child LEFT JOIN ^posts AS parent ON parent.postid=child.parentid WHERE child.postid=#)";
		$selectspec['arguments']=array($postid);
		$selectspec['single']=true;
		
		return $selectspec;
	}
	
	function qa_db_related_qs_selectspec($voteuserid, $postid, $count=QA_DB_RETRIEVE_QS_AS)
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['columns'][]='score';
		
		// added LOG(postid)/1000000 here to ensure ordering is deterministic even if several posts have same score
		
		$selectspec['source'].=" JOIN (SELECT postid, SUM(score)+LOG(postid)/1000000 AS score FROM ((SELECT ^titlewords.postid, LOG(#/titlecount) AS score FROM ^titlewords JOIN ^words ON ^titlewords.wordid=^words.wordid JOIN ^titlewords AS source ON ^titlewords.wordid=source.wordid WHERE source.postid=# AND titlecount<#) UNION ALL (SELECT ^posttags.postid, 2*LOG(#/tagcount) AS score FROM ^posttags JOIN ^words ON ^posttags.wordid=^words.wordid JOIN ^posttags AS source ON ^posttags.wordid=source.wordid WHERE source.postid=# AND tagcount<#)) x GROUP BY postid ORDER BY score DESC LIMIT #) y ON ^posts.postid=y.postid";
		
		$selectspec['arguments']=array($voteuserid, QA_IGNORED_WORDS_FREQ, $postid, QA_IGNORED_WORDS_FREQ,
			QA_IGNORED_WORDS_FREQ, $postid, QA_IGNORED_WORDS_FREQ, $count);
			
		$selectspec['sortdesc']='score';
			
		return $selectspec;
	}
	
	function qa_db_search_posts_selectspec($db, $voteuserid, $titlewords, $contentwords, $tagwords, $handlewords, $start, $count=QA_DB_RETRIEVE_QS_AS)
	{
		// add LOG(postid)/1000000 here to ensure ordering is deterministic even if several posts have same score

		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['columns'][]='score';
		$selectspec['columns'][]='matchparts';
		$selectspec['arguments']=array($voteuserid);
		$selectspec['source'].=" JOIN (SELECT questionid, SUM(score)+LOG(questionid)/1000000 AS score, GROUP_CONCAT(CONCAT_WS(':', matchpostid, ROUND(score,3))) AS matchparts FROM (";
		$selectspec['sortdesc']='score';
		
		$selectparts=0;
		
		if (!empty($titlewords)) {
			// At the indexing stage, duplicate words in title are ignored, so this doesn't count multiple appearances.
			
			$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
				"(SELECT postid AS questionid, LOG(#/titlecount) AS score, postid AS matchpostid FROM ^titlewords JOIN ^words ON ^titlewords.wordid=^words.wordid WHERE word IN ($) AND titlecount<#)";

			array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $titlewords, QA_IGNORED_WORDS_FREQ);
		}
		
		if (!empty($contentwords)) {
			// (1-1/(1+count)) weights words in content based on their frequency: If a word appears once in content
			// it's equivalent to 1/2 an appearance in the title (ignoring the contentcount/titlecount factor).
			// If it appears an infinite number of times, it's equivalent to one appearance in the title.
			// This will discourage keyword stuffing while still giving some weight to multiple appearances.
			// On top of that, answer matches are worth half a question match, and comment matches half again.
			
			$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
				"(SELECT questionid, (1-1/(1+count))*LOG(#/contentcount)*(CASE ^contentwords.type WHEN 'Q' THEN 1.0 WHEN 'A' THEN 0.5 ELSE 0.25 END) AS score, ^contentwords.postid AS matchpostid FROM ^contentwords JOIN ^words ON ^contentwords.wordid=^words.wordid WHERE word IN ($) AND contentcount<#)";

			array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $contentwords, QA_IGNORED_WORDS_FREQ);
		}
		
		if (!empty($tagwords)) {
			// Appearances in the tag list count like 2 appearances in the title (ignoring the tagcount/titlecount factor).
			// This is because tags express explicit semantic intent, whereas titles do not necessarily. 
			
			$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
				"(SELECT postid AS questionid, 2*LOG(#/tagcount) AS score, postid AS matchpostid FROM ^posttags JOIN ^words ON ^posttags.wordid=^words.wordid WHERE word IN ($) AND tagcount<#)";

			array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $tagwords, QA_IGNORED_WORDS_FREQ);
		}
		
		if (!empty($handlewords)) {
			if (QA_EXTERNAL_USERS) {
				$userids=qa_get_userids_from_public($db, $handlewords);
				
				if (count($userids)) {
					$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
						"(SELECT postid AS questionid, LOG(#/qposts) AS score, postid AS matchpostid FROM ^posts JOIN ^userpoints ON ^posts.userid=^userpoints.userid WHERE ^posts.userid IN ($) AND type='Q')";
					
					array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $userids);
				}

			} else {
				$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
					"(SELECT postid AS questionid, LOG(#/qposts) AS score, postid AS matchpostid FROM ^posts JOIN ^users ON ^posts.userid=^users.userid JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE handle IN ($) AND type='Q')";

				array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $handlewords);				
			}
		}
		
		if ($selectparts==0)
			$selectspec['source'].='(SELECT NULL as questionid, 0 AS score, NULL as matchpostid FROM ^posts WHERE postid=NULL)';

		$selectspec['source'].=") x GROUP BY questionid ORDER BY score DESC LIMIT #,#) y ON ^posts.postid=y.questionid";
		
		array_push($selectspec['arguments'], $start, $count);
		
		return $selectspec;
	}
	
	function qa_db_tag_recent_qs_selectspec($voteuserid, $tag, $start, $count=QA_DB_RETRIEVE_QS_AS)
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posttags WHERE wordid=(SELECT wordid FROM ^words WHERE word=$) ORDER BY postcreated DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		$selectspec['arguments']=array($voteuserid, $tag, $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}
	
	function qa_db_tag_count_qs_selectspec($tag)
	{
		return array(
			'columns' => array('tagcount'),
			'source' => '^words WHERE word=$',
			'arguments' => array($tag),
			'arrayvalue' => 'tagcount',
			'single' => true,
		);
	}
	
	function qa_db_user_recent_qs_selectspec($voteuserid, $identifier, $count=QA_DB_RETRIEVE_QS_AS)
	{
		$selectspec=qa_db_posts_basic_selectspec(true, false, false);
		
		$selectspec['source'].=" WHERE ^posts.userid=".(QA_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$)")." AND type='Q' ORDER BY ^posts.created DESC LIMIT #";
		$selectspec['arguments']=array($voteuserid, $identifier, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}
	
	function qa_db_user_recent_as_selectspec($identifier, $count=QA_DB_RETRIEVE_QS_AS)
	{
		$selectspec=qa_db_posts_basic_selectspec();
		
		$selectspec['columns']['apostid']='aposts.postid';
		$selectspec['columns']['acreated']='UNIX_TIMESTAMP(aposts.created)';
		
		$selectspec['source'].=" JOIN ^posts AS aposts ON ^posts.postid=aposts.parentid".
			(QA_EXTERNAL_USERS ? "" : " JOIN ^users AS ausers ON aposts.userid=ausers.userid").
			" WHERE aposts.userid=".(QA_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$)")." AND aposts.type='A'".
			" ORDER BY aposts.created DESC LIMIT #";
			
		$selectspec['arguments']=array($identifier, $count);
		$selectspec['sortdesc']='acreated';
		
		return $selectspec;
	}
		
	function qa_db_popular_tags_selectspec($start, $count=QA_DB_RETRIEVE_TAGS)
	{
		return array(
			'columns' => array('word' => 'BINARY word', 'tagcount'),
			'source' => '^words JOIN (SELECT wordid FROM ^words WHERE tagcount>0 ORDER BY tagcount DESC LIMIT #,#) y ON ^words.wordid=y.wordid',
			'arguments' => array($start, $count),
			'arraykey' => 'word',
			'arrayvalue' => 'tagcount',
			'sortdesc' => 'tagcount',
		);
	}

	function qa_db_user_account_selectspec($useridhandle, $isuserid)
	{
		return array(
			'columns' => array(
				'userid', 'passsalt', 'passcheck' => 'HEX(passcheck)', 'email' => 'BINARY email', 'level', 'handle' => 'BINARY handle', 'resetcode',
				'created' => 'UNIX_TIMESTAMP(created)'
			),
			
			'source' => '^users WHERE '.($isuserid ? 'userid' : 'handle').'=$',
			'arguments' => array($useridhandle),
			'single' => true,
		);	
	}
	
	function qa_db_user_profile_selectspec($useridhandle, $isuserid)
	{
		return array(
			'columns' => array('title' => 'BINARY title', 'content' => 'BINARY content'),
			'source' => '^userprofile WHERE userid='.($isuserid ? '$' : '(SELECT userid FROM ^users WHERE handle=$)'),
			'arguments' => array($useridhandle),
			'arraykey' => 'title',
			'arrayvalue' => 'content',
		);
	}
	
	function qa_db_user_points_selectspec($identifier)
	{
		return array(
			'columns' => array('points', 'qposts', 'aposts', 'aselects', 'aselecteds', 'qvotes', 'avotes', 'qvoteds', 'avoteds', 'upvoteds', 'downvoteds'),
			'source' => '^userpoints WHERE userid='.(QA_EXTERNAL_USERS ? '$' : '(SELECT userid FROM ^users WHERE handle=$)'),
			'arguments' => array($identifier),
			'single' => true,
		);
	}
	
	function qa_db_user_rank_selectspec($identifier)
	{
		return array(
			'columns' => array('rank' => '1+COUNT(*)'),
			'source' => '^userpoints WHERE points>COALESCE((SELECT points FROM ^userpoints WHERE userid='.(QA_EXTERNAL_USERS ? '$' : '(SELECT userid FROM ^users WHERE handle=$)').'), 0)',
			'arguments' => array($identifier),
			'arrayvalue' => 'rank',
			'single' => true,
		);
	}
	
	function qa_db_top_users_selectspec($start, $count=QA_DB_RETRIEVE_USERS)
	{
		if (QA_EXTERNAL_USERS)
			return array(
				'columns' => array('userid', 'points', 'qposts', 'aposts'),
				'source' => '^userpoints ORDER BY points DESC LIMIT #,#',
				'arguments' => array($start, $count),	
				'arraykey' => 'userid',
				'sortdesc' => 'points',
			);
		
		else
			return array(
				'columns' => array('^users.userid', 'handle' => 'BINARY handle', 'points', 'qposts', 'aposts'),
				'source' => '^users JOIN (SELECT userid FROM ^userpoints ORDER BY points DESC LIMIT #,#) y ON ^users.userid=y.userid JOIN ^userpoints ON ^users.userid=^userpoints.userid',
				'arguments' => array($start, $count),	
				'arraykey' => 'userid',		
				'sortdesc' => 'points',
			);
	}
	
	function qa_db_users_from_level_selectspec($level)
	{
		return array(
			'columns' => array('^users.userid', 'handle' => 'BINARY handle', 'level'),
			'source' => '^users WHERE level>=# ORDER BY level DESC',
			'arguments' => array($level),
			'sortdesc' => 'level',
		);
	}
	
	function qa_db_options_cache_selectspec($title)
	{
		return array(
			'columns' => array('content'),
			'source' => '^options WHERE title=$',
			'arguments' => array($title),
			'arrayvalue' => 'content',
			'single' => true,
		);
	}
	
?>