=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.0

- Final release of version 1.0, 9th April 2010.

**New Features**

- Added reCAPTCHA support for feedback and forgot password pages.
- Added documentation for functions in the source code.

**Other Changes**

- Show the site name on its own in the HTML title of the home page.
- Pass the page request to the theme class to enable custom pages.
- Added individual CSS classes to navigation items to allow per-item styling.
- Apply rate limits when claiming a post or converting an answer to a comment.
- Improved aesthetics and wording during the installation process.
- Doubled the height of the text area when adding or editing comments.
- Stopped using several PHP functions which cause warnings under PHP 5.3.
- Email field now focuses when anonymous users click the checkbox above.

**Bug Fixes**

- Fixed incorrect reference to $qa_db when sending notification of new questions.
- Fixed ugly mouseover effect for icon buttons in Candy theme.
- Fixed error given by some MySQL configurations when logging out.



.. _Question2Answer: http://www.question2answer.org/
