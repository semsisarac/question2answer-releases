<?php

/*
	Question2Answer 1.0.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-maxima.php
	Version: 1.0.1
	Date: 2010-05-21 10:07:28 GMT
	Description: Definitions that determine database column size and rows retrieved


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


//	Maximum column sizes - any of these can be defined in qa-config.php to override the defaults below,
//	but you need to do so before creating the database, otherwise it's too late.

	@define('QA_DB_MAX_EMAIL_LENGTH', 80);
	@define('QA_DB_MAX_HANDLE_LENGTH', 20);
	@define('QA_DB_MAX_TITLE_LENGTH', 800);
	@define('QA_DB_MAX_CONTENT_LENGTH', 8000);
	@define('QA_DB_MAX_TAGS_LENGTH', 800);
	@define('QA_DB_MAX_WORD_LENGTH', 80);
	@define('QA_DB_MAX_OPTION_TITLE_LENGTH', 40);
	@define('QA_DB_MAX_PROFILE_TITLE_LENGTH', 40);
	@define('QA_DB_MAX_PROFILE_CONTENT_LENGTH', 8000);

//	How many records to retrieve for different circumstances. In many cases we retrieve more records than we
//	end up needing to display once we know the value of an option. Wasteful, but allows one query per page.

	@define('QA_DB_RETRIEVE_QS_AS', 50);
	@define('QA_DB_RETRIEVE_TAGS', 200);
	@define('QA_DB_RETRIEVE_USERS', 200);
	@define('QA_DB_RETRIEVE_ASK_TAG_QS', 500);
	@define('QA_DB_RETRIEVE_COMPLETE_TAGS', 10000);


/*
	Omit PHP closing tag to help avoid accidental output
*/