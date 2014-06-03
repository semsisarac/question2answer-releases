=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.0.1

- Cleared up some remaining issues related to URL paths, 23rd May 2010.

**Other Changes**

- Removed PHP closing tags (?>) to help prevent accidental output by users modifying files.

**Bug Fixes**

- Substituted encodeURIComponent() for escape() in Javascript to preserve Unicode.
- Fixed some problems with the new URL structures introduced in 1.0.1 beta.
- Introduced a more rigorous URL structure test and fixed some edge cases.



.. _Question2Answer: http://www.question2answer.org/
