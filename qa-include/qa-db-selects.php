<?php
	
/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-selects.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Builders of selectspec arrays (see qa-db.php) used to specify database SELECTs


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

	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';

	
	function qa_db_select_with_pending($db) // any number of extra parameters read via func_get_args()
/*
	Return the results of all the SELECT operations specified by the supplied selectspec parameters, while also
	loading any options which have been queued for retrieval. Uses one DB query unless QA_OPTIMIZE_LOCAL_DB is true.
	If only one parameter (after $db) is supplied, return its result, otherwise return an array of results.
*/
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
			qa_options_load_options($db, $outresults['_options']);
		
		return $singleresult ? $outresults[0] : $outresults;
	}

	
	function qa_db_posts_basic_selectspec($uservote=false, $full=false, $user=true)
/*
	Return the common selectspec used to build any selectspecs which retrieve posts from the database.
	If $uservote is true, enable retrieving the vote made by a particular user on each post (if so, the
	userid must be given as the first item in the 'arguments' element of the selectspec based on this).
	If $full is true, get full information on the posts, instead of just information for listing pages.
	If $user is true, get information about the user who wrote the post (or cookie if anonymous).
*/
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

	
	function qa_db_recent_qs_selectspec($voteuserid, $start, $hidden=false, $count=QA_DB_RETRIEVE_QS_AS)
/*
	Return the selectspec to retrieve $count recent questions ($hidden or not),
	starting from offset $start, with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posts WHERE type=$ ORDER BY ^posts.created DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		$selectspec['arguments']=array($voteuserid, $hidden ? 'Q_HIDDEN' : 'Q', $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}

	
	function qa_db_unanswered_qs_selectspec($voteuserid, $start, $hidden=false, $count=QA_DB_RETRIEVE_QS_AS)
/*
	Return the selectspec to retrieve $count recent unanswered questions ($hidden or not),
	starting from offset $start, with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posts WHERE type=$ AND acount=0 ORDER BY ^posts.created DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		$selectspec['arguments']=array($voteuserid, $hidden ? 'Q_HIDDEN' : 'Q', $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}


	function qa_db_recent_a_qs_selectspec($voteuserid, $start, $hidden=false, $count=QA_DB_RETRIEVE_QS_AS)
/*
	For $count most recent answers ($hidden or not), starting from offset $start,
	return the selectspec to retrieve the antecedent questions for those answers, with the corresponding
	vote on those questions made by $voteuserid (if not null). The selectspec will also retrieve some
	information about the answers themselves, in columns named with the prefix 'a'.
*/
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
			" JOIN (SELECT postid FROM ^posts WHERE type=$ ORDER BY ^posts.created DESC LIMIT #,#) y ON aposts.postid=y.postid WHERE ^posts.type!='Q_HIDDEN'";
			
		$selectspec['arguments']=array($voteuserid, $hidden ? 'A_HIDDEN' : 'A', $start, $count);
		$selectspec['sortdesc']='acreated';
		
		return $selectspec;
	}

	
	function qa_db_recent_c_qs_selectspec($voteuserid, $start, $hidden=false, $count=QA_DB_RETRIEVE_QS_AS)
