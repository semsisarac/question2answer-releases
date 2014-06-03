<?php
	
/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-widgets.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Controller for admin page for editing widgets


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

	
//	Get current list of widgets and determine the state of this admin page

	$widgetid=qa_post_text('edit');
	if (!strlen($widgetid))
		$widgetid=qa_get('edit');
		
	$widgets=qa_db_select_with_pending(qa_db_widgets_selectspec());

	if (isset($widgetid)) {
		$editwidget=null;
		foreach ($widgets as $widget)
			if ($widget['widgetid']==$widgetid)
				$editwidget=$widget;

	} else {
		$editwidget=array('title' => qa_post_text('title'));
		if (!isset($editwidget['title']))
			$editwidget['title']=qa_get('title');
	}

	$module=qa_load_module('widget', @$editwidget['title']);
	
	$widgetfound=isset($module);
			

//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;
		
		
//	Define an array of relevant templates we can use

	$templateoptions=array(
		'question' => 'admin/question_pages',

		'qa' => 'main/recent_qs_as_title',
		'questions' => 'main/recent_qs_title',
		'unanswered' => 'main/unanswered_qs_title',
		'activity' => 'main/recent_activity_title',

		'tags' => 'main/popular_tags',
		'tag' => 'admin/tag_pages',
		'categories' => 'main/all_categories',
		'users' => 'main/highest_users',
		'user' => 'admin/user_pages',

		'ask' => 'question/ask_title',
		'search' => 'main/search_title',
		'feedback' => 'misc/feedback_title',

		'login' => 'users/login_title',
		'register' => 'users/register_title',
		'account' => 'profile/my_account_title',

		'ip' => 'admin/ip_address_pages',
		'admin' => 'admin/admin_title',
	);
	

