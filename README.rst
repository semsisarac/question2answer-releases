=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.4.2

- Minor fixes and improvements, 12th September 2011.

**New Features**

- Added the ability for administrators to remove a user's avatar.
- Added category question pages and category pages in XML sitemap, with options for control.
- Allowed widgets to be embedded in custom pages.
- Added Welsh (Cymraeg) to list of supported languages.

**Bug Fixes**

- Added ideographic space, comma and full stops as recognized punctuation, for CJK support.
- Fixed bug which removed periods (.) from the names of files uploaded in WYSIWYG editor.
- Display tag auto-suggestion and auto-completion even if a tag error message is showing.
- Worked around bug in MySQL 4.1.22 which affected retrieval of category information.
- Added synchronization to prevent duplicate users being created from external identity providers.
- Fixed PHP warning messages which appear under several unusual circumstances.

**Code Changes**

- Treat false or '' responses from option_default() module functions the same as null.
- Added raw user information in $qa_content['raw'] on user-related pages, for access by custom themes or layers.



.. _Question2Answer: http://www.question2answer.org/