/*
	For $count most recent comments ($hidden or not), starting from offset $start,
	return the selectspec to retrieve the antecedent questions for those comments, with the corresponding
	vote on those questions made by $voteuserid (if not null). The selectspec will also retrieve some
	information about the comments themselves, in columns named with the prefix 'c'.
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['arraykey']='cpostid';

		$selectspec['columns']['cpostid']='cposts.postid';
		$selectspec['columns']['cuserid']='cposts.userid';
		$selectspec['columns']['ccookieid']='cposts.cookieid';
		$selectspec['columns']['ccreated']='UNIX_TIMESTAMP(cposts.created)';
		$selectspec['columns']['cpoints']='cuserpoints.points';
		
		if (!QA_EXTERNAL_USERS)
			$selectspec['columns']['chandle']='BINARY cusers.handle';
		
		$selectspec['source'].=" JOIN ^posts AS parentposts ON".
			" ^posts.postid=(CASE parentposts.type WHEN 'A' THEN parentposts.parentid ELSE parentposts.postid END)".
			" JOIN ^posts AS cposts ON parentposts.postid=cposts.parentid".
			(QA_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS cusers ON cposts.userid=cusers.userid").
			" LEFT JOIN ^userpoints AS cuserpoints ON cposts.userid=cuserpoints.userid".
			" JOIN (SELECT postid FROM ^posts WHERE type=$ ORDER BY ^posts.created DESC LIMIT #,#) y ON cposts.postid=y.postid WHERE (^posts.type!='Q_HIDDEN') AND (parentposts.type!='A_HIDDEN')";
			
		$selectspec['arguments']=array($voteuserid, $hidden ? 'C_HIDDEN' : 'C', $start, $count);
		$selectspec['sortdesc']='ccreated';
		
		return $selectspec;
	}


	function qa_db_full_post_selectspec($voteuserid, $postid)
/*
	Return the selectspec to retrieve the full information for $postid, with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true, true);

		$selectspec['source'].=" WHERE ^posts.postid=#";
		$selectspec['arguments']=array($voteuserid, $postid);
		$selectspec['single']=true;

		return $selectspec;
	}

	
	function qa_db_full_child_posts_selectspec($voteuserid, $parentid)
/*
	Return the selectspec to retrieve the full information for all posts whose parent is $parentid,
	with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true, true);
		
		$selectspec['source'].=" WHERE ^posts.parentid=#";
		$selectspec['arguments']=array($voteuserid, $parentid);
		
		return $selectspec;
	}


	function qa_db_full_a_child_posts_selectspec($voteuserid, $questionid)
/*
	Return the selectspec to retrieve the full information for all posts whose parent is an answer which
	has $questionid as its parent, with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true, true);
		
		$selectspec['source'].=" JOIN ^posts AS parents ON ^posts.parentid=parents.postid WHERE parents.parentid=# AND (parents.type='A' OR parents.type='A_HIDDEN')" ;
		$selectspec['arguments']=array($voteuserid, $questionid);
		
		return $selectspec;
	}
	

	function qa_db_post_parent_q_selectspec($questionid)
/*
	Return the selectspec to retrieve the question for the parent of $questionid (where $questionid is a follow-on question),
	i.e. the parent of $questionid's parent if $questionid's parent is an answer, otherwise $questionid's parent itself.
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(false, false);
		
		$selectspec['source'].=" WHERE ^posts.postid=(SELECT IF((parent.type='A') OR (parent.type='A_HIDDEN'), parent.parentid, parent.postid) FROM ^posts AS child LEFT JOIN ^posts AS parent ON parent.postid=child.parentid WHERE child.postid=#)";
		$selectspec['arguments']=array($questionid);
		$selectspec['single']=true;
		
		return $selectspec;
	}
	

	function qa_db_related_qs_selectspec($voteuserid, $questionid, $count=QA_DB_RETRIEVE_QS_AS)
/*
	Return the selectspec to retrieve the $count most closely related questions to $questionid,
	with the corresponding vote made by $voteuserid (if not null). This works by looking for other
	questions which have title words or tag words in common.
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['columns'][]='score';
		
		// added LOG(postid)/1000000 here to ensure ordering is deterministic even if several posts have same score
		
		$selectspec['source'].=" JOIN (SELECT postid, SUM(score)+LOG(postid)/1000000 AS score FROM ((SELECT ^titlewords.postid, LOG(#/titlecount) AS score FROM ^titlewords JOIN ^words ON ^titlewords.wordid=^words.wordid JOIN ^titlewords AS source ON ^titlewords.wordid=source.wordid WHERE source.postid=# AND titlecount<#) UNION ALL (SELECT ^posttags.postid, 2*LOG(#/tagcount) AS score FROM ^posttags JOIN ^words ON ^posttags.wordid=^words.wordid JOIN ^posttags AS source ON ^posttags.wordid=source.wordid WHERE source.postid=# AND tagcount<#)) x GROUP BY postid ORDER BY score DESC LIMIT #) y ON ^posts.postid=y.postid";
		
		$selectspec['arguments']=array($voteuserid, QA_IGNORED_WORDS_FREQ, $questionid, QA_IGNORED_WORDS_FREQ,
			QA_IGNORED_WORDS_FREQ, $questionid, QA_IGNORED_WORDS_FREQ, $count);
			
		$selectspec['sortdesc']='score';
			
		return $selectspec;
	}
	

	function qa_db_search_posts_selectspec($db, $voteuserid, $titlewords, $contentwords, $tagwords, $handlewords, $start, $count=QA_DB_RETRIEVE_QS_AS)
/*
	Return the selectspec to retrieve the $count top question matches, starting from the offset $start,
	with the corresponding vote made by $voteuserid (if not null). The search is performed for any of $titlewords in the title,
	$contentwords in the content (of the question or an answer or comment for whom that is the antecedent question), $tagwords
	in tags, and for question author usernames which match a word in $handlewords (so this won't help find a question by an author
	with a handle that contains more than one word). The results also include a 'score' column based on the matching strength,
	and a 'matchparts' column that tells us where the score came from (since a question could get weight from a match in the
	question itself, and/or weight from a match in its answers, comments, or comments on answers). The 'matchparts' is a
	comma-separated list of matchpostid:matchscore pairs, where a matchpostid can be repeated. See qa-page-search.php for usage.
*/
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
/*
	Return the selectspec to retrieve $count recent questions with $tag, starting from offset $start,
	with the corresponding vote on those questions made by $voteuserid (if not null).
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		// use two tests here - one which can use the index, and the other which narrows it down exactly - then limit to 1 just in case
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posttags WHERE wordid=(SELECT wordid FROM ^words WHERE word=$ AND word=$ COLLATE utf8_bin LIMIT 1) ORDER BY postcreated DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		$selectspec['arguments']=array($voteuserid, $tag, qa_strtolower($tag), $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}

	
	function qa_db_tag_count_qs_selectspec($tag)
/*
	Return the selectspec to retrieve the number of questions tagged with $tag (single value)
*/
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
/*
	Return the selectspec to retrieve $count recent questions by the user identified by $identifier, where
	$identifier is a handle if we're using internal user management, or a userid if QA_EXTERNAL_USERS.
	Also include the corresponding vote on those questions made by $voteuserid (if not null).
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true, false, false);
		
		$selectspec['source'].=" WHERE ^posts.userid=".(QA_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$)")." AND type='Q' ORDER BY ^posts.created DESC LIMIT #";
		$selectspec['arguments']=array($voteuserid, $identifier, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}

	
	function qa_db_user_recent_a_qs_selectspec($voteuserid, $identifier, $count=QA_DB_RETRIEVE_QS_AS)
/*
	For $count recent answers by the user identified by $identifier (see qa_db_user_recent_qs_selectspec() comment)
	return the selectspec to retrieve the antecedent questions for those answers, with the corresponding
	vote on those questions made by $voteuserid (if not null). The selectspec will also retrieve some
	information about the answers themselves, in columns named with the prefix 'a'.
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(true);
		
		$selectspec['columns']['apostid']='aposts.postid';
		$selectspec['columns']['acreated']='UNIX_TIMESTAMP(aposts.created)';
		
		$selectspec['source'].=" JOIN ^posts AS aposts ON ^posts.postid=aposts.parentid".
			(QA_EXTERNAL_USERS ? "" : " JOIN ^users AS ausers ON aposts.userid=ausers.userid").
			" WHERE aposts.userid=".(QA_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$)")." AND aposts.type='A'".
			" ORDER BY aposts.created DESC LIMIT #";
			
		$selectspec['arguments']=array($voteuserid, $identifier, $count);
		$selectspec['sortdesc']='acreated';
		
		return $selectspec;
	}

		
	function qa_db_popular_tags_selectspec($start, $count=QA_DB_RETRIEVE_TAGS)
/*
	Return the selectspec to retrieve the $count most popular tags, starting from offset $start.
	The selectspec will produce a sorted array with tags in the key, and counts in the values.
*/
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
/*
	Return the selecspec to retrieve a single array with details of the account of the user identified by
	$useridhandle, which should be a userid if $isuserid is true, otherwise $useridhandle should be a handle.
*/
	{
		return array(
			'columns' => array(
				'userid', 'passsalt', 'passcheck' => 'HEX(passcheck)', 'email' => 'BINARY email', 'level', 'handle' => 'BINARY handle', 'resetcode',
				'created' => 'UNIX_TIMESTAMP(created)', 'sessioncode',
			),
			
			'source' => '^users WHERE '.($isuserid ? 'userid' : 'handle').'=$',
			'arguments' => array($useridhandle),
			'single' => true,
		);
	}

	
	function qa_db_user_profile_selectspec($useridhandle, $isuserid)
