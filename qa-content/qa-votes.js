/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-votes.js
	Version: 1.0-beta-1
	Date: 2010-02-04 14:10:15 GMT


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

function qa_vote_click(elem, oldvote)
{
	var ens=elem.name.split('_');
	var postid=ens[1];
	var vote=parseInt(ens[2]);
	
	qa_ajax_post('qa-include/qa-ajax-vote.php', {postid:postid, vote:vote}, 
		function(response) {
			var lines=response.split("\n");
			
			if (lines[0]=='1') {
				var prefixshow={'voted_up_':vote>0, 'vote_up_':vote==0, 'vote_down_':vote==0, 'voted_down_':vote<0};
				for (prefix in prefixshow)
					document.getElementById(prefix+postid).style.display=prefixshow[prefix] ? '' : 'none';
				
				var newvote=parseInt(oldvote)+parseInt(vote);
				
				if (newvote>=1)
					var votehtml='+'+newvote;
				else if (newvote<=-1)
					var votehtml='&ndash;'+(-newvote);
				else
					var votehtml='0';
				
				document.getElementById('votes_'+postid).innerHTML=votehtml;

			} else if (lines[0]=='0') {
				var mess=document.getElementById('errorbox');
				
				if (!mess) {
					var mess=document.createElement('div');
					mess.id='errorbox';
					mess.className='qa-error';
					mess.innerHTML=lines[1];
				}
				
				var postelem=document.getElementById(postid);
				postelem.parentNode.insertBefore(mess, postelem);
			
			} else {
				alert('Failed to connect to site');
			}

		}
	);
	
	return false;
}

function qa_ajax_post(path, params, callback)
{
	var url=qa_root+path+'?qa_root='+escape(qa_root)+'&qa_request='+escape(qa_request);
	for (var key in params)
		url+='&'+escape(key)+'='+escape(params[key]);
	
	jx.load(url, callback, 'text', 'POST', {onError:callback});
}