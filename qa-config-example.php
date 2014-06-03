<?php

/*
	Question2Answer 1.0.1-beta (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-config-example.php
	Version: 1.0.1-beta
	Date: 2010-05-11 12:36:30 GMT
	Description: After renaming, use this to set up database details and other stuff


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

/*
	======================================================================
	  THE 4 DEFINITIONS BELOW ARE REQUIRED AND MUST BE SET BEFORE USING!
	======================================================================
*/

	define('QA_MYSQL_HOSTNAME', '127.0.0.1');
	define('QA_MYSQL_USERNAME', 'your-mysql-username');
	define('QA_MYSQL_PASSWORD', 'your-mysql-password');
	define('QA_MYSQL_DATABASE', 'your-mysql-db-name');
	
/*
	Ultra-concise installation instructions:
	
	1. Create a MySQL database.
	2. Create a MySQL user with full permissions for that database.
	3. Rename this file to qa-config.php.
	4. Set the above four definitions and save.
	5. Place all the Question2Answer files on your server.
	6. Open the appropriate URL, and follow the instructions.

	More detailed installation instructions here: http://www.question2answer.org/
*/

/*
	======================================================================
	 OPTIONAL CONSTANT DEFINITIONS, INCLUDING SUPPORT FOR SINGLE SIGN-ON
	======================================================================

	QA_MYSQL_TABLE_PREFIX will be added to all table names, to allow multiple datasets
	in a single MySQL database, or to include the QA tables in an existing database.
*/

	define('QA_MYSQL_TABLE_PREFIX', 'qa_');

/*
	Flags for using external code - set to true if you're replacing default functions
	
	QA_EXTERNAL_LANG to use your language translation logic in qa-external/qa-external-lang.php
	QA_EXTERNAL_USERS to use your user identification code in qa-external/qa-external-users.php
	QA_EXTERNAL_EMAILER to use your email sending function in qa-external/qa-external-emailer.php
*/
	
	define('QA_EXTERNAL_USERS', false);
	define('QA_EXTERNAL_LANG', false);
	define('QA_EXTERNAL_EMAILER', false);

/*
	Some settings to help optimize your QA site's performance.
	
	QA_MAX_LIMIT_START is the maximum start parameter that can be requested. As this gets
	higher, queries tend to get slower, since MySQL must examine more information. Very high
	start numbers will usually only requested by search engine robots anyway.
	
	If a title word or tag is used QA_IGNORED_WORDS_FREQ times or more, it is ignored when
	searching or finding related questions. This saves time by ignoring words which are so
	common that they are probably not worth matching on.

	Set QA_OPTIMIZE_LOCAL_DB to true if your web server and MySQL are running on the same box.
	When viewing a page on your site, this will use several simple MySQL queries instead of one
	complex one, which makes sense since there is no latency for localhost access.
	
	Set QA_PERSISTENT_CONN_DB to true to use persistent database connections. Only use this if
	you are absolutely sure it is a good idea under your setup - generally it is not.
	For more information: http://www.php.net/manual/en/features.persistent-connections.php
	
	Set QA_DEBUG_PERFORMANCE to true to show detailed performance profiling information.
*/

	define('QA_MAX_LIMIT_START', 19999);
	define('QA_IGNORED_WORDS_FREQ', 10000);
	define('QA_OPTIMIZE_LOCAL_DB', false);
	define('QA_PERSISTENT_CONN_DB', false);
	define('QA_DEBUG_PERFORMANCE', false);
	
/*
	And lastly... if you want to, you can define any constant from qa-db-maxima.php in this
	file to override the default setting. Just make sure you know what you're doing!
*/
	
?>
