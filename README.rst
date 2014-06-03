=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.0 beta 2

- Second public version, 8th March 2010. A mostly complete preview of the 1.0 release, some small things left to do.

**New Features**

- Added comments on questions and answers.
- Added related questions.
- Many new notifications, e.g. if an answer is selected or a question is commented on.
- Allow the number of up votes and down votes for each post to be displayed separately.
- Allow voting to be enabled or disabled for questions or answers.
- Store and display user who last modified a post, and when they modified it.
- Users who log in after posting can reclaim the post as their own.
- Added website to user profile, which is linked with rel=nofollow.
- Added an admin option to insert custom content into page <HEAD>.

**Other Changes**

- Show gray arrows instead of nothing when a user cannot vote on their own post.
- Show more information about votes given and received on user page.
- Don't allow the author of a hidden post to reshow it, if it was hidden by an editor.
- Improved style of buttons and positioning of forms on question page.
- Improved speed of search within content of posts.
- Added 'Best answer' under star if an answer was selected.
- Show 'by anonymous' for a post if it was not by a registered user.
- Disallow user editing their profile on the user page (they use My Account).
- Allow voting in every case where vote counts are shown for a post.
- Changed order of input fields on register page to be more intuitive.
- Prompt users to enter hyphens to create multi-word tags.
- Show suggested next step at bottom of some more low-content pages.
- Don't show answer form on question pages by default unless it has no answers.
- Added link to online documentation if .htaccess file not working.
- Added individual CSS classes to buttons to allow different styling.

**Bug Fixes**

- Allow viewing of pages for tags containing ampersands (&).
- Allow clicking auto-complete tags with ampersands and $ signs.
- Disallow + and / characters in usernames due to URL problems.
- Set appropriate count to zero in words table if a word is no longer used.
- Make tabs and leading spaces visible in post content.
- Don't allow a comment or answer to be viewed out of context by requesting its postid.
- Include anonymous questions when counting how many times a user had their answer selected.
- Initiate Gzip page compression code earlier, to prevent content encoding errors on some setups.
- Fix database error when indexing content if a word was not added to the words table.
- Add DirectoryIndex and commented RewriteBase directives to .htaccess file.
- Ajax voting code now refreshes more HTML code, to get pluralization right.



.. _Question2Answer: http://www.question2answer.org/
