<?php
	
/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-lang-emails.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: Language phrases for email notifications


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

	return array(
		'a_commented_body' => "Your answer on ^site_title has a new comment:\n\n^c_content\n\nYour answer was:\n\n^c_context\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
		'a_commented_subject' => 'Your ^site_title answer has a new comment',
		'a_followed_body' => "Your answer on ^site_title has a new related question:\n\n^q_title\n\nYour answer was:\n\n^a_content\n\nClick below to answer the new question:\n\n^url\n\nThank you,\n\n^site_title",
		'a_followed_subject' => 'Your ^site_title answer has a related question',
		'a_selected_body' => "Congratulations! Your answer on ^site_title has just been selected as the best:\n\n^a_content\n\nThe question was:\n\n^q_title\n\nClick below to see your answer:\n\n^url\n\nThank you,\n\n^site_title",
		'a_selected_subject' => 'Your ^site_title answer has been selected!',
		'c_commented_body' => "A new comment has been added after your comment on ^site_title:\n\n^c_content\n\nThe discussion is following:\n\n^c_context\n\nYou may respond by adding another comment:\n\n^url\n\nThank you,\n\n^site_title",
		'c_commented_subject' => 'Your ^site_title comment has been added to',
		'feedback_body' => "Comments:\n^message\n\nName:\n^name\n\nEmail:\n^email\n\nPrevious page:\n^previous\n\nUser:\n^url\n\nIP address:\n^ip\n\nBrowser:\n^browser",
		'feedback_subject' => '^ feedback',
		'new_password_body' => "^Your new password for ^site_title is below.\n\nPassword: ^password\n\nIt is recommended to change this password immediately after logging in.\n\nThank you,\n^site_title\n^url",
		'new_password_subject' => '^site_title - Your New Password',
		'q_answered_body' => "Your question on ^site_title has just been answered:\n\n^a_content\n\nYour question was:\n\n^q_title\n\nIf you like this answer, you may select it as the best:\n\n^url\n\nThank you,\n\n^site_title",
		'q_answered_subject' => 'Your ^site_title question was answered',
		'q_commented_body' => "Your question on ^site_title has a new comment:\n\n^c_content\n\nYour question was:\n\n^c_context\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
		'q_commented_subject' => 'Your ^site_title question has a new comment',
		'q_posted_body' => "A new question has been asked on ^site_title:\n\n^q_title\n\n^q_content\n\nClick below to see the question:\n\n^url\n\nThank you,\n\n^site_title",
		'q_posted_subject' => '^site_title has a new question',
		'reset_body' => "Please click below to reset your password for ^site_title.\n\n^url\n\nAlternatively, enter the code below into the field provided.\n\nCode: ^code\n\nIf you did not ask to reset your password, please ignore this message.\n\nThank you,\n^site_title",
		'reset_subject' => '^site_title - Reset Forgotten Password',
		'welcome_body' => "Thank you for registering for ^site_title.\n\n^customYour login details are as follows:\n\nEmail: ^email\nPassword: ^password\n\nPlease keep this information safe for future reference.\n\nThank you,\n\n^site_title\n^url",
		'welcome_subject' => 'Welcome to ^site_title!',
	);
	
?>