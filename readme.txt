=== QuickTestWP ===
Contributors: abedputra
Tags: quiz, test, assessment, exam, questions
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress quiz and test plugin with image upload support, progress tracking, email results, and test sequences.

== Description ==

QuickTestWP is a powerful WordPress plugin for creating interactive quizzes, assessments, and exams on your WordPress site. Perfect for educators, trainers, and anyone who needs to create engaging tests with multiple question types.

= Features =

* **Multiple Question Types**: Support for multiple choice, true/false, and short answer questions
* **Image Support**: Upload images for questions to make them more engaging
* **Time Limits**: Set time limits for tests with countdown timer
* **Progress Tracking**: Visual progress bar showing completion status
* **Email Results**: Automatically send test results to users via email
* **Test Sequences**: Create sequences of multiple tests that users take one after another
* **Role-Based Access**: Restrict tests to specific user roles
* **Results Management**: View, manage, and resend results from the admin panel
* **CSV Import**: Bulk import questions from CSV files
* **Responsive Design**: Mobile-friendly interface that works on all devices
* **Security**: Built-in security features including nonce verification and input sanitization

= Usage =

1. Create a new test from **QuickTestWP** → **Add New**
2. Add questions with images, multiple choice options, or short answers
3. Set time limits and access restrictions
4. Insert the test into any post or page using the shortcode: `[quicktestwp id="1"]`
5. Users can take the test and receive results via email

= Shortcodes =

* `[quicktestwp id="1"]` - Display a single test
* `[quicktestwp_sequence id="1"]` - Display a sequence of tests

= Question Types =

* **Multiple Choice**: Four answer options (A, B, C, D)
* **True/False**: Two options (True or False)
* **Short Answer**: Users type their answer

== Installation ==

1. Upload the `qtest` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **QuickTestWP** in the admin menu to start creating tests

== Frequently Asked Questions ==

= How do I create a test? =

Go to **QuickTestWP** → **Add New**, enter your test details, and add questions.

= Can I import questions from a CSV file? =

Yes! Go to **QuickTestWP** → **Import Questions** and upload a CSV file. Download the sample CSV template to see the required format.

= How do I display a test on my site? =

Use the shortcode `[quicktestwp id="1"]` (replace 1 with your test ID) in any post or page. You can also use the "Insert QuickTestWP" meta box when editing posts.

= Can I create a sequence of multiple tests? =

Yes! Create a test sequence from **QuickTestWP** → **Add New Sequence** and add multiple tests. Users will take them one after another.

= How are results stored? =

Results are stored in the database and can be viewed from **QuickTestWP** → **Results**. You can also resend result emails from there.

= Can I restrict tests to specific user roles? =

Yes! When creating or editing a test, you can select which user roles are allowed to access it.

== Screenshots ==

1. Test creation interface
2. Question editor with image upload
3. Frontend quiz display
4. Results management
5. CSV import feature

== Changelog ==

= 1.0.0 =
* Initial release
* Multiple question types support
* Image upload for questions
* Time limits and timer
* Progress tracking
* Email results
* Test sequences
* CSV import
* Role-based access control
* Results management

== Upgrade Notice ==

= 1.0.0 =
Initial release of QuickTestWP plugin.
