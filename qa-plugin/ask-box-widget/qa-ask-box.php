<?php

/*
	Question2Answer 1.4-dev (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/ask-box-widget/qa-ask-box.php
	Version: 1.4-dev
	Date: 2011-04-04 09:06:42 GMT
	Description: Widget module class for ask a question box


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

	class qa_ask_box {
		
		function allow_template($template)
		{
			$allow=false;
			
			switch ($template)
			{
				case 'activity':
				case 'categories':
				case 'feedback':
				case 'qa':
				case 'questions':
				case 'search':
				case 'tag':
				case 'tags':
				case 'unanswered':
					$allow=true;
					break;
			}
			
			return $allow;
		}
		
		function allow_region($region)
		{
			$allow=false;
			
			switch ($region)
			{
				case 'main':
				case 'side':
				case 'full':
					$allow=true;
					break;
			}
			
			return $allow;
		}
		
		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
?>
<FORM METHOD="POST" ACTION="./ask">
	<TABLE CLASS="qa-form-tall-table" STYLE="width:100%">
		<TR STYLE="vertical-align:middle;">
			<TD CLASS="qa-form-tall-label" STYLE="padding:8px; <?=($region=='side') ? 'padding-bottom:0;' : 'text-align:right;'?>" WIDTH="1">
				<?=strtr(qa_lang_html('question/ask_title'), array(' ' => '&nbsp;'))?>:
			</TD>
<?
			if ($region=='side') {
?>
		</TR>
		<TR>
<?			
			}
?>
			<TD CLASS="qa-form-tall-data" STYLE="padding:8px;" WIDTH="*">
				<INPUT NAME="title" TYPE="text" CLASS="qa-form-tall-text" STYLE="width:95%;">
			</TD>
		</TR>
	</TABLE>
	<INPUT TYPE="hidden" NAME="doask1" VALUE="1">
</FORM>
<?
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/