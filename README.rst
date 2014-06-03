=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.5

- Final release of major feature update, 18th January 2012.

**New Features**

- Added admin option to allow users to log in by email only (i.e. not by username).

**Bug Fixes**

- Fixed reading of notification checkbox value when submitting answer or comment forms via Ajax.
- Fixed bug where question page jumps immediately to the answer form if it is shown.
- Fixed 'reply' buttons on question comments if Javascript is disabled or a comment was just added.
- Apply moderation settings correctly if anonymous users are treated less strictly than some registered users.
- Fixed error with passing func_get_args() as a parameter that appears in some versions of PHP.




.. _Question2Answer: http://www.question2answer.org/