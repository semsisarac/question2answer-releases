<?php
	
/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-rewrite-test.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Sits in an iframe and shows whether .htaccess file is installed and working


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

	echo '<HTML><BODY STYLE="margin:0; padding:0; background-color:white;">';
	echo '<TABLE WIDTH="100%" HEIGHT="100%" CELLSPACING="0" CELLPADDING="0">';
	echo '<TR VALIGN="middle"><TD STYLE="font-size:11px; font-weight:bold; font-family:arial,sans-serif;">&nbsp; ';
	
	if ($qa_request=='rewrite-pass') // .htaccess converts requests to rewrite-test into rewrite-pass, so if conversion happened
		echo '.htaccess OK - safe to use!';
	else
		echo '<FONT COLOR="#FF0000">.htaccess not OK <A HREF="http://www.question2answer.org/htaccess.php" TARGET="_blank">[?]</A> - do not use!</FONT>';
		
	echo '</TD></TR></TABLE>';
	
	echo '</BODY></HTML>';

	exit;
?>