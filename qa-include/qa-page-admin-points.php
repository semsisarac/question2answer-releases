<?php
	
/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-points.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Controller for admin page for user points


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
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	
	
//	Standard pre-admin operations

	qa_admin_pending();
	
	if (!qa_admin_check_privileges())
		return;


//	Perform any actions requested

	$optionnames=qa_db_points_option_names();
	
	if (qa_clicked('doshowdefaults')) {
		$options=array();
		
		foreach ($optionnames as $optionname)
			$options[$optionname]=qa_default_option($optionname);
		
	} else {
		if (qa_clicked('docancel'))
			;

		elseif (qa_clicked('dosaverecalc')) {
			foreach ($optionnames as $optionname)
				qa_set_option($qa_db, $optionname, (int)qa_post_text('option_'.$optionname));
				
			if (!qa_post_text('has_js'))
				qa_redirect('admin/recalc', array('dorecalcpoints' => 1));
		}
	
		$options=qa_get_options($qa_db, $optionnames);
	}
	
	
//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/points_title');

	$qa_content['error']=qa_admin_page_error($qa_db);

	$qa_content['form']=array(
		'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" NAME="points_form" onsubmit="document.forms.points_form.has_js.value=1; return true;" ',
		
		'style' => 'wide',
		
		'buttons' => array(
			'saverecalc' => array(
				'tags' => ' NAME="dorecalcpoints" ID="dosaverecalc" ', // name important for recalc logic
				'label' => qa_lang_html('admin/save_recalc_button'),
			),
		),
		
		'hidden' => array(
			'dosaverecalc' => '1',
			'has_js' => '0',
		),
	);

	
	if (qa_clicked('doshowdefaults')) {
		$qa_content['form']['ok']=qa_lang_html('admin/points_defaults_shown');
	
		$qa_content['form']['buttons']['cancel']=array(
			'tags' => ' NAME="docancel" ',
			'label' => qa_lang_html('admin/cancel_button'),
		);

	} else {
		if (qa_clicked('docancel'))
			;
		elseif (qa_clicked('dosaverecalc')) {
			$qa_content['form']['ok']='<SPAN ID="recalc_points_ok"></SPAN>';
			
			$qa_content['script_src'][]='jxs_compressed.js';
			$qa_content['script_src'][]='qa-admin.js?'.QA_VERSION;
			$qa_content['script_var']['qa_warning_recalc']=qa_lang('admin/stop_recalc_warning');
			
			$qa_content['script_onloads'][]=array(
				"qa_recalc_click(document.getElementById('dosaverecalc'), ".qa_js(qa_lang('admin/save_recalc_button')).", 'recalc_points_ok');"
			); // doesn't change button title since we haven't set up onClick handler anyway
		}
		
		$qa_content['form']['buttons']['showdefaults']=array(
			'tags' => ' NAME="doshowdefaults" ',
			'label' => qa_lang_html('admin/show_defaults_button'),
		);
	}

	
	foreach ($optionnames as $optionname) {
		$optionfield=array(
			'label' => qa_lang_html('options/'.$optionname),
			'tags' => ' NAME="option_'.$optionname.'" ',
			'value' => qa_html($options[$optionname]),
			'type' => 'number',
			'note' => qa_lang_html('admin/points'),
		);
		
		switch ($optionname) {
			case 'points_multiple':
				$optionfield['prefix']='&#215;';
				unset($optionfield['note']);
				break;
				
			case 'points_per_q_voted':
			case 'points_per_a_voted':
				$optionfield['prefix']='&#177;';
				break;
				
			case 'points_q_voted_max_loss':
			case 'points_a_voted_max_loss':
				$optionfield['prefix']='&ndash;';
				break;
				
			case 'points_base':
				unset($optionfield['note']);
			default:
				$optionfield['prefix']='+';
				break;
		}
		
		$qa_content['form']['fields'][$optionname]=$optionfield;
	}
	
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();


/*
	Omit PHP closing tag to help avoid accidental output
*/