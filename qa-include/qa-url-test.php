<?php
	
/*
	Question2Answer 1.0.1-beta (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-url-test.php
	Version: 1.0.1-beta
	Date: 2010-05-11 12:36:30 GMT
	Description: Sits in an iframe and shows a green page with word 'OK'


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

	if (@$_GET['param']=='$&-_#%@') {
		require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	
		echo '<HTML><BODY STYLE="margin:0; padding:0;">';
		echo '<TABLE WIDTH="100%" HEIGHT="100%" CELLSPACING="0" CELLPADDING="0">';
		echo '<TR VALIGN="middle"><TD ALIGN="center" STYLE="border-style:solid; border-width:1px; background-color:#fff; ';
		echo qa_admin_url_test_html();
		echo 'TD></TR></TABLE>';
		echo '</BODY></HTML>';
	}

	exit;
?>