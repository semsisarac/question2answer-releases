<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-editor.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Editor module class for WYSIWYG editor plugin


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

	class qa_wysiwyg_editor {
		
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->urltoroot=$urltoroot;
		}
		
		function calc_quality($content, $format)
		{
			if ($format=='html')
				return 1.0;
			elseif ($format=='')
				return 0.8;
			else
				return 0;
		}
		
		function get_field(&$qa_content, $content, $format, $fieldname, $rows, $autofocus)
		{
			$qa_content['script_src'][]=$this->urltoroot.'ckeditor.js';
			$qa_content['script_onloads'][]="CKEDITOR.replace(".qa_js($fieldname).", {toolbar:[".
				"['Bold','Italic','Underline','Strike'],".
				"['Font','FontSize'],".
				"['TextColor','BGColor'],".
				"['Link','Unlink'],".
				"'/',".
				"['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],".
				"['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],".
				"['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar'],".
				"['RemoveFormat', 'Maximize']".
			"], defaultLanguage:".qa_js(qa_opt('site_language')).", skin:'v2', toolbarCanCollapse:false, removePlugins:'elementspath', resize_enabled:false, autogrow:false, startupFocus:".($autofocus ? 'true' : 'false').", entities:false})";
			
			if ($format=='html')
				$html=$content;
			else
				$html=qa_html($content, true);
			
			return array(
				'tags' => ' NAME="'.$fieldname.'" ',
				'value' => qa_html($html),
				'rows' => $rows,
			);
		}
		
		function read_post($fieldname)
		{
			$html=qa_post_text($fieldname);
			
			$htmlformatting=preg_replace('/<\s*\/?\s*(br|p)\s*\/?\s*>/i', '', $html); // remove <p>, <br>, etc... since those are OK in text
			
			if (preg_match('/<.+>/', $htmlformatting)) // if still some other tags, it's worth keeping in HTML
				return array(
					'format' => 'html',
					'content' => qa_sanitize_html($html), // qa_sanitize_html() is ESSENTIAL for security
				);
			
			else { // convert to text
				$viewer=qa_load_module('viewer', '');

				return array(
					'format' => '',
					'content' => $viewer->get_text($html, 'html', array())
				);
			}
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/