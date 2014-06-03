=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.5 beta 1

- First beta of major feature update, 14th December 2011, also on GitHub_.

**Major New Features**

For Users and Administrators:

- Added moderation of posts by anonymous or unconfirmed users, or users with insufficient points.
- Added ability to close a question as a duplicate of another question, or with an explanatory note.
- Added user favoriting of questions, users, tags and categories.
- Added personalized news feeds, showing activity for users' favorites and responses to their content.
- Added mass mailing, allowing admins to send an email to all registered users (with opt out).
- Added Ajax support for adding answers and comments, instead of requiring a page refresh.
- Added Ajax support for many other one-click operations on answers and comments.
- Added option to send all emails directly via SMTP, rather than PHP's mail() function.

For Developers:

- Added overrides, allowing plugins to replace or wrap over 150 core Q2A functions.
- Added plugin filter modules which validate and/or modify many types of user input.
- Added plugin search modules which implement a custom indexer and/or search engine.
- Added plugin captcha modules which provide human verification interfaces like reCAPTCHA.
- Added plugin process modules which can run code at specific stages of Q2A's response processing.
- Added init_queries() method allowing plugin modules to perform some database setup.
- Added ability for plugins to register their own language files for easy internationalization.
- Added tables for extendable meta information on posts, users, tags and categories (see qa-db-metas.php).

**Other New Features**

Functionality:

- Added ability to store an archive of private messages, showing users their previous correspondence.
- Added option to show a different theme to users on mobile devices.
- Added option to add a custom field to questions, and display its value publicly on question pages.
- Added optional custom messages on ask, answer, comment and registration forms.
- Added option to set whether users are allowed to answer their own questions.
- Added option to automatically close questions if an answer was selected.

Navigation:

- Added paging of answers on a question page, with an option of how many per page.
- Added option to shorten comment lists beyond a certain length, and show all by clicking.
- Moved related questions to a widget which can also be displayed on the right of question pages.
- Page elements now appear or disappear using jQuery slide and fade effects.
- Extended 'Unanswered' page to list questions with no selected answer or no upvoted answers.
- Allow browsing by category on all listing pages if QA_ALLOW_UNINDEXED_QUERIES is set in qa-config.php.
- Added category descriptions as popups in the category navigation, and below the question category selector.
- Added option to show full dates if posts are more than a certain number of days old.
- Added option not to show 'Ask a Question' link in main navigation.
- Page listing activity for a user now includes recent edits by the user.
- Added information about posts that were closed, moved, retagged, etc... to the 'All Activity' page.
- Added <LINK REL="prev"...> and <LINK REL="next"...> tags for search-engine friendly pagination.

Administration:

- Added new admin panels listing flagged and queued posts, accessible to non-admins where appropriate.
- Added Ajax buttons for one-click operations on hidden, queued and flagged posts in admin panel.
- Added ability for admins to manually assign bonus points to particular users.
- Added update notices displayed at the top of the page for first-time visitors or new registered users.
- Added separate permission setting for downvoting, to prevent negative behavior.
- Added separate permission setting for retagging and recategorizing questions.
- Added easy phrase customization, using a qa-lang-\*.php file in the qa-lang/custom directory.
- Added ability to make custom pages or links only visible to users of a certain level.
- Added separate points settings for receiving up vs down votes on posts.
- Page listing activity from an IP address now shows whether posts are already hidden.
- Added option requiring email address confirmation to complete registration.
- Added limit on new user registrations per IP address per hour.
- Added ability to completely delete users from the database.
- Added display of the Q2A build date and database size in the 'Stats' section of the admin panel.

For Developers:

- Added ability for plugins to display per-user update notices at the top of Q2A pages (see qa-db-notices.php).
- Added support for theme metadata in qa-styles.css files, for display in admin panel.
- Added support for online version checking of third-party themes and plugins.
- Added $this->context in theme class to help theme functions find out more about what they're showing.
- Added some additional useful elements in the $params array for some events sent to event modules.
- If QA_DEBUG_PERFORMANCE is on, show number or rows and columns returned by each database query.
- Layer classes are now renamed to include their source file, making error debugging easier.
- The qa_fatal_error() function now shows a backtrace of the calling stack.

