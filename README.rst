=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.0 beta 3

- Third public version, 31st March 2010. Feature complete preview of the 1.0 release.

**New Features**

- Added second theme (Candy).
- Added support for reCAPTCHA for user registration and anonymous posts.
- Added optional tab for unanswered questions.
- Added ability for author or editor to convert an answer into a comment.
- Added 'Remember me' checkbox on login to store cookie in browser.
- Added admin panel showing hidden questions, answers and comments.
- Added admin option to be emailed when a new question is asked.
- Added number of comments to admin statistics page.
- Added switch for display of user points alongside usernames.

**Other Changes**

- Users now prevented from directly opening include files from their web browser.
- All files now use Windows (CRLF) line endings for easy editing.
- After posting an answer or comment, the page is scrolled to the new content.
- Logged in sessions no longer expire while the browser window is open.
- Duplicate submissions of answers or comments now detected.
- All users shown on the 'Users' page, even if they have no activity.

**Bug Fixes**

- Improve reliability of pop-up tooltips on buttons in Internet Explorer.
- When using external users, allow viewing of pages for users with no activity.
- Fixed requests to pages for tags or users with unusual characters.
- Fixed layout where a question has a large number of tags.
- Be forgiving if some $_SERVER elements are not set by the PHP environment.
- Changed date() to gmdate() to prevent warnings in some PHP versions.
- Fixed external user sample code to work with empty array parameters.
- Custom text is properly included in user welcome emails.



.. _Question2Answer: http://www.question2answer.org/