//	Process saving an old or new widget

	if (qa_clicked('docancel'))
		qa_redirect('admin/layout');

	elseif (qa_clicked('dosavewidget')) {
		require_once QA_INCLUDE_DIR.'qa-db-admin.php';
		
		if (qa_post_text('dodelete')) {
			qa_db_widget_delete($editwidget['widgetid']);
			qa_redirect('admin/layout');
		
		} else {
			if ($widgetfound) {
				$intitle=qa_post_text('title');
				$inposition=qa_post_text('position');
				$intemplates=array();
				
				if (qa_post_text('template_all'))
					$intemplates[]='all';
				
				foreach ($templateoptions as $template => $langkey)
					if (qa_post_text('template_'.$template))
						$intemplates[]=$template;
						
				$intags=implode(',', $intemplates);
	
			//	Perform appropriate database action
		
				if (isset($editwidget['widgetid'])) { // changing existing widget
					$widgetid=$editwidget['widgetid'];
					qa_db_widget_set_fields($widgetid, $intags);
	
				} else
					$widgetid=qa_db_widget_create($intitle, $intags);
	
				qa_db_widget_move($widgetid, substr($inposition, 0, 2), substr($inposition, 2));
			}
			
			qa_redirect('admin/layout');
		}
	}
	
		
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/layout_title');
	
	$qa_content['error']=qa_admin_page_error();
	
	$positionoptions=array();
	
	$placeoptionhtml=qa_admin_place_options();
	
	$regioncodes=array(
		'F' => 'full',
		'M' => 'main',
		'S' => 'side',
	);

	foreach ($placeoptionhtml as $place => $optionhtml) {
		$region=$regioncodes[substr($place, 0, 1)];

		$widgetallowed=method_exists($module, 'allow_region') && $module->allow_region($region);
		
		if ($widgetallowed)
			foreach ($widgets as $widget)
				if ( ($widget['place']==$place) && ($widget['title']==$editwidget['title']) && ($widget['widgetid']!==@$editwidget['widgetid']) )
					$widgetallowed=false; // don't allow two instances of same widget in same place

		if ($widgetallowed) {
			$previous=null;
			$passedself=false;
			$maxposition=0;
			
			foreach ($widgets as $widget)
				if ($widget['place']==$place) {
					$positionhtml=$optionhtml;
					
					if (isset($previous))
						$positionhtml.=' - '.qa_lang_html_sub('admin/after_x', qa_html($passedself ? $widget['title'] : $previous['title']));
						
					if ($widget['widgetid']==@$editwidget['widgetid'])
						$passedself=true;
		
					$maxposition=max($maxposition, $widget['position']);
					$positionoptions[$place.$widget['position']]=$positionhtml;
						
					$previous=$widget;
				}
				
			if ((!isset($editwidget['widgetid'])) || $place!=@$editwidget['place']) {
				$positionhtml=$optionhtml;
				
				if (isset($previous))
					$positionhtml.=' - '.qa_lang_html_sub('admin/after_x', $previous['title']);
	
				$positionoptions[$place.(isset($previous) ? (1+$maxposition) : 1)]=$positionhtml;
			}
		}
	}
	
	$positionvalue=@$positionoptions[$editwidget['place'].$editwidget['position']];
	
	$qa_content['form']=array(
		'tags' => 'METHOD="POST" ACTION="'.qa_path_html($qa_request).'"',
		
		'style' => 'tall',
		
		'fields' => array(
			'title' => array(
				'label' => qa_lang_html('admin/widget_name').' &nbsp; '.qa_html($editwidget['title']),
				'type' => 'static',
				'tight' => true,
			),
			
			'position' => array(
				'id' => 'position_display',
				'tags' => 'NAME="position"',
				'label' => qa_lang_html('admin/position'),
				'type' => 'select',
				'options' => $positionoptions,
				'value' => $positionvalue,
			),
			
			'delete' => array(
				'tags' => 'NAME="dodelete" ID="dodelete"',
				'label' => qa_lang_html('admin/delete_widget_position'),
				'value' => 0,
				'type' => 'checkbox',
			),
				
			'all' => array(
				'id' => 'all_display',
				'label' => qa_lang_html('admin/widget_all_pages'),
				'type' => 'checkbox',
				'tags' => 'NAME="template_all" ID="template_all"',
				'value' => is_numeric(strpos(','.@$editwidget['tags'].',', ',all,')),
			),

			'templates' => array(
				'id' => 'templates_display',
				'label' => qa_lang_html('admin/widget_pages_explanation'),
				'type' => 'custom',
				'html' => '',
			),
		),

		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html(isset($editwidget['widgetid']) ? 'main/save_button' : ('admin/add_widget_button')),
			),
			
			'cancel' => array(
				'tags' => 'NAME="docancel"',
				'label' => qa_lang_html('main/cancel_button'),
			),
		),
		
		'hidden' => array(
			'dosavewidget' => '1', // for IE
			'edit' => @$editwidget['widgetid'],
			'title' => @$editwidget['title'],
		),
	);
	
	if (method_exists($module, 'allow_template'))
		foreach ($templateoptions as $template => $langkey)
			if ($module->allow_template($template))
				$qa_content['form']['fields']['templates']['html'].=
					'<INPUT TYPE="checkbox" NAME="template_'.$template.'"'.
					(is_numeric(strpos(','.@$editwidget['tags'].',', ','.$template.',')) ? ' CHECKED' : '').
					'/>'.qa_lang_html($langkey).'<BR/>';
					
	if (isset($editwidget['widgetid']))
		qa_checkbox_to_display($qa_content, array(
			'templates_display' => '!(dodelete||template_all)',
			'all_display' => '!dodelete',
		));

	else {
		unset($qa_content['form']['fields']['delete']);
		qa_checkbox_to_display($qa_content, array(
			'templates_display' => '!template_all',
		));
	}
	
	if (!$widgetfound) {
		unset($qa_content['form']['fields']['title']['tight']);
		$qa_content['form']['fields']['title']['error']=qa_lang_html('admin/widget_not_available');
		unset($qa_content['form']['fields']['position']);
		unset($qa_content['form']['fields']['all']);
		unset($qa_content['form']['fields']['templates']);
		if (!isset($editwidget['widgetid']))
			unset($qa_content['form']['buttons']['save']);
		
	} elseif (!count($positionoptions)) {
		unset($qa_content['form']['fields']['title']['tight']);
		$qa_content['form']['fields']['title']['error']=qa_lang_html('admin/widget_no_positions');
		unset($qa_content['form']['fields']['position']);
		unset($qa_content['form']['fields']['all']);
		unset($qa_content['form']['fields']['templates']);
		unset($qa_content['form']['buttons']['save']);
	}

	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/