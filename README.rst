=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.0.1 beta

- Minor fixes with a focus on installation issues, 11th May 2010.

**New Features**

- Added more options for page URL structure.
- Extended language checking script (qa-check-lang.php) to test translations.

**Other Changes**

- Don't show reCAPTCHA on the feedback form if the user is logged in.
- When adding comments, switch user notification on by default.
- Improved error reporting when database installation or upgrade fails.

**Bug Fixes**

- Fixed viewing page for a tag which was used in two different accented forms.
- Fixed submitting comments and answers on questions with % symbols.
- Fixed QA_BASE_DIR on setups with no $_SERVER['SCRIPT_FILENAME'].
- Fixed installing on servers which can't use index.php/x style URLs (e.g. 1&1).
- Fixed vote display on list of recently answered questions on user pages.
- Don't allow answers to be converted into comments where comments aren't allowed.



.. _Question2Answer: http://www.question2answer.org/
