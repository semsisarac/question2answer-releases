=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.4.1

- Minor fixes and source code changes from 1.4 release, 10th July 2011.

**Bug Fixes**

- Fixed MySQL error when setting a vote or flag on a post, if MySQL is in strict mode.
- Fixed potential security issue in some browsers by escaping / slashes in Javascript variable values.

**Other Changes**

- Added optional RSS feed for hot questions.
- When converting an answer to a comment, only mark the post as edited if its content changed.
- Retrieve user's IP address through new function qa_remote_ip_address() in qa-base.php.
- Added link to the language checking page in the admin interface, to encourage complete translations.
- Added white-space:nowrap; style to prompt in Ask Box Widget, to prevent wrapping in some languages.



.. _Question2Answer: http://www.question2answer.org/
