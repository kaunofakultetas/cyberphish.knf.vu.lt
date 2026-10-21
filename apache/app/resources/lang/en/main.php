<?php

// -----------------------------------------------------------
//  [*] en/main — the site's own strings, in English
//
//  Every text of the member side and its mails in English, by
//  key: menus and page titles, form labels and buttons, the
//  flash messages, the mails' subjects and bodies, the
//  badges' descriptions. The other four languages hold the
//  same keys in their own main.php. The admin panel never
//  switches the locale, so the few main keys its controllers
//  use come from this file too. A few keys appear twice; PHP
//  keeps the later entry.
//
//  Known bug (low): no_questions is defined twice
//  (main.php:134, 184) and PHP keeps the later, generic text,
//  so the self-evaluation page, which the first was written
//  for, never says the category has no questions. Fix: give
//  the category text its own key and use that key in
//  self-eval.blade.php.
//  Pinned as lang-no-questions.
//
//  Known bug (low): Admin\ManagerMainController::change_pass
//  asks for msg_current_pass_wrong, msg_current_new_same,
//  msg_new_pass_dont_match, msg_pass_too_short and
//  msg_pass_changed, which no main.php defines, so the
//  panel's password form answers with those raw keys. Fix:
//  use the member side's curr_pass_wrong, curr_new_same,
//  new_pass_not_match, pass_too_short and pass_changed, which
//  say the same.
//  Pinned as admin-pass-messages.
//
//  Used by:
//    - the views under resources/views — the layouts, the
//      sign-in forms, the course pages and the mails'
//      bodies — through __('main.…')
//    - the controllers, middleware and models that flash a
//      message or build a text — Auth\LoginController,
//      Auth\RegisterController, ManagerMainController,
//      LMController, SimulationsController,
//      KnowledgeTestController, BaseController, FinishedSe
//      and LoginLog
//    - the mailables, for their subjects — SendVerification,
//      SendRemindLink, SendNewPass and SimulationsResults
//    - the Feature suite — compares flash messages with the
//      same keys
// -----------------------------------------------------------


