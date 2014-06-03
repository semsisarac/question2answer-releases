=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.6 beta 1

- First beta of major feature upgrade, 6th June 2013.

**New Features**

For Users:

- Added walls for posting messages on user profile pages (if admin allows it).
- Added ability for anonymous users to include a name for display with their posts.
- Added highlighting of user favorite questions, tags, categories and users where those entities appear.
- Added more information to user's news feed so that it's clear why each item appears there.
- Added new and improved design for user profile pages in the default Snow theme.
- Added separate pages for each user showing their recent activity, all questions and all answers.
- Added option to show the number of views on question pages.
- Added avatar at top of default Snow theme for logged in users.
- Improved display of private message history to match user wall posts.

For Administrators:

- Added per-category privilege levels, e.g. to allow a user to be an expert in one category only.
- Added optional user moderation, where new users must wait for approval, or have reduced privileges.
- Added option to store all uploaded content in disk files rather than in the database.
- Added ability for privileged users to make silent edits to the content of posts.
- Added ability for custom user fields to be shown on the user registration form.
- Added option to control whether moderated posts show their time of creation or time of approval.
- Added mouseover showing who flagged and voted on each post, for privileged users only.
- Added option for posts to be queued again for moderation when they are edited.
- Added ability for some custom user fields to be shown to privileged users only.
- Added optional notification email for admin whenever a new user registers.
- Added option to moderate posts based on whether the author has enough points.
- Added count of flagged and queued posts in the admin sub-navigation menu.
- Improved layout of plugins section of admin panel to only show one set of options at a time.

For Developers:

- Added internal image upload API for use by WYSIWYG editor and other plugins.
- Added reorder_parts(), form_reorder_fields() and form_reorder_buttons() methods for reordering theme elements.
- Added qa-part-\* classes to HTML pages to allow targeting of specific page forms via CSS.
- Improved error reporting for eval()'d code from overrides and layers by showing the source file name.
- Improved error reporting in some other places by removing the @ sign where possible.


**Other Changes**

Security:

- Added protection from Cross-Site Request Forgery (CSRF) attacks in all forms.
- Information displayed for fatal errors no longer includes full file paths.
- No longer display failed database queries to the user.
- Blocked users and IPs are still able to view question pages.
- When hiding all posts from a user or IP address, also reject queued posts from that user/IP.

General:

- Users are warned if they have reached a rate limit before they write a new post.
- Posts which were hidden while queued for moderation can now be reshown.
- Facebook login now occurs on a separate page, for better future compatibility.
- If a question has no answers, don't include an empty <H2> tag on the question page.
- Removed qa-\*-hover CSS classes and related Javascript, whose only purpose was IE6 support.
- Updated to CKEditor 3.6.6 and Facebook PHP SDK 3.2.2.

For Developers:

- The load_module() method in modules is now passed the module type and name as extra parameters.
- Duplicate Javascript includes in $qa_content['script_src']) are now removed.
- Adding terminating semicolons (;) after all Javascript statements to allow Javascript compression methods.
- Language checking script now considers language phrases used in plugins.
- The functions below from Q2A 1.5.x have been removed or are not backwards compatible:
   - qa_page_q_add_a_form()
   - qa_page_q_add_c_form()
   - qa_page_q_comment_view()
   - qa_page_q_comment_follow_list()
- The new CSS classes below should be added to custom themes (start by copying from qa-theme/Default/qa-styles.css):
   - qa-message-item
   - qa-message-content
   - qa-message-avatar
   - qa-message-meta
   - qa-message-buttons
   - qa-q-favorited
   - qa-tag-favorited
   - qa-cat-favorited
   - qa-user-favorited
   - qa-nav-cat-favorited
   - qa-browse-cat-favorited
   - qa-cat-parent-favorited

**Bug Fixes**

- Prevent registration from an external user identity provider if the email already matches an account.
- Fixed display of avatars (and PHP error) for some list items when using external user integration.
- Moderation by third party filter modules is now reapplied when a hidden post is reshown by its author.
- Fixed erroneous updating of the last write IP address for some users when deleting an answer.
- Filtered out ASCII control characters from the content displayed in XML feeds.
- Fixed bug when creating a user from an external identity source on systems with case sensitive MySQL table names.
- Convert . to _ in variable names when building $_GET array to match PHP's behavior.
- Fixed bug where users with blank email addresses could generate an error by clicking to confirm.
- Prevent the deletion of an answer which had a related question asked, to avoid SQL error.
- Ensure that 'answered' and 'commented' links on question pages use the canonical URL for the question.
- Fixed invalid CSS class names for some individual navigation items.
- Fixed silent Javascript error in Snow theme on non-question pages.
- Removed login form on Snow theme when using external user integration.



.. _Question2Answer: http://www.question2answer.org/