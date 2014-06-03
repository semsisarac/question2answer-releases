<?php
	
/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-categories.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Controller for admin page for editing categories


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';

	
//	Get current list of categories

	$categories=qa_db_select_with_pending(qa_db_categories_selectspec());
	

//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;
		
		
//	Work out the appropriate state for the page
	
	if (qa_clicked('doaddcategory'))
		$editcategory=array();

	elseif (qa_clicked('dosavecategory')) {
		$editcategory=@$categories[qa_post_text('edit')];
		if (!isset($editcategory))
			$editcategory=array();

	} else
		$editcategory=@$categories[qa_get('edit')];
	

//	Process saving options

	if (count($categories) && (qa_clicked('dosaveoptions') || qa_clicked('doaddcategory')))
		qa_set_option('allow_no_category', (int)qa_post_text('option_allow_no_category'));


//	Process saving an old or new category

	if (qa_clicked('docancel'))
		$editcategory=null;

	elseif (qa_clicked('dosavecategory')) {
		require_once QA_INCLUDE_DIR.'qa-db-admin.php';
		require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		if (qa_post_text('dodelete')) {
			$inreassign=qa_post_text('reassign');

			qa_db_category_delete($editcategory['categoryid'], strlen($inreassign) ? $inreassign : null);
			qa_db_ifcategory_qcount_update(strlen($inreassign) ? $inreassign : null);

			$categories=qa_db_select_with_pending(qa_db_categories_selectspec()); // reload after changes
			$editcategory=null;
		
		} else {
			$inname=qa_post_text('name');
			$inposition=qa_post_text('position');

			$errors=array();
			
		//	Verify the name is legitimate
		
			if (empty($inname))
				$errors['name']=qa_lang('main/field_required');
			elseif (qa_strlen($inname)>QA_DB_MAX_CAT_PAGE_TITLE_LENGTH)
				$errors['name']=qa_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TITLE_LENGTH);
			else
				foreach ($categories as $category)
					if (
						($category['categoryid'] != @$editcategory['categoryid']) &&
						qa_strtolower($category['title']) == qa_strtolower($inname)
					)
						$errors['name']=qa_lang('admin/category_already_used');
		
		//	Verify the slug is legitimate (and try some defaults if we're creating a new category, and it's not)
				
			for ($attempt=0; $attempt<100; $attempt++) {
				switch ($attempt) {
					case 0:
						$inslug=qa_post_text('slug');
						if (!isset($inslug))
							$inslug=implode('-', qa_string_to_words($inname));
						break;
						
					case 1:
						$inslug=qa_lang_sub('admin/category_default_slug', $inslug);
						break;
						
					default:
						$inslug=qa_lang_sub('admin/category_default_slug', $attempt-1);
						break;
				}
				
				list($matchcategoryid, $matchpage)=qa_db_select_with_pending(
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
				elseif (isset($matchcategoryid) && ($matchcategoryid!=@$editcategory['categoryid']))
					$errors['slug']=qa_lang('admin/category_already_used');
				elseif (isset($matchpage))
					$errors['slug']=qa_lang('admin/page_already_used');
				else
					unset($errors['slug']);
				
				if (isset($editcategory['categoryid']) || !isset($errors['slug'])) // don't try other options if editing existing category
					break;
			}
			
		//	Perform appropriate database action
	
			if (isset($editcategory['categoryid'])) { // changing existing category
				qa_db_category_rename($editcategory['categoryid'],
					isset($errors['name']) ? $editcategory['title'] : $inname,
					isset($errors['slug']) ? $editcategory['tags'] : $inslug);
				
				qa_db_category_move($editcategory['categoryid'], $inposition);
				
				$categories=qa_db_select_with_pending(qa_db_categories_selectspec()); // reload after changes
	
				if (empty($errors))
					$editcategory=null;
				else
					$editcategory=@$categories[$editcategory['categoryid']];
	
			} else { // creating a new one
				if (empty($errors)) {
					$categoryid=qa_db_category_create($inname, $inslug);
					
					if (isset($inposition))
						qa_db_category_move($categoryid, $inposition);
					
					$categories=qa_db_select_with_pending(qa_db_categories_selectspec()); // reload after changes
					$editcategory=null;
				}
			}
		}
	}
		
	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/categories_title');
	
	$qa_content['error']=qa_admin_page_error();
	
	if (isset($editcategory)) {
		$positionoptions=array();
		$reassignoptions=array('' => qa_lang_html('main/no_category'));
		
		$previous=null;
		$passedself=false;
		
		foreach ($categories as $key => $category) {
			if (isset($previous))
				$positionhtml=qa_lang_html_sub('admin/after_x', qa_html($passedself ? $category['title'] : $previous['title']));
			else
				$positionhtml=qa_lang_html('admin/first');

			$positionoptions[$category['position']]=$positionhtml;

			if ($category['categoryid'] == @$editcategory['categoryid'])
				$passedself=true;
			else
				$reassignoptions[$category['categoryid']]=$category['title'];
				
			$previous=$category;
		}
		
		if (isset($editcategory['position']))
			$positionvalue=$positionoptions[$editcategory['position']];

		else {
			$positionvalue=isset($previous) ? qa_lang_html_sub('admin/after_x', qa_html($previous['title'])) : qa_lang_html('admin/first');
			$positionoptions[1+@max(array_keys($positionoptions))]=$positionvalue;
		}
		
		$qa_content['form']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_path_html($qa_request).'"',
			
			'style' => 'tall',
			
			'fields' => array(
				'name' => array(
					'id' => 'name_display',
					'tags' => 'NAME="name" ID="name"',
					'label' => qa_lang_html(count($categories) ? 'admin/category_name' : 'admin/category_name_first'),
					'value' => qa_html(isset($inname) ? $inname : @$editcategory['title']),
					'error' => qa_html(@$errors['name']),
				),
				
				'delete' => array(
					'tags' => 'NAME="dodelete" ID="dodelete"',
					'label' =>
						'<SPAN ID="reassign_shown">'.qa_lang_html('admin/delete_category_reassign').'</SPAN>'.
						'<SPAN ID="reassign_hidden" STYLE="display:none;">'.qa_lang_html('admin/delete_category').'</SPAN>',
					'value' => 0,
					'type' => 'checkbox',
				),
				
				'slug' => array(
					'id' => 'slug_display',
					'tags' => 'NAME="slug"',
					'label' => qa_lang_html('admin/category_slug'),
					'value' => qa_html(isset($inslug) ? $inslug : @$editcategory['tags']),
					'error' => qa_html(@$errors['slug']),
				),
				
				'reassign' => array(
					'id' => 'reassign_display',
					'tags' => 'NAME="reassign"',
					'type' => 'select',
					'options' => $reassignoptions,
				),
				
				'position' => array(
					'id' => 'position_display',
					'tags' => 'NAME="position"',
					'label' => qa_lang_html('admin/category_position'),
					'type' => 'select',
					'options' => $positionoptions,
					'value' => $positionvalue,
				),
			),

			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html(isset($editcategory['categoryid']) ? 'main/save_button' : 'admin/add_category_button'),
				),
				
				'cancel' => array(
					'tags' => 'NAME="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'dosavecategory' => '1', // for IE
				'edit' => @$editcategory['categoryid'],
			),
		);
		
		if (isset($editcategory['categoryid']))
			qa_checkbox_to_display($qa_content, array(
				'reassign_display' => 'dodelete',
				'reassign_shown' => 'dodelete',
				'reassign_hidden' => '!dodelete',
				'position_display' => '!dodelete',
				'slug_display' => '!dodelete',
			));
		
		else {
			unset($qa_content['form']['fields']['slug']);
			unset($qa_content['form']['fields']['delete']);
			unset($qa_content['form']['fields']['reassign']);

			if (!count($categories))
				unset($qa_content['form']['fields']['position']);
		}
		
		$qa_content['focusid']='name';
	
	} else {
		$categoryhtml='<OL STYLE="margin-bottom:0;">';
		foreach ($categories as $category)
			$categoryhtml.='<LI><A HREF="'.qa_path_html('admin/categories', array('edit' => $category['categoryid'])).'">'.
				qa_html($category['title']).'</A> - '.qa_lang_html_sub('main/x_questions', $category['qcount']).'</LI>';
		$categoryhtml.='</OL>';
		
		$qa_content['form']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
			
			'style' => 'tall',
			
			'fields' => array(
				'intro' => array(
					'label' => qa_lang_html('admin/categories_introduction'),
					'type' => 'static',
				),
				
				'categories' => array(
					'label' => qa_lang_html('admin/click_category_edit'),
					'type' => 'static',
					'value' => $categoryhtml,
				),

				'allow_no_category' => array(
					'label' => qa_lang_html('options/allow_no_category'),
					'tags' => 'NAME="option_allow_no_category"',
					'type' => 'checkbox',
					'value' => qa_opt('allow_no_category'),
				),
				
			),

			'buttons' => array(
				'save' => array(
					'tags' => 'NAME="dosaveoptions"',
					'label' => qa_lang_html('main/save_button'),
				),

				'add' => array(
					'tags' => 'NAME="doaddcategory"',
					'label' => qa_lang_html('admin/add_category_button'),
				),
			),
		);
		
		if (count($categories)) {
			unset($qa_content['form']['fields']['intro']);
		} else {
			unset($qa_content['form']['fields']['allow_no_category']);
			unset($qa_content['form']['fields']['categories']);
			unset($qa_content['form']['buttons']['save']);
		}
	}


	$qa_content['navigation']['sub']=qa_admin_sub_navigation();
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/