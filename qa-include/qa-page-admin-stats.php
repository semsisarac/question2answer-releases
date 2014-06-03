<?php
	
/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-stats.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Controller for admin page showing usage statistics


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

	require_once QA_INCLUDE_DIR.'qa-db-recalc.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-admin.php';

	
//	Standard pre-admin operations

	qa_admin_pending();
	
	if (!qa_admin_check_privileges())
		return;


//	Get the information to display

	qa_options_set_pending(array('cache_qcount', 'cache_acount', 'cache_ccount', 'cache_userpointscount'));

	$qcount=qa_get_option($qa_db, 'cache_qcount');
	$qcount_anon=qa_db_count_posts($qa_db, 'Q', false);

	$acount=qa_get_option($qa_db, 'cache_acount');
	$acount_anon=qa_db_count_posts($qa_db, 'A', false);

	$ccount=qa_get_option($qa_db, 'cache_ccount');
	$ccount_anon=qa_db_count_posts($qa_db, 'C', false);

	
//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/stats_title');
	
	$qa_content['error']=qa_admin_page_error($qa_db);

	$qa_content['form']=array(
		'style' => 'wide',
		
		'fields' => array(
			'qcount' => array(
				'label' => qa_lang_html('admin/total_qs'),
				'value' => qa_html(number_format($qcount)),
			),
			
			'qcount_users' => array(
				'label' => qa_lang_html('admin/from_users'),
				'value' => qa_html(number_format($qcount-$qcount_anon)),
			),
	
			'qcount_anon' => array(
				'label' => qa_lang_html('admin/from_anon'),
				'value' => qa_html(number_format($qcount_anon)),
			),
			
			'break1' => array(
				'type' => 'blank',
			),
	
			'acount' => array(
				'label' => qa_lang_html('admin/total_as'),
				'value' => qa_html(number_format($acount)),
			),
	
			'acount_users' => array(
				'label' => qa_lang_html('admin/from_users'),
				'value' => qa_html(number_format($acount-$acount_anon)),
			),
	
			'acount_anon' => array(
				'label' => qa_lang_html('admin/from_anon'),
				'value' => qa_html(number_format($acount_anon)),
			),
			
			'break2' => array(
				'type' => 'blank',
			),
			
			'ccount' => array(
				'label' => qa_lang_html('admin/total_cs'),
				'value' => qa_html(number_format($ccount)),
			),
	
			'ccount_users' => array(
				'label' => qa_lang_html('admin/from_users'),
				'value' => qa_html(number_format($ccount-$ccount_anon)),
			),
	
			'ccount_anon' => array(
				'label' => qa_lang_html('admin/from_anon'),
				'value' => qa_html(number_format($ccount_anon)),
			),
			
			'break3' => array(
				'type' => 'blank',
			),
			
			'users' => array(
				'label' => qa_lang_html('admin/users_registered'),
				'value' => QA_EXTERNAL_USERS ? '' : qa_html(number_format(qa_db_count_users($qa_db))),
			),
	
			'users_active' => array(
				'label' => qa_lang_html('admin/users_active'),
				'value' => qa_html(number_format(qa_get_option($qa_db, 'cache_userpointscount'))),
			),
			
			'users_posted' => array(
				'label' => qa_lang_html('admin/users_posted'),
				'value' => qa_html(number_format(qa_db_count_active_users($qa_db, 'posts'))),
			),
	
			'users_voted' => array(
				'label' => qa_lang_html('admin/users_voted'),
				'value' => qa_html(number_format(qa_db_count_active_users($qa_db, 'uservotes'))),
			),
		),
	);
	
	if (QA_EXTERNAL_USERS)
		unset($qa_content['form']['fields']['users']);
	else
		unset($qa_content['form']['fields']['users_active']);

	foreach ($qa_content['form']['fields'] as $index => $field)
		if (empty($field['type']))
			$qa_content['form']['fields'][$index]['type']='static';
	
	$qa_content['form_2']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_path_html('admin/recalc').'" ',
		
		'title' => qa_lang_html('admin/database_cleanup'),
		
		'style' => 'basic',
		
		'buttons' => array(
			'recount_posts' => array(
				'label' => qa_lang_html('admin/recount_posts'),
				'tags' => ' NAME="dorecountposts" onClick="return qa_recalc_click(this, '.qa_js(qa_lang('admin/recount_posts_stop')).', \'recount_posts_note\');" ',
				'note' => '<SPAN ID="recount_posts_note">'.qa_lang_html('admin/recount_posts_note').'</SPAN>',
			),
	
			'reindex_posts' => array(
				'label' => qa_lang_html('admin/reindex_posts'),
				'tags' => ' NAME="doreindexposts" onClick="return qa_recalc_click(this, '.qa_js(qa_lang('admin/reindex_posts_stop')).', \'reindex_posts_note\');" ',
				'note' => '<SPAN ID="reindex_posts_note">'.qa_lang_html('admin/reindex_posts_note').'</SPAN>',
			),
			
			'recalc_points' => array(
				'label' => qa_lang_html('admin/recalc_points'),
				'tags' => ' NAME="dorecalcpoints" onClick="return qa_recalc_click(this, '.qa_js(qa_lang('admin/recalc_points_stop')).', \'recalc_points_note\');" ',
				'note' => '<SPAN ID="recalc_points_note">'.qa_lang_html('admin/recalc_points_note').'</SPAN>',
			),
		),
	);
	
	$qa_content['script_src'][]='jxs_compressed.js';
	$qa_content['script_src'][]='qa-admin.js?'.QA_VERSION;
	$qa_content['script_var']['qa_warning_recalc']=qa_lang('admin/stop_recalc_warning');
	
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

?>