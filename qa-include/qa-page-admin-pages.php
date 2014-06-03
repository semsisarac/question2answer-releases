<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-pages.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for admin page for editing custom pages and external links


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';

	
//	Queue requests for pending admin options

	qa_admin_pending();
	

//	Get current list of pages and determine the state of this admin page

	$pageid=qa_post_text('edit');
	if (!isset($pageid))
		$pageid=qa_get('edit');
		
	@list($pages, $editpage)=qa_db_select_with_pending($qa_db,
		qa_db_pages_selectspec(),
		isset($pageid) ? qa_db_page_full_selectspec($pageid, true) : null
	);
		
	if ((qa_clicked('doaddpage') || qa_clicked('doaddlink') || qa_clicked('dosavepage')) && !isset($editpage)) {
		$editpage=array();
		$isexternal=qa_clicked('doaddlink') || qa_post_text('external');
		
	} elseif (isset($editpage))
		$isexternal=$editpage['flags'] & QA_PAGE_FLAGS_EXTERNAL;
	

//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges())
		return;
		
		
//	Define an array of navigation settings we can change, option name => language key
	
	$hascustomhome=qa_get_option($qa_db, 'show_custom_home');
	
	$navoptions=array(
		'nav_home' => 'main/nav_home',
		'nav_activity' => 'main/nav_activity',
		 $hascustomhome ? 'nav_qa_not_home' : 'nav_qa_is_home' => $hascustomhome ? 'main/nav_qa' : 'admin/nav_qa_is_home',
		'nav_questions' => 'main/nav_qs',
		'nav_unanswered' => 'main/nav_unanswered',
		'nav_tags' => 'main/nav_tags',
		'nav_categories' => 'main/nav_categories',
		'nav_users' => 'main/nav_users',
	);
	
	if (!qa_get_option($qa_db, 'show_custom_home'))
		unset($navoptions['nav_home']);
		
	if (!qa_using_categories($qa_db))
		unset($navoptions['nav_categories']);

	if (!qa_using_tags($qa_db))
		unset($navoptions['nav_tags']);
	
	qa_options_set_pending(array_keys($navoptions));


//	Process saving an old or new page

	if (qa_clicked('docancel'))
		$editpage=null;

	elseif (qa_clicked('dosaveoptions') || qa_clicked('doaddpage') || qa_clicked('doaddlink')) {
		foreach ($navoptions as $optionname => $langkey)
			qa_set_option($qa_db, $optionname, (int)qa_post_text('option_'.$optionname));

	} elseif (qa_clicked('dosavepage')) {
		require_once QA_INCLUDE_DIR.'qa-db-admin.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$reloadpages=false;
		
		if (qa_post_text('dodelete')) {
			qa_db_page_delete($qa_db, $editpage['pageid']);
			$editpage=null;
			$reloadpages=true;
		
		} else {
			$inname=qa_post_text('name');
			$inposition=qa_post_text('position');
			$inurl=qa_post_text('url');
			$innewwindow=qa_post_text('newwindow');
			$inheading=qa_post_text('heading');
			$incontent=qa_post_text('content');

			$errors=array();
			
		//	Verify the name (navigation link) is legitimate
		
			if (empty($inname))
				$errors['name']=qa_lang('main/field_required');
			elseif (qa_strlen($inname)>QA_DB_MAX_CAT_PAGE_TITLE_LENGTH)
				$errors['name']=qa_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TITLE_LENGTH);
			else
				foreach ($pages as $page)
					if (
						($page['pageid'] != @$editpage['pageid']) &&
						qa_strtolower($page['title']) == qa_strtolower($inname)
					)
						$errors['name']=qa_lang('admin/page_already_used');
						
			if ($isexternal) {
			
			//	Verify the url is legitimate (vaguely)
			
				if (empty($inurl))
					$errors['url']=qa_lang('main/field_required');
				elseif (qa_strlen($inurl)>QA_DB_MAX_CAT_PAGE_TAGS_LENGTH)
					$errors['url']=qa_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TAGS_LENGTH);

			} else {
			
			//	Verify the heading is legitimate
			
				if (qa_strlen($inheading)>QA_DB_MAX_TITLE_LENGTH)
					$errors['heading']=qa_lang_sub('main/max_length_x', QA_DB_MAX_TITLE_LENGTH);
			
			//	Verify the slug is legitimate (and try some defaults if we're creating a new page, and it's not)
					
				for ($attempt=0; $attempt<100; $attempt++) {
					switch ($attempt) {
						case 0:
							$inslug=qa_post_text('slug');
							if (!isset($inslug))
								$inslug=implode('-', qa_string_to_words($inname));
							break;
							
						case 1:
							$inslug=qa_lang_sub('admin/page_default_slug', $inslug);
							break;
							
						default:
							$inslug=qa_lang_sub('admin/page_default_slug', $attempt-1);
							break;
					}
					
					list($matchcategoryid, $matchpage)=qa_db_select_with_pending($qa_db,
						qa_db_slug_to_category_id_selectspec($inslug),
						qa_db_page_full_selectspec($inslug, false)
					);
					
					if (empty($inslug))
						$errors['slug']=qa_lang('main/field_required');
					elseif (qa_strlen($inslug)>QA_DB_MAX_CAT_PAGE_TAGS_LENGTH)
						$errors['slug']=qa_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TAGS_LENGTH);
					elseif (preg_match('/[\\+\\/]/', $inslug))
						$errors['slug']=qa_lang_sub('admin/slug_bad_chars', '+ /');
					elseif (qa_is_slug_reserved($inslug))
						$errors['slug']=qa_lang('admin/slug_reserved');
					elseif (isset($matchpage) && ($matchpage['pageid']!=@$editpage['pageid']))
						$errors['slug']=qa_lang('admin/page_already_used');
					elseif (isset($matchcategoryid))
						$errors['slug']=qa_lang('admin/category_already_used');
					else
						unset($errors['slug']);
					
					if (isset($editpage['pageid']) || !isset($errors['slug'])) // don't try other options if editing existing page
						break;
				}
			}
			
		//	Perform appropriate database action
	
			if (isset($editpage['pageid'])) { // changing existing page
				if ($isexternal)
					qa_db_page_set_fields($qa_db, $editpage['pageid'],
						isset($errors['name']) ? $editpage['title'] : $inname,
						QA_PAGE_FLAGS_EXTERNAL | ($innewwindow ? QA_PAGE_FLAGS_NEW_WINDOW : 0),
						isset($errors['url']) ? $editpage['tags'] : $inurl,
						null, null);

				else
					qa_db_page_set_fields($qa_db, $editpage['pageid'],
						isset($errors['name']) ? $editpage['title'] : $inname,
						0,
						isset($errors['slug']) ? $editpage['tags'] : $inslug,
						isset($errors['heading']) ? $editpage['heading'] : $inheading,
						isset($errors['content']) ? $editpage['content'] : $incontent);
				
				qa_db_page_move($qa_db, $editpage['pageid'], substr($inposition, 0, 1), substr($inposition, 1));
				
				$reloadpages=true;
	
				if (empty($errors))
					$editpage=null;
				else
					$editpage=@$pages[$editpage['pageid']];
	
			} else { // creating a new one
				if (empty($errors)) {
					if ($isexternal)
						$pageid=qa_db_page_create($qa_db, $inname, QA_PAGE_FLAGS_EXTERNAL | ($innewwindow ? QA_PAGE_FLAGS_NEW_WINDOW : 0), $inurl, null, null);
					else
						$pageid=qa_db_page_create($qa_db, $inname, 0, $inslug, $inheading, $incontent);
						
					qa_db_page_move($qa_db, $pageid, substr($inposition, 0, 1), substr($inposition, 1));

					$editpage=null;
					$reloadpages=true;
				}
			}
		}
		
		if ($reloadpages) {
			unset($qa_nav_pages_cached);
			$pages=qa_db_select_with_pending($qa_db, qa_db_pages_selectspec());
		}
	}
		
		