/*
	Return the selectspec to retrieve all user profile (location, website, etc...) information of the user
	identified by $useridhandle (see qa_db_user_account_selectspec() comment), as an array of [field] => [value]
*/
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
/*
	Return the selectspec to retrieve all columns from the userpoints table for the user identified by $identifier
	(see qa_db_user_recent_qs_selectspec() comment), as a single array
*/
	{
		return array(
			'columns' => array('points', 'qposts', 'aposts', 'cposts', 'aselects', 'aselecteds', 'qvotes', 'avotes', 'qvoteds', 'avoteds', 'upvoteds', 'downvoteds'),
			'source' => '^userpoints WHERE userid='.(QA_EXTERNAL_USERS ? '$' : '(SELECT userid FROM ^users WHERE handle=$)'),
			'arguments' => array($identifier),
			'single' => true,
		);
	}

	
	function qa_db_user_rank_selectspec($identifier)
/*
	Return the selectspec to calculate the rank in points of the user identified by $identifier
	(see qa_db_user_recent_qs_selectspec() comment), as a single value
*/
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
/*
	Return the selectspec to get the $count top scoring users, starting from offset $start, with handles
	if we're using internal user management
*/
	{
		if (QA_EXTERNAL_USERS)
			return array(
				'columns' => array('userid', 'points'),
				'source' => '^userpoints ORDER BY points DESC LIMIT #,#',
				'arguments' => array($start, $count),
				'arraykey' => 'userid',
				'sortdesc' => 'points',
			);
		
		else
			return array(
				'columns' => array('^users.userid', 'handle' => 'BINARY handle', 'points'),
				'source' => '^users JOIN (SELECT userid FROM ^userpoints ORDER BY points DESC LIMIT #,#) y ON ^users.userid=y.userid JOIN ^userpoints ON ^users.userid=^userpoints.userid',
				'arguments' => array($start, $count),
				'arraykey' => 'userid',
				'sortdesc' => 'points',
			);
	}

	
	function qa_db_users_from_level_selectspec($level)
/*
	Return the selectspec to get information about users at a certain privilege level or higher
*/
	{
		return array(
			'columns' => array('^users.userid', 'handle' => 'BINARY handle', 'level'),
			'source' => '^users WHERE level>=# ORDER BY level DESC',
			'arguments' => array($level),
			'sortdesc' => 'level',
		);
	}

	
	function qa_db_options_cache_selectspec($title)
/*
	Return the selectspec to get a particular cached value out of the options table, as a single value
*/
	{
		return array(
			'columns' => array('content'),
			'source' => '^options WHERE title=$',
			'arguments' => array($title),
			'arrayvalue' => 'content',
			'single' => true,
		);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/