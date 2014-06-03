/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-admin.js
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: JS for admin pages to handle Ajax-triggered recalculations


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

var qa_recalc_running=0;

window.onbeforeunload=function(event)
{
	if (qa_recalc_running>0) {
		event=event||window.event;
		var message=qa_warning_recalc;
		event.returnValue=message;
		return message;
	}
}

function qa_recalc_click(elem, value, noteid)
{
	if (elem.qa_recalc_running) {
		elem.qa_recalc_stopped=true;
	
	} else {
		elem.qa_recalc_running=true;
		elem.qa_recalc_stopped=false;
		qa_recalc_running++;
		
		document.getElementById(noteid).innerHTML='';
		elem.qa_original_value=elem.value;
		elem.value=value;
		
		qa_recalc_update(elem, elem.name, noteid);
	}
	
	return false;
}

function qa_recalc_update(elem, state, noteid)
{
	if (state)
		qa_ajax_post('qa-include/qa-ajax-recalc.php', {state:state},
			function(response) {
				var lines=response.replace(/^\s+/, '').split("\n");
				
				if (lines[0]=='1') {
					if (lines[2])
						document.getElementById(noteid).innerHTML=lines[2];
					
					if (elem.qa_recalc_stopped)
						qa_recalc_cleanup(elem);
					else
						qa_recalc_update(elem, lines[1], noteid);
				
				} else if (lines[0]=='0') {
					document.getElementById(noteid).innerHTML=lines[2];
					qa_recalc_cleanup(elem);
				
				} else {
					alert('Failed to connect to site');
					qa_recalc_cleanup(elem);
				}
			}
		);

	else
		qa_recalc_cleanup(elem);
}

function qa_recalc_cleanup(elem)
{
	elem.value=elem.qa_original_value;
	elem.qa_recalc_running=null;
	qa_recalc_running--;
}

function qa_ajax_post(path, params, callback)
{
	var url=qa_root+path+'?qa_root='+escape(qa_root)+'&qa_request='+escape(qa_request);
	for (var key in params)
		url+='&'+escape(key)+'='+escape(params[key]);
	
	jx.load(url, callback, 'text', 'POST', {onError:callback});
}