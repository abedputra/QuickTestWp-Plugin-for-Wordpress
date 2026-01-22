# QuickTestWP - WordPress Quiz & Test Plugin

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://php.net/)

A comprehensive WordPress quiz and test plugin with image upload support, progress tracking, email results, and test sequences. Perfect for creating interactive quizzes, assessments, and exams on your WordPress site.

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Usage](#-usage)
- [Shortcodes](#-shortcodes)
- [Question Types](#-question-types)
- [Test Sequences](#-test-sequences)
- [Admin Features](#-admin-features)
- [Contributing](#-contributing)
- [Support](#-support)
- [License](#-license)

## ✨ Features

### Core Features
- **Multiple Question Types**: Support for multiple choice, true/false, and short answer questions
- **Image Support**: Upload images for questions to make them more engaging
- **Time Limits**: Set time limits for tests with countdown timer
- **Progress Tracking**: Visual progress bar showing completion status
- **Email Results**: Automatically send test results to users via email
- **Test Sequences**: Create sequences of multiple tests that users take one after another
- **Role-Based Access**: Restrict tests to specific user roles
- **Results Management**: View, manage, and resend results from the admin panel
- **CSV Import**: Bulk import questions from CSV files
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **Security**: Built-in security features including nonce verification and input sanitization

### User Experience
- Clean, modern interface
- One question per page with smooth navigation
- Real-time timer display
- Instant score calculation
- Result lookup by email and test ID
- Auto-continue option for test sequences

## 📦 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.0 or higher
- **MySQL**: 5.6 or higher

## 🚀 Installation

### Method 1: Manual Installation

1. **Download the plugin**
   - Download the plugin files or clone this repository
   ```bash
   git clone https://github.com/abedputra/quicktestwp.git
   ```

2. **Upload to WordPress**
   - Navigate to your WordPress installation directory
   - Go to `wp-content/plugins/`
   - Upload the `quicktestwp` folder here

3. **Activate the plugin**
   - Log in to your WordPress admin panel
   - Go to **Plugins** → **Installed Plugins**
   - Find **QuickTestWP** and click **Activate**

### Method 2: WordPress Admin Upload

1. **Zip the plugin folder**
   - Compress the `quicktestwp` folder into a `.zip` file

2. **Upload via WordPress**
   - Go to **Plugins** → **Add New** → **Upload Plugin**
   - Choose the zip file and click **Install Now**
   - Click **Activate Plugin**

## 🎯 Quick Start

1. **Create Your First Test**
   - Go to **QuickTestWP** → **Add New** in your WordPress admin
   - Enter a title and description for your test
   - Optionally set a time limit (in minutes)
   - Click **Save Test**

2. **Add Questions**
   - After saving, you'll see the questions section
   - Click **Add Question**
   - Enter your question text
   - Optionally upload an image
   - Choose question type (Multiple Choice, True/False, or Short Answer)
   - Enter answer options and mark the correct answer
   - Click **Save Question**

3. **Insert Test into Post/Page**
   - Edit any post or page
   - Look for the **Insert QuickTestWP** meta box on the right sidebar
   - Select your test and click **Insert Test**
   - Or manually add: `[quicktestwp id="1"]` (replace 1 with your test ID)

4. **Publish and Test**
   - Publish your post/page
   - Visit the frontend and take the test
   - Results will be automatically saved and emailed

## 📖 Usage

### Creating a Test

1. Navigate to **QuickTestWP** → **Add New**
2. Fill in the test details:
   - **Title**: Name of your test
   - **Description**: Optional description
   - **Time Limit**: Optional time limit in minutes (0 = no limit)
   - **Allowed Roles**: Select which user roles can access this test (leave empty for public access)
3. Click **Save Test**
4. Add questions using the question editor below

### Adding Questions

1. Click **Add Question** button
2. Fill in question details:
   - **Question Text**: Your question
   - **Question Image**: Optional image upload
   - **Question Type**: Choose from:
     - Multiple Choice
     - True/False
     - Short Answer
   - **Answer Options**: Enter options A, B, C, D (for multiple choice)
   - **Correct Answer**: Select or enter the correct answer
   - **Question Order**: Set display order (optional)
3. Click **Save Question**

### Importing Questions from CSV

1. Go to **QuickTestWP** → **Import Questions**
2. Select the test you want to import questions into
3. Download the sample CSV file to see the format
4. Prepare your CSV file with columns:
   - `question_text`: The question
   - `question_image`: Image URL (optional)
   - `question_type`: `multiple_choice`, `true_false`, or `short_answer`
   - `option_a`, `option_b`, `option_c`, `option_d`: Answer options
   - `correct_answer`: The correct answer
   - `question_order`: Display order
5. Upload your CSV file
6. Click **Import Questions**

## 🔧 Shortcodes

### Single Test Shortcode

Display a single test on any post or page:

```
[quicktestwp id="1"]
```

**Parameters:**
- `id` (required): The test ID

**Example:**
```
[quicktestwp id="5"]
```

### Test Sequence Shortcode

Display a sequence of tests:

```
[quicktestwp_sequence id="1"]
```

**Parameters:**
- `id` (required): The sequence ID

**Example:**
```
[quicktestwp_sequence id="3"]
```

### Using Shortcodes in Posts/Pages

**Method 1: Using the Meta Box**
1. Edit your post or page
2. Look for the **Insert QuickTestWP** meta box in the sidebar
3. Select a test from the dropdown
4. Click **Insert Test** button

**Method 2: Manual Entry**
- Simply type the shortcode in your post editor:
  ```
  [quicktestwp id="1"]
  ```

**Method 3: Classic Editor Button**
- In the Classic Editor, click the **QuickTestWP** button in the toolbar
- Enter the test ID when prompted

## 📝 Question Types

### Multiple Choice
- Four answer options (A, B, C, D)
- Users select one correct answer
- Case-insensitive comparison

### True/False
- Two options: True or False
- Users select one option
- Exact match comparison

### Short Answer
- Users type their answer in a text field
- Case-insensitive comparison
- Whitespace is trimmed

## 🔄 Test Sequences

Test sequences allow you to chain multiple tests together. Users take tests one after another, and you can configure auto-continue between tests.

### Creating a Sequence

1. Go to **QuickTestWP** → **Add New Sequence**
2. Enter sequence title and description
3. Add tests to the sequence:
   - Select a test from the dropdown
   - Set the order
   - Optionally enable **Auto Continue** (automatically moves to next test)
   - Click **Add Test**
4. Reorder tests by dragging
5. Click **Save Sequence**

### Using Sequences

Insert a sequence using the shortcode:
```
[quicktestwp_sequence id="1"]
```

Users will start with the first test in the sequence and progress through them automatically (if auto-continue is enabled).

## 🎛️ Admin Features

### Test Management
- **All Tests**: View all created tests
- **Add New**: Create new tests
- **Edit**: Click on any test to edit
- **Delete**: Remove tests and their questions
- **Copy Shortcode**: Quick copy button for shortcodes

### Results Management
- **View Results**: See all test results
- **Filter by Test**: Filter results by specific test
- **Resend Email**: Resend result emails to users
- **Delete Results**: Remove unwanted results
- **Export**: Results are stored in the database for export

### Import/Export
- **CSV Import**: Bulk import questions from CSV
- **Sample CSV**: Download sample file for reference

## 🎨 Customization

The plugin includes CSS classes that you can customize:

- `.quicktestwp-container`: Main container
- `.quicktestwp-question-page`: Individual question page
- `.quicktestwp-answer-option`: Answer option button
- `.quicktestwp-progress-bar`: Progress bar container
- `.quicktestwp-timer`: Timer display

Add custom CSS in your theme's `style.css` or use a custom CSS plugin.

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

### How to Contribute

1. **Fork the repository**
   ```bash
   git clone https://github.com/abedputra/quicktestwp.git
   cd quicktestwp
   ```

2. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Make your changes**
   - Follow WordPress coding standards
   - Add comments for complex logic
   - Test your changes thoroughly

4. **Commit your changes**
   ```bash
   git commit -m "Add: Description of your feature"
   ```

5. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request**
   - Go to the repository on GitHub
   - Click "New Pull Request"
   - Describe your changes

### Contribution Guidelines

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Write clear commit messages
- Test your code before submitting
- Update documentation if needed
- Be respectful and constructive in discussions

### Areas for Contribution

- 🐛 Bug fixes
- ✨ New features
- 📚 Documentation improvements
- 🎨 UI/UX enhancements
- 🌐 Translations
- ⚡ Performance optimizations
- 🔒 Security improvements

## 📧 Support

- **Author**: Abed Putra
- **Website**: [https://abedputra.my.id](https://abedputra.my.id)
- **Issues**: [GitHub Issues](https://github.com/abedputra/quicktestwp/issues)

If you encounter any issues or have questions:
1. Check the [Issues](https://github.com/abedputra/quicktestwp/issues) page
2. Create a new issue with:
   - Description of the problem
   - Steps to reproduce
   - WordPress and PHP versions
   - Any error messages

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Abed Putra

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 🙏 Credits

- Built with ❤️ by [Abed Putra](https://abedputra.my.id)
- Uses WordPress core functionality
- Icons from WordPress Dashicons

## 📚 Changelog

### Version 1.0.0
- Initial release
- Multiple question types support
- Image upload for questions
- Time limits and timer
- Progress tracking
- Email results
- Test sequences
- CSV import
- Role-based access control
- Results management

---

**Made with ❤️ for the WordPress community**
