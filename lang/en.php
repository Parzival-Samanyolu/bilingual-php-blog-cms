<?php

declare(strict_types=1);

/**
 * English (en) UI strings for My Blog.
 *
 * Every key here MUST have an identical counterpart in lang/tr.php.
 * Values may contain {placeholder} tokens replaced by __($key, ['placeholder' => ...]).
 *
 * @return array<string,string>
 */
return [
    // --- Branding ---------------------------------------------------------
    'site_name'          => 'My Blog',
    'site_tagline'       => 'Knowledge & encyclopedia platform',

    // --- Navigation -------------------------------------------------------
    'nav_home'           => 'Home',
    'nav_search'         => 'Search',
    'nav_login'          => 'Log in',
    'nav_register'       => 'Sign up',
    'nav_logout'         => 'Log out',
    'nav_profile'        => 'My profile',
    'nav_dashboard'      => 'Author panel',
    'nav_admin'          => 'Admin',
    'nav_categories'     => 'Categories',
    'nav_about'          => 'About',
    'nav_contact'        => 'Contact',
    'nav_menu'           => 'Menu',

    // --- Search -----------------------------------------------------------
    'search_placeholder' => 'Search articles, topics or keywords...',
    'search_results_for' => 'Search results for "{query}"',
    'search_button'      => 'Search',
    'no_results'         => 'No results found.',
    'no_results_hint'    => 'Try different keywords or clear the filters.',
    'filter_category'    => 'Filter by category',
    'filter_tag'         => 'Filter by tag',
    'filter_all'         => 'All',
    'filter_apply'       => 'Apply',
    'filter_clear'       => 'Clear filters',
    'results_count'      => '{count} results',

    // --- Article actions --------------------------------------------------
    'btn_read_more'      => 'Read more',
    'btn_like'           => 'Like',
    'btn_bookmark'       => 'Bookmark',
    'btn_share'          => 'Share',
    'btn_liked'          => 'Liked',
    'btn_bookmarked'     => 'Bookmarked',
    'share_copy_link'    => 'Copy link',
    'share_copied'       => 'Link copied',
    'share_twitter'      => 'Share on X',
    'share_facebook'     => 'Share on Facebook',
    'share_whatsapp'     => 'Share on WhatsApp',

    // --- Article meta labels ---------------------------------------------
    'label_views'        => 'views',
    'label_comments'     => 'comments',
    'label_likes'        => 'likes',
    'label_author'       => 'Author',
    'label_date'         => 'Date',
    'label_category'     => 'Category',
    'label_tags'         => 'Tags',
    'label_updated'      => 'Updated',
    'label_reading_time' => 'read',
    'minutes_short'      => 'min',

    // --- Related / listings ----------------------------------------------
    'related_articles'   => 'Related Articles',
    'latest_articles'    => 'Latest Articles',
    'trending'           => 'Trending',
    'categories_heading' => 'Categories',
    'tags_heading'       => 'Tags',
    'more_in_category'   => 'More in this category',
    'view_all'           => 'View all',

    // --- Comments ---------------------------------------------------------
    'comments_heading'   => 'Comments',
    'leave_comment'      => 'Leave a comment',
    'comment_placeholder' => 'Write your comment...',
    'comment_submit'     => 'Post comment',
    'comment_reply'      => 'Reply',
    'comment_pending'    => 'Your comment is awaiting approval.',
    'comment_success'    => 'Your comment was submitted and will appear once approved.',
    'login_to_comment'   => 'Log in to leave a comment.',
    'no_comments'        => 'No comments yet. Be the first to comment.',

    // --- Pagination -------------------------------------------------------
    'pagination_prev'    => 'Previous',
    'pagination_next'    => 'Next',
    'pagination_page'    => 'Page {page}',
    'pagination_of'      => 'Page {current} of {total}',

    // --- Auth: login / register ------------------------------------------
    'login_title'        => 'Log in',
    'register_title'     => 'Sign up',
    'label_name'         => 'Full name',
    'label_username'     => 'Username',
    'label_email'        => 'Email',
    'label_password'     => 'Password',
    'label_password_confirm' => 'Confirm password',
    'btn_login'          => 'Log in',
    'btn_register'       => 'Sign up',
    'remember_me'        => 'Remember me',
    'forgot_password'    => 'Forgot password',
    'or_continue_with'   => 'or continue with',
    'login_with_google'  => 'Log in with Google',
    'no_account'         => 'Don\'t have an account?',
    'have_account'       => 'Already have an account?',
    'register_as_author' => 'Register as author',
    'register_as_reader' => 'Register as reader',
    'register_role_hint' => 'Author accounts can publish articles after admin approval.',
    'pending_approval_message' => 'Your author application has been received. You will be notified once an admin approves your account.',

    // --- Profile ----------------------------------------------------------
    'profile_title'      => 'Profile',
    'profile_bio'        => 'About',
    'profile_articles'   => 'Articles',
    'profile_bookmarks'  => 'Bookmarks',
    'profile_edit'       => 'Edit profile',
    'profile_avatar'     => 'Profile photo',

    // --- Admin ------------------------------------------------------------
    'admin_dashboard'    => 'Dashboard',
    'admin_articles'     => 'Articles',
    'admin_categories'   => 'Categories',
    'admin_users'        => 'Users',
    'admin_comments'     => 'Comments',
    'admin_tags'         => 'Tags',
    'admin_settings'     => 'Settings',
    'admin_pending_authors' => 'Pending Authors',
    'admin_pending_comments' => 'Pending Comments',
    'admin_stats'        => 'Statistics',

    // --- Status labels ----------------------------------------------------
    'status_draft'       => 'Draft',
    'status_pending'     => 'Pending',
    'status_published'   => 'Published',
    'status_rejected'    => 'Rejected',

    // --- Generic buttons --------------------------------------------------
    'btn_approve'        => 'Approve',
    'btn_reject'         => 'Reject',
    'btn_delete'         => 'Delete',
    'btn_edit'           => 'Edit',
    'btn_save'           => 'Save',
    'btn_cancel'         => 'Cancel',
    'btn_submit'         => 'Submit',
    'btn_publish'        => 'Publish',
    'btn_new'            => 'Add new',
    'btn_back'           => 'Back',
    'btn_ban'            => 'Ban',
    'btn_unban'          => 'Unban',
    'confirm_delete'     => 'Are you sure you want to delete this?',

    // --- Flash messages ---------------------------------------------------
    'flash_success'      => 'The operation completed successfully.',
    'flash_error'        => 'Something went wrong. Please try again.',
    'flash_saved'        => 'Your changes have been saved.',
    'flash_deleted'      => 'The record has been deleted.',
    'flash_unauthorized' => 'You are not authorized to perform this action.',
    'flash_login_required' => 'You must log in to continue.',
    'flash_csrf'         => 'Session validation failed. Please try again.',

    // --- Static pages -----------------------------------------------------
    'about_page_title'   => 'About',
    'contact_page_title' => 'Contact',
    'contact_name'       => 'Your name',
    'contact_email'      => 'Your email address',
    'contact_subject'    => 'Subject',
    'contact_message'    => 'Your message',
    'contact_send'       => 'Send message',
    'contact_success'    => 'Your message has been sent. We will get back to you shortly.',
    'contact_error'      => 'Your message could not be sent. Please try again later.',

    // --- Footer -----------------------------------------------------------
    'footer_about'       => 'My Blog is a knowledge platform offering reliable, in-depth information across many fields, notably technology and history.',
    'footer_links'       => 'Links',
    'footer_follow'      => 'Follow us',
    'copyright'          => '© {year} My Blog — All rights reserved.',

    // --- Language switcher ------------------------------------------------
    'lang_tr'            => 'Türkçe',
    'lang_en'            => 'English',
    'lang_switch'        => 'Switch language',

    // --- Errors -----------------------------------------------------------
    'error_404_title'    => 'Page not found',
    'error_404_message'  => 'The page you are looking for may have moved or never existed.',
    'error_500_title'    => 'Something went wrong',
    'error_500_message'  => 'An unexpected error occurred. Please try again later.',
    'error_back_home'    => 'Back to home',

    // --- Home / hero / sections (used by views) ---
    'hero_heading'        => 'Your gateway to knowledge',
    'hero_subtitle'       => 'Reliable, free encyclopedia content across thousands of topics — from technology to history.',
    'search_prompt'       => 'What would you like to learn?',
    'section_trending'    => 'Trending',
    'section_latest'      => 'Latest',
    'section_categories'  => 'Categories',
    'subcategories'       => 'Subcategories',
    'no_comments_yet'     => 'No comments yet. Be the first to comment.',
    'btn_submit_comment'  => 'Post Comment',
    'comment_moderation_note' => 'Your comment will appear once it has been approved.',
    'pagination_label'    => 'Pagination',
    'footer_pages'        => 'Pages',
    'footer_rights'       => 'All rights reserved.',

    // --- About page ---
    'about_lead'              => 'My Blog is a multilingual encyclopedia platform dedicated to free, accessible knowledge for everyone.',
    'about_mission_title'     => 'Our Mission',
    'about_mission_body'      => 'To provide accurate, unbiased and well-sourced knowledge in Turkish and English, free of charge.',
    'about_editorial_title'   => 'Editorial Principles',
    'about_editorial_body'    => 'Every article is reviewed and approved by our editorial team before publication.',
    'about_contribute_title'  => 'Contribute',
    'about_contribute_body'   => 'Apply as an author and, once approved, publish your own articles.',

    // --- Contact page ---
    'contact_lead'          => 'Get in touch for questions, suggestions or collaborations.',
    'contact_field_name'    => 'Your name',
    'contact_field_email'   => 'Your email',
    'contact_field_subject' => 'Subject',
    'contact_field_message' => 'Your message',
    'contact_submit'        => 'Send',
    'contact_direct_label'  => 'Direct email',

    // --- Author editor / dashboard ---
    'editor_new_title'  => 'New Article',
    'editor_edit_title' => 'Edit Article',
    'dashboard_title'   => 'Author Dashboard',

    // --- Admin: chrome ---
    'admin_panel'         => 'Admin Panel',
    'admin_view_site'     => 'View Site',
    'admin_logout'        => 'Log out',
    'admin_nav_dashboard' => 'Dashboard',
    'admin_nav_articles'  => 'Articles',
    'admin_nav_categories' => 'Categories',
    'admin_nav_users'     => 'Users',
    'admin_nav_comments'  => 'Comments',
    'admin_nav_tags'      => 'Tags',
    'admin_nav_settings'  => 'Settings',

    // --- Admin: dashboard stats ---
    'admin_stat_published'         => 'Published',
    'admin_stat_drafts'            => 'Drafts',
    'admin_stat_pending_articles'  => 'Pending Articles',
    'admin_stat_pending_comments'  => 'Pending Comments',
    'admin_stat_users'             => 'Users',
    'admin_stat_views'             => 'Views',
    'admin_recent_pending_articles' => 'Recent Pending Articles',
    'admin_recent_pending_comments' => 'Recent Pending Comments',
    'admin_no_pending_articles'    => 'No articles awaiting approval.',
    'admin_no_pending_comments'    => 'No comments awaiting approval.',
    'admin_no_articles'            => 'No articles found.',
    'admin_no_categories'          => 'No categories found.',

    // --- Admin: table columns ---
    'admin_col_title'    => 'Title',
    'admin_col_author'   => 'Author',
    'admin_col_category' => 'Category',
    'admin_col_status'   => 'Status',
    'admin_col_lang'     => 'Language',
    'admin_col_views'    => 'Views',
    'admin_col_actions'  => 'Actions',
    'admin_col_name'     => 'Name',
    'admin_col_role'     => 'Role',
    'admin_col_user'     => 'User',
    'admin_col_comment'  => 'Comment',
    'admin_col_sort'     => 'Order',

    // --- Admin: actions ---
    'admin_action_edit'    => 'Edit',
    'admin_action_delete'  => 'Delete',
    'admin_action_publish' => 'Publish',
    'admin_action_approve' => 'Approve',
    'admin_action_reject'  => 'Reject',
    'admin_action_review'  => 'Review',
    'admin_action_save'    => 'Save',
    'admin_action_cancel'  => 'Cancel',

    // --- Admin: forms ---
    'admin_article_edit'   => 'Edit Article',
    'admin_category_new'   => 'New Category',
    'admin_category_edit'  => 'Edit Category',
    'admin_field_title'    => 'Title',
    'admin_field_content'  => 'Content',
    'admin_field_excerpt'  => 'Excerpt',
    'admin_field_cover'    => 'Cover Image',
    'admin_field_cover_hint' => 'JPG, PNG or WebP. Automatically converted to WebP.',
    'admin_field_meta_title'       => 'Meta Title',
    'admin_field_meta_description' => 'Meta Description',
    'admin_field_name'        => 'Name',
    'admin_field_description' => 'Description',
    'admin_field_slug'        => 'URL Slug',
    'admin_field_parent'      => 'Parent Category',
    'admin_field_no_parent'   => '— Top level —',
    'admin_field_tags'        => 'Tags',
    'admin_field_tags_hint'   => 'Separate with commas (e.g. php, history, science).',
    'admin_slug_auto_hint'    => 'Leave blank to auto-generate from the title.',
    'admin_seo_section'       => 'SEO Settings',
    'admin_confirm_delete'    => 'Are you sure you want to delete this?',
    'admin_filter_all'        => 'All',
    'admin_filter_apply'      => 'Filter',

    // --- Count labels (use a {count} placeholder) ---
    'label_article_count'  => '{count} articles',
    'search_result_count'  => '{count} results found',

    // --- Auth: password reset ---
    'reset_password_title' => 'Reset password',
    'reset_password_intro' => 'Enter your email and we will send you a reset link.',
    'new_password_title'   => 'Set a new password',
    'btn_send'             => 'Send',
];