//	Prepare content for theme
	
	qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/pages_title');
	
	$qa_content['error']=qa_admin_page_error($qa_db);

	if (isset($editpage)) {
		$positionoptions=array();
		
		if (!$isexternal)
			$positionoptions['_'.max(1, @$editpage['position'])]=qa_lang_html('admin/no_link');
		
		$navlangkey=array(
			'B' => 'admin/before_main_menu',
			'M' => 'admin/after_main_menu',
			'O' => 'admin/opposite_main_menu',
			'F' => 'admin/after_footer',
		);
		
		foreach ($navlangkey as $nav => $langkey) {
			$previous=null;
			$passedself=false;
			$maxposition=0;
			
			foreach ($pages as $key => $page)
				if ($page['nav']==$nav) {
					if (isset($previous))
						$positionhtml=qa_lang_html_sub('admin/after_x_tab', qa_html($passedself ? $page['title'] : $previous['title']));
					else
						$positionhtml=qa_lang_html($langkey);
						
					if ($page['pageid']==@$editpage['pageid'])
						$passedself=true;
		
					$maxposition=max($maxposition, $page['position']);
					$positionoptions[$nav.$page['position']]=$positionhtml;
						
					$previous=$page;
				}
				
			if ($nav!=@$editpage['nav']) {
				$positionvalue=isset($previous) ? qa_lang_html_sub('admin/after_x_tab', $previous['title']) : qa_lang_html($langkey);
				$positionoptions[$nav.(isset($previous) ? (1+$maxposition) : 1)]=$positionvalue;
			}
		}
		
		$positionvalue=@$positionoptions[$editpage['nav'].$editpage['position']];
		
		$qa_content['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_path_html($qa_request).'" ',
			
			'style' => 'tall',
			
			'fields' => array(
				'name' => array(
					'tags' => ' NAME="name" ID="name" ',
					'label' => qa_lang_html($isexternal ? 'admin/link_name' : 'admin/page_name'),
					'value' => qa_html(isset($inname) ? $inname : @$editpage['title']),
					'error' => qa_html(@$errors['name']),
				),
				
				'delete' => array(
					'tags' => ' NAME="dodelete" ID="dodelete" ',
					'label' => qa_lang_html($isexternal ? 'admin/delete_link' : 'admin/delete_page'),
					'value' => 0,
					'type' => 'checkbox',
				),
				
				'position' => array(
					'id' => 'position_display',
					'tags' => ' NAME="position" ',
					'label' => qa_lang_html('admin/page_link_position'),
					'type' => 'select',
					'options' => $positionoptions,
					'value' => $positionvalue,
				),
				
				'slug' => array(
					'id' => 'slug_display',
					'tags' => ' NAME="slug" ',
					'label' => qa_lang_html('admin/page_slug'),
					'value' => qa_html(isset($inslug) ? $inslug : @$editpage['tags']),
					'error' => qa_html(@$errors['slug']),
				),
				
				'url' => array(
					'id' => 'url_display',
					'tags' => ' NAME="url" ',
					'label' => qa_lang_html('admin/link_url'),
					'value' => qa_html(isset($inurl) ? $inurl : @$editpage['tags']),
					'error' => qa_html(@$errors['url']),
				),
				
				'newwindow' => array(
					'id' => 'newwindow_display',
					'tags' => ' NAME="newwindow" ',
					'label' => qa_lang_html('admin/link_new_window'),
					'value' => (isset($innewwindow) ? $innewwindow : (@$editpage['flags'] & QA_PAGE_FLAGS_NEW_WINDOW)) ? 1 : 0,
					'type' => 'checkbox',
				),
				
				'heading' => array(
					'id' => 'heading_display',
					'tags' => ' NAME="heading" ',
					'label' => qa_lang_html('admin/page_heading'),
					'value' => qa_html(isset($inheading) ? $inheading : @$editpage['heading']),
					'error' => qa_html(@$errors['heading']),
				),
				
				'content' => array(
					'id' => 'content_display',
					'tags' => ' NAME="content" ',
					'label' => qa_lang_html('admin/page_content_html'),
					'value' => qa_html(isset($incontent) ? $incontent : @$editpage['content']),
					'error' => qa_html(@$errors['content']),
					'rows' => 16,
				),
			),

			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html(isset($editpage['pageid']) ? 'main/save_button' : ($isexternal ? 'admin/add_link_button' : 'admin/add_page_button')),
				),
				
				'cancel' => array(
					'tags' => ' NAME="docancel" ',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'dosavepage' => '1', // for IE
				'edit' => @$editpage['pageid'],
				'external' => (int)$isexternal,
			),
		);
		
		if ($isexternal) {
			unset($qa_content['form']['fields']['slug']);
			unset($qa_content['form']['fields']['heading']);
			unset($qa_content['form']['fields']['content']);
		
		} else {
			unset($qa_content['form']['fields']['url']);
			unset($qa_content['form']['fields']['newwindow']);
		}
		
		if (isset($editpage['pageid']))
			qa_checkbox_to_display($qa_content, array(
				'position_display' => '!dodelete',
				($isexternal ? 'url_display' : 'slug_display') => '!dodelete',
				($isexternal ? 'newwindow_display' : 'heading_display') => '!dodelete',
				'content_display' => '!dodelete',
			));
		
		else {
			unset($qa_content['form']['fields']['slug']);
			unset($qa_content['form']['fields']['delete']);
		}
		
		$qa_content['focusid']='name';
	
	} else {
		$pagehtml='<UL STYLE="margin-bottom:0;">';
		foreach ($pages as $page)
			$pagehtml.='<LI><A HREF="'.qa_path_html('admin/pages', array('edit' => $page['pageid'])).'">'.
				qa_html($page['title']).'</A></LI>';
		$pagehtml.='</UL>';
		
		$qa_content['form']=array(
			'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
			
			'style' => 'tall',
			
			'fields' => array(),

			'buttons' => array(
				'save' => array(
					'tags' => ' NAME="dosaveoptions" ',
					'label' => qa_lang_html('main/save_button'),
				),

				'addpage' => array(
					'tags' => ' NAME="doaddpage" ',
					'label' => qa_lang_html('admin/add_page_button'),
				),

				'addlink' => array(
					'tags' => ' NAME="doaddlink" ',
					'label' => qa_lang_html('admin/add_link_button'),
				),
			),
		);
		
		$qa_content['form']['fields']['navlinks']=array(
			'label' => qa_lang_html('admin/nav_links_explanation'),
			'type' => 'static',
			'tight' => true,
		);

		foreach ($navoptions as $optionname => $langkey) {
			$qa_content['form']['fields'][$optionname]=array(
				'label' => qa_lang_html($langkey),
				'tags' => ' NAME="option_'.$optionname.'" ',
				'type' => 'checkbox',
				'value' => qa_get_option($qa_db, $optionname),
			);
		}
		
		$qa_content['form']['fields']['pages']=array(
			'label' => count($pages) ? qa_lang_html('admin/click_name_edit') : qa_lang_html('admin/pages_explanation'),
			'type' => 'static',
			'value' => count($pages) ? $pagehtml : null,
		);
	}


	$qa_content['navigation']['sub']=qa_admin_sub_navigation();


/*
	Omit PHP closing tag to help avoid accidental output
*/