return [

    'my_info' => 'My Information',
    'my_badges' => 'My Badges',
    'mark_completed' => 'Mark as Completed!',
    'completed' => 'Completed!',
    'additional_files' => 'Additional files',
    'news' => 'News',
    'read' => 'Read',
    'no_news' => 'No News',
    'about_cyberphish' => 'About CyberPhish course',
    'more_about_us' => 'More',
    'project_news' => 'Project News',
    'more_news' => 'More news',
    'partners' => 'Partners',
    'login' => 'Login',
    'register' => 'Register',
    'logout' => 'Logout',
    'learning_material' => 'Learning Material',
    'course_progress' => 'Course Progress',
    'curr_password' => 'Current password',
    'new_password' => 'New password',
    'repeat_password' => 'Repeat new password',
    'change_password' => 'Change Password',
    'change_username' => 'Change username',
    'change_username2' => 'Change Username',
    'home'=>'Home',
    'learning_material'=>'Learning Material',
    'contact'=>'Contact',
    'sign_in'=>'Sign In',
    'sign_up'=>'Sign Up',
    'language'=>'Language',
    'welcome_back'=>'Welcome Back!',
    'enter_email'=>'Enter Email Address...',
    'passw'=>'Password',
    'repassw'=>'Repeat Password',
    'forgot_password'=>'Forgot Password?',
    'create_acc'=>'Create an Account!',
    'agree_terms'=>'I agree with Terms and Conditions',
    'already_acc_login'=>'Already have an account? Login!',
    'register'=>'Register',
    'forgot_password_descr'=>'We get it, stuff happens. Just enter your email address below and we\'ll send you a link to reset your password!',
    'verify_email'=>'Verify Your Email Address',
    'link_sent'=>'A fresh verification link has been sent to your email address.',
    'link_sent2'=>'Before proceeding, please check your email for a verification link.',
    'link_sent3'=>'If you did not receive the email',
    'request_another_link'=>'click here to request another',
    'reset_pass'=>'Reset Password',
    'login_url'=>'Login URL',
    'your_new_pass'=>'Your new password',
    'link_new_login_info'=>'Click link below and you will receive new login information',
    'verify_acc'=>'Verify your account',
    'new_pass_email_subject'=>'Your new password',
    'pass_reset_subject'=>'Password reset link',
    'account_verification_subject'=>'Account verification',
    'curr_pass_wrong'=>'Current password is wrong',
    'curr_new_same'=>'Current and new password is the same',
    'new_pass_not_match'=>'New passwords do not match',
    'pass_too_short'=>'Password is too short',
    'pass_changed'=>'Password changed',
    // Despite their keys, these two answer the user name
    // change (ManagerMainController::change_username)
    'pass_updated'=>'Username updated!',
    'pass_unavailable'=>'Username is unavailable!',
    'auth_failed'=>'Wrong email and/or password!',
    'forgot_pass_msg'=>'If email exists in our database, you will receive reset link.',
    'new_pass_emailed'=>'We just emailed you new password',
    'user_verified'=>'User verified! Now you can login.',
    'user_registered'=>'User registered! Check your email for verification link.',
    'pass_dont_match'=>'Passwords do not match!',
    'email_already_exists'=>'User with this email already exists!',
    'erasmus'=>'This project has been funded with support from the European Commission. This publication [communication] reflects the views only of the author, and the Commission cannot be held responsible for any use which may be made of the information contained therein.',
    'download_slides'=>'Download slides',
    'self_evaluation_test'=>'Self Evaluation Test',
    'self_eval_history'=>'Self Evaluation History',
    'country'=>'Country',
    'do_it_again'=>'Do it again',
    'correct_answer'=>'Correct answer',
    'wrong_answer'=>'Wrong answer',
    'selected_answer'=>'Selected answer',
    'started'=>'Started',
    'ended'=>'Ended',
    'points'=>'Points',
    'next'=>'Next',
    'no_questions'=>'No questions for this category!',
    'self_eval_ranks'=>'Self Evaluation Ranks',
    'nobody'=>'Nobody',
    'username'=>'Username',
    'ranks'=>'Ranks',
    'self_eval'=>'Self Evaluation',
    'simulations'=>'Simulations',
    'simulation'=>'Simulation',
    'actors'=>'Actors',
    'choose_type'=>'Type',
    'attack_type'=>'Attack Type',
    'goal'=>'Goal',
    'source'=>'Source',
    'simulation_categories'=>'Categories',
    'simulation_attributes'=>'Attributes',
    'start'=>'Start',
    'simulation_finished'=>'Simulation finished!',
    'do_simulation_again'=>'Do this simulation again!',
    'other_simulations'=>'Other Simulations',
    'points_collected'=>'Points collected',
    'simulations_history'=>'Simulations History',
    'at_least_one'=>'Select at least one option',
    'simulations_ranks'=>'Simulations Ranks',
    'position'=>'Position',
    'last_ended'=>'Last ended',
    // The two modes of a simulation's start page, keyed in
    // Lithuanian: po kiekvieno, feedback after each step, and
    // pabaigoje, feedback at the end
    'simulation_po_kiekvieno'=>'For learning purposes',
    'simulation_pabaigoje'=>'For knowledge testing purposes',
    'my_badges'=>'My Badges',
    // HTML: the success alert prints it unescaped
    // (parts/notif.blade.php), so the link works
    'new_badge'=>"You received new badge. Check <a href=\"/cp/badges\">My Badges</a>",
    'badge_topic'=>'Complete first topic',
    'badge_self_evaluation_test'=>'Complete first self evaluation test',
    'badge_category'=>'Complete first category',
    'badge_all_presentations'=>'Complete all presentations',
    'badge_all_simulations'=>'Complete all simulations',
    'badge_finished_course'=>'Complete the course',
    'badge_final_test'=>'Complete final test',
    'badge_login_10'=>'Login for 10 consecutive days',
    'knowledge_test'=>'Knowledge Test',
    'results'=>'Result',
    'test_finished'=>'Test is finished!',
    // Nothing shows only_3 at the moment
    'only_3'=>'You are only allowed a maximum of three attempts for this test.',
    'not_anymore'=>'You cannot do it anymore',
    // no_questions again: PHP keeps this entry, so the
    // category text further up never shows
    'no_questions'=>'No questions',
    'total_tries'=>'Total number of attempts so far',
    'you_failed'=>'You failed!',
    'you_passed'=>'You passed!',
    'download_certificate'=>'Download certificate',
    'change_fullname'=>'Change full name',
    'fullname'=>'Full name',
    'updated'=>'Updated!',
    'pq_questionare'=>'Post-Course Questionnaire'

];
