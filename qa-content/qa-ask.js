/*
	Question2Answer 1.0-beta-3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-ask.js
	Version: 1.0-beta-3
	Date: 2010-03-31 12:13:41 GMT


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