**Other Changes**

- Updated to CKEditor 3.6.2, jQuery 1.7.1, htmLawed 1.1.10 and Google's reCAPTCHA.
- Facebook Login plugin migrated to OAuth 2.0 and Facebook PHP SDK 3.1.1.
- The login rate limit per IP address now applies to successful as well as unsuccessful login attempts.
- Shortened and clarified descriptions for many admin options.
- Added support for Bengali and Traditional Chinese languages.
- Retrieve options in a separate database query, unless QA_OPTIMIZE_DISTANT_DB is set in qa-config.php.
- Removed QA_EXTERNAL_LANG and QA_EXTERNAL_EMAILER from qa-config.php - use plugin overrides instead.
- Changed URL structure for retrieving user avatar image JPEGs.
- Editor modules can define load_script(), focus_script() and update_script() methods to work with Ajax.

**Bug Fixes**

- Fixed HTML escaping of titles of similar questions displayed on 'Ask a Question' page.
- Fixed application of permission setting requiring email confirmation and a minimum number of points.
- Fixed problem integrating with WordPress in network/multisite mode.
- Fixed high level functions in qa-app-posts.php to work with external user management.
- Fixed bug where including # or $ in some URLs could lead to a database query error.
- Worked around MySQL deadlock errors (errno 1213) by waiting a short time then retrying.

**Key Source Code Changes**

- Removed all use of global variables outside of functions, to enable Q2A to run entirely within a local scope.
- Moved reCAPTCHA functionality to a plugin, and activity count and ask box widgets to core modules.
- The database connection is now set to UTF-8 encocding, so BINARY casts and _utf8 introducers no longer needed.
- Removed version information from all files except qa-base.php to enable easier forking and diffing.
- The functions below from Q2A 1.4.x have been removed or are not backwards compatible:

qa_answer_validate() qa_base_db_connect() qa_base_db_disconnect()
qa_captcha_error()
qa_captcha_html()
qa_captcha_possible()
qa_captcha_validate()
qa_comment_validate()
qa_cookie_report_action()
qa_db_categoryslugs_sql()
qa_db_posts_recount()
qa_db_tag_count_qs_selectspec()
qa_db_unanswered_qs_selectspec()
qa_feed_load_ifcategory()
qa_handle_email_validate()
qa_length_validate()
qa_logged_in_user_selectspec()
qa_notify_validate()
qa_options_load_options()
qa_options_pending_selectspecs()
qa_page_q_add_c_form()
qa_page_q_comment_follow_list()
qa_page_q_do_comment()
qa_page_q_edit_a_form()
qa_page_q_edit_c_form()
qa_page_q_edit_q_form()
qa_page_q_load_q()
qa_page_q_post_rules()
qa_post_index()
qa_post_parent_to_answer()
qa_profile_field_validate()
qa_question_validate()
qa_search_max_match_anchor()
qa_user_report_action()
qa_vote_error_html()

- The new CSS classes below should be added to custom themes (start by copying from qa-theme/Default/qa-styles.css):

qa-activity-count
qa-activity-count-item
qa-activity-count-data
qa-c-item-expand
qa-favorite-button
qa-favorite-hover
qa-favorite-image
qa-favoriting
qa-form-tall-suffix
qa-notice-close-button
qa-notice-close-hover
qa-notice
qa-q-item-buttons
qa-q-item-content
qa-q-item-flags
qa-q-view-closed-content
qa-q-view-closed
qa-q-view-extra-content
qa-q-view-extra
qa-related-q-item
qa-related-q-list
qa-unfavorite-button
qa-unfavorite-hover


.. _Question2Answer: http://www.question2answer.org/
.. _GitHub: https://github.com/q2a/question2answer