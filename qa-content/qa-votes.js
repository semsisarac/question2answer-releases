/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-votes.js
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: JS to handle Ajax voting


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

function qa_vote_click(elem, oldvote)
{
	var ens=elem.name.split('_');
	var postid=ens[1];
	var vote=parseInt(ens[2]);
	var anchor=ens[3];
	
	qa_ajax_post('vote', {postid:postid, vote:vote},
		function(lines) {
			if (lines[0]=='1') {
				document.getElementById('voting_'+postid).innerHTML=lines.slice(1).join("\n");

			} else if (lines[0]=='0') {
				var mess=document.getElementById('errorbox');
				
				if (!mess) {
					var mess=document.createElement('div');
					mess.id='errorbox';
					mess.className='qa-error';
					mess.innerHTML=lines[1];
				}
				
				var postelem=document.getElementById(anchor);
				postelem.parentNode.insertBefore(mess, postelem);
			
			} else {
				alert('Unexpected response from server - please try again.');
			}

		}
	);
	
	return false;
}

function qa_ajax_post(operation, params, callback)
{
	var url=qa_root+'?qa=ajax&qa_operation='+operation+'&qa_root='+encodeURIComponent(qa_root)+'&qa_request='+encodeURIComponent(qa_request);
	for (var key in params)
		url+='&'+encodeURIComponent(key)+'='+encodeURIComponent(params[key]);
	
	jx.load(url, function(response) {
		var header='QA_AJAX_RESPONSE';
		var headerpos=response.indexOf(header);
		
		if (headerpos>=0)
			callback(response.substr(headerpos+header.length).replace(/^\s+/, '').split("\n"));
		else
			callback([]);

	}, 'text', 'POST', {onError:callback});
}