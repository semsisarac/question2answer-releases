/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-ask.js
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: JS for ask page, for tag auto-completion


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

function qa_tag_click(link)
{
	var elem=document.getElementById('tags');
	var parts=qa_tag_typed_parts(elem);
	
	// removes any HTML tags and ampersand
	var tag=link.innerHTML.replace(/<[^>]*>/g, '').replace('&amp;', '&');
	
	// replace if matches typed, otherwise append
	var newvalue=(parts.typed && (tag.toLowerCase().indexOf(parts.typed.toLowerCase())>=0))
		? (parts.before+' '+tag+' '+parts.after+' ') : (elem.value+' '+tag+' ');
	
	// sanitize and set value
	elem.value=newvalue.replace(/[\s,]+/g, ' ').replace(/^\s+/g, '');

	elem.focus();
	qa_tag_hints();
		
	return false;
}

function qa_tag_hints(skipcomplete)
{
	var elem=document.getElementById('tags');
	var parts=qa_tag_typed_parts(elem);
	var html='';
	var completed=false;
			
	// space-separated existing tags
	var havelc=' '+elem.value.toLowerCase().replace(/[\s,]/g, ' ');
	
	// first try to auto-complete
	if (parts.typed && qa_tags_complete) {
		html=qa_tags_to_html(qa_tags_complete.split(' '), parts.typed.toLowerCase().replace('&', '&amp;'), null);
		completed=html ? true : false;
	}
	
	// otherwise show examples
	if (qa_tags_examples && !completed)
		html=qa_tags_to_html(qa_tags_examples.split(' '), null, null);
	
	// set title visiblity and hint list
	document.getElementById('tag_examples_title').style.display=(html && !completed) ? '' : 'none';
	document.getElementById('tag_complete_title').style.display=(html && completed) ? '' : 'none';
	document.getElementById('tag_hints').innerHTML=html;
}

function qa_tags_to_html(tags, matchlc, havelc)
{
	var html='';
	var added=0;
	
	for (var i=0; i<tags.length; i++) {
		var tag=tags[i];
		var taglc=tag.toLowerCase();
		
		if ( (!matchlc) || (taglc.indexOf(matchlc)>=0) ) // match if necessary
			if ( (!havelc) || (havelc.indexOf(' '+taglc+' ')<0) ) { // check if already entered
				if (matchlc) { // if matching, show appropriate part in bold
					var matchstart=taglc.indexOf(matchlc);
					var matchend=matchstart+matchlc.length;
					inner='<SPAN STYLE="font-weight:normal;">'+tag.substring(0, matchstart)+'<B>'+
						tag.substring(matchstart, matchend)+'</B>'+tag.substring(matchend)+'</SPAN>';
				} else // otherwise show as-is
					inner=tag;
					
				html+=qa_tag_template.replace(/\^/g, inner.replace('$', '$$$$'))+' '; // replace ^ in template, escape $s
				
				if (++added>=qa_tags_max)
					break;
			}
	}
	
	return html;
}

function qa_caret_from_end(elem)
{
	if (document.selection) { // for IE
		elem.focus();
		var sel=document.selection.createRange();
		sel.moveStart('character', -elem.value.length);
		
		return elem.value.length-sel.text.length;

	} else if (typeof(elem.selectionEnd)!='undefined') // other browsers
		return elem.value.length-elem.selectionEnd;

	else // by default return safest value
		return 0;
}

function qa_tag_typed_parts(elem)
{
	var caret=elem.value.length-qa_caret_from_end(elem);
	var active=elem.value.substring(0, caret);
	var passive=elem.value.substring(active.length);
	
	// if the caret is in the middle of a word, move the end of word from passive to active
	if (active.match(/[^\s,]$/) && (adjoinmatch=passive.match(/^[^\s,]+/))) {
		active+=adjoinmatch[0];
		passive=elem.value.substring(active.length);
	}
	
	// find what has been typed so far
	var typedmatch=active.match(/[^\s,]+$/) || [''];
	
	return {before:active.substring(0, active.length-typedmatch[0].length), after:passive, typed:typedmatch[0]};
}