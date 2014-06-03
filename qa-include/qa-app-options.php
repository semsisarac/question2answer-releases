<?php
	
/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-options.php
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

	require_once QA_INCLUDE_DIR.'qa-db-options.php';

	function qa_get_option($db, $name)
	{
		$options=qa_get_options($db, array($name));

		return $options[$name];
	}

	function qa_get_options($db, $names)
	{
		global $qa_options_cache, $qa_options_pending;
		
	//	If any options not cached, retrieve them, and set as default if missing

		if (qa_options_set_pending($names)) {
			qa_options_load_options(qa_db_single_select($db, qa_options_pending_selectspec()));
			
			foreach ($names as $name)
				if (!isset($qa_options_cache[$name]))
					qa_set_option($db, $name, qa_default_option($name));
		}
		
	//	Pull out the options specifically requested here

		$options=array();
		foreach ($names as $name)
			$options[$name]=$qa_options_cache[$name];
		
		return $options;
	}
	
	function qa_options_set_pending($names)
	{
		global $qa_options_cache, $qa_options_pending;
		
		$added=false;
		
		foreach ($names as $name)
			if (!isset($qa_options_cache[$name])) {
				$qa_options_pending[$name]=true;
				$added=true;
			}
			
		return $added;
	}
	
	function qa_options_pending_selectspec()
	{
		global $qa_options_pending;
		
		if (count($qa_options_pending))
			return array(
				'columns' => array('title', 'content' => 'BINARY content'),
				'source' => '^options WHERE title IN ($)',
				'arguments' => array(array_keys($qa_options_pending)),
				'arraykey' => 'title',
				'arrayvalue' => 'content',
			);
		else
			return false;
	}
	
	function qa_options_load_options($gotoptions)
	{
		global $qa_options_cache, $qa_options_pending;
		
		foreach ($gotoptions as $name => $value) {
			$qa_options_cache[$name]=$value;
			unset($qa_options_pending[$name]);
		}
	}

	function qa_set_option($db, $name, $value)
	{
		global $qa_options_cache, $qa_options_pending;
		
		qa_db_set_option($db, $name, $value);

		$qa_options_cache[$name]=$value;
		unset($qa_options_pending[$name]);
	}
	
	function qa_reset_options($db, $names)
	{
		foreach ($names as $name)
			qa_set_option($db, $name, qa_default_option($name));
	}
	
	function qa_default_option($name)
	{
		global $qa_root_url_inferred;
		
		$fixed_defaults=array(
			'answer_needs_login' => 0,
			'ask_needs_login' => 0,
			'columns_tags' => 3,
			'columns_users' => 2,
			'do_ask_check_qs' => 0,
			'do_example_tags' => 1,
			'do_complete_tags' => 1,
			'do_related_qs' => 1,
			'match_ask_check_qs' => 3,
			'match_related_qs' => 3,
			'match_example_tags' => 3,
			'max_rate_ip_as' => 150,
			'max_rate_ip_qs' => 50,
			'max_rate_ip_votes' => 1500,
			'max_rate_user_as' => 30,
			'max_rate_user_qs' => 10,
			'max_rate_user_votes' => 300,
			'min_len_a_content' => 12,
			'min_len_q_content' => 0,
			'min_len_q_title' => 12,
			'neat_urls' => 0,
			'page_size_as' => 20,
			'page_size_ask_check_qs' => 5,
			'page_size_home' => 20,
			'page_size_qs' => 20,
			'page_size_related_qs' => 5,
			'page_size_search' => 10,
			'page_size_ask_tags' => 5,
			'page_size_tag_qs' => 20,
			'page_size_tags' => 60,
			'page_size_user_as' => 20,
			'page_size_user_qs' => 20,
			'page_size_users' => 40,
			'pages_prev_next' => 3,
			'points_a_selected' => 30,
			'points_a_voted_max_gain' => 20,
			'points_a_voted_max_loss' => 5,
			'points_base' => 100,
			'points_multiple' => 10,
			'points_per_a_voted' => 2,
			'points_per_q_voted' => 1,
			'points_post_a' => 4,
			'points_post_q' => 2,
			'points_q_voted_max_gain' => 10,
			'points_q_voted_max_loss' => 3,
			'points_select_a' => 3,
			'points_vote_on_a' => 1,
			'points_vote_on_q' => 1,
			'show_url_links' => 1,
			'site_theme' => 'Default',
		);
		
		if (isset($fixed_defaults[$name]))
			$value=$fixed_defaults[$name];
			
		else
			switch ($name) {
				case 'site_url':
					$value=$qa_root_url_inferred; // from qa-index.php
					break;
					
				case 'site_title': 
					$value=qa_default_site_title();
					break;
					
				case 'from_email': // heuristic to remove short prefix (e.g. www. or qa.)
					$parts=explode('.', @$_SERVER['HTTP_HOST']);
					
					if ( (count($parts)>2) && (strlen($parts[0])<5) && !is_numeric($parts[0]) )
						unset($parts[0]);
						
					$value='no-reply@'.((count($parts)>1) ? implode('.', $parts) : 'example.com');
					break;
					
				case 'email_privacy':
					$value=qa_lang_html('options/default_privacy');
					break;
				
				case 'custom_sidebar':
					$value=qa_lang_sub('options/default_sidebar', qa_html(qa_default_site_title()));
					break;
				
				default:
					$value='';
					break;
			}
		
		return $value;
	}
	
	function qa_default_site_title() // heuristic to take longest part of host name
	{
		$parts=explode('.', @$_SERVER['HTTP_HOST']);

		$longestpart='';
		foreach ($parts as $part)
			if (strlen($part)>strlen($longestpart))
				$longestpart=$part;
			
		return ((strlen($longestpart)>3) ? (ucfirst($longestpart).' ') : '').qa_lang('options/default_suffix');
	}
	
?>