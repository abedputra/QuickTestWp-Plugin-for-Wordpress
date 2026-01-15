<?php
/**
 * AJAX handlers for QTest
 */

if (!defined('ABSPATH')) {
    exit;
}

class QTest_Ajax {
    
    public function __construct() {
        // Admin AJAX
        add_action('wp_ajax_qtest_save_test', array($this, 'save_test'));
        add_action('wp_ajax_qtest_save_question', array($this, 'save_question'));
        add_action('wp_ajax_qtest_delete_test', array($this, 'delete_test'));
        add_action('wp_ajax_qtest_delete_question', array($this, 'delete_question'));
        
        // Frontend AJAX
        add_action('wp_ajax_qtest_submit_result', array($this, 'submit_result'));
        add_action('wp_ajax_nopriv_qtest_submit_result', array($this, 'submit_result'));
        add_action('wp_ajax_qtest_get_result', array($this, 'get_result'));
        add_action('wp_ajax_nopriv_qtest_get_result', array($this, 'get_result'));
        add_action('wp_ajax_qtest_get_average_times', array($this, 'get_average_times'));
        add_action('wp_ajax_nopriv_qtest_get_average_times', array($this, 'get_average_times'));
        
        // Admin AJAX - Resend email
        add_action('wp_ajax_qtest_resend_email', array($this, 'resend_email'));
        add_action('wp_ajax_qtest_delete_result', array($this, 'delete_result'));
        
        // Admin AJAX - Import questions
        add_action('wp_ajax_qtest_import_questions', array($this, 'import_questions'));
        
        // Admin AJAX - Sequences
        add_action('wp_ajax_qtest_save_sequence', array($this, 'save_sequence'));
        add_action('wp_ajax_qtest_delete_sequence', array($this, 'delete_sequence'));
        add_action('wp_ajax_qtest_add_sequence_test', array($this, 'add_sequence_test'));
        add_action('wp_ajax_qtest_remove_sequence_test', array($this, 'remove_sequence_test'));
    }
    
    /**
     * Save test
     */
    public function save_test() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_tests';
        
        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $time_limit = isset($_POST['time_limit']) ? intval($_POST['time_limit']) : 0;
        $allowed_roles = isset($_POST['allowed_roles']) ? sanitize_text_field($_POST['allowed_roles']) : '';
        
        if (empty($title)) {
            wp_send_json_error(array('message' => 'Title is required'));
        }
        
        $update_data = array(
            'title' => $title,
            'description' => $description,
            'time_limit' => $time_limit
        );
        $update_format = array('%s', '%s', '%d');
        
        if (!empty($allowed_roles)) {
            $update_data['allowed_roles'] = $allowed_roles;
            $update_format[] = '%s';
        }
        
        if ($test_id) {
            $result = $wpdb->update(
                $table,
                $update_data,
                array('id' => $test_id),
                $update_format,
                array('%d')
            );
            // Update returns number of rows affected (0 if no change, false on error)
            if ($result === false) {
                wp_send_json_error(array('message' => 'Failed to save test: ' . $wpdb->last_error));
            } else {
                wp_send_json_success(array('test_id' => $test_id, 'message' => 'Test saved successfully'));
            }
        } else {
            $result = $wpdb->insert(
                $table,
                $update_data,
                $update_format
            );
            if ($result === false) {
                wp_send_json_error(array('message' => 'Failed to save test: ' . $wpdb->last_error));
            } else {
                $test_id = $wpdb->insert_id;
                wp_send_json_success(array('test_id' => $test_id, 'message' => 'Test saved successfully'));
            }
        }
    }
    
    /**
     * Save question
     */
    public function save_question() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_questions';
        
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $test_id = intval($_POST['test_id']);
        $question_type = isset($_POST['question_type']) ? sanitize_text_field($_POST['question_type']) : 'multiple_choice';
        $question_text = sanitize_textarea_field($_POST['question_text']);
        $question_image = esc_url_raw($_POST['question_image']);
        $option_a = isset($_POST['option_a']) ? sanitize_text_field($_POST['option_a']) : '';
        $option_b = isset($_POST['option_b']) ? sanitize_text_field($_POST['option_b']) : '';
        $option_c = isset($_POST['option_c']) ? sanitize_text_field($_POST['option_c']) : '';
        $option_d = isset($_POST['option_d']) ? sanitize_text_field($_POST['option_d']) : '';
        $correct_answer = sanitize_text_field($_POST['correct_answer']);
        $question_order = intval($_POST['question_order']);
        
        if (empty($question_text) || empty($correct_answer)) {
            wp_send_json_error(array('message' => 'Question text and correct answer are required'));
        }
        
        // Validate based on question type
        if ($question_type === 'multiple_choice') {
            if (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
                wp_send_json_error(array('message' => 'All options (A, B, C, D) are required for multiple choice questions'));
            }
            if (!in_array(strtoupper($correct_answer), array('A', 'B', 'C', 'D'))) {
                wp_send_json_error(array('message' => 'Correct answer must be A, B, C, or D for multiple choice questions'));
            }
            $correct_answer = strtoupper($correct_answer);
        } elseif ($question_type === 'true_false') {
            if (!in_array($correct_answer, array('True', 'False'))) {
                wp_send_json_error(array('message' => 'Correct answer must be True or False'));
            }
        } elseif ($question_type === 'short_answer') {
            if (empty($correct_answer)) {
                wp_send_json_error(array('message' => 'Correct answer is required for short answer questions'));
            }
        }
        
        $data = array(
            'test_id' => $test_id,
            'question_type' => $question_type,
            'question_text' => $question_text,
            'question_image' => $question_image,
            'option_a' => $option_a,
            'option_b' => $option_b,
            'option_c' => $option_c,
            'option_d' => $option_d,
            'correct_answer' => $correct_answer,
            'question_order' => $question_order
        );
        
        if ($question_id) {
            // Format array: test_id(%d), question_type(%s), question_text(%s), question_image(%s), 
            // option_a(%s), option_b(%s), option_c(%s), option_d(%s), correct_answer(%s), question_order(%d)
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $question_id),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
                array('%d')
            );
        } else {
            // Format array: test_id(%d), question_type(%s), question_text(%s), question_image(%s), 
            // option_a(%s), option_b(%s), option_c(%s), option_d(%s), correct_answer(%s), question_order(%d)
            $result = $wpdb->insert($table, $data, array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'));
            $question_id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            wp_send_json_success(array('question_id' => $question_id, 'message' => 'Question saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save question'));
        }
    }
    
    /**
     * Delete test
     */
    public function delete_test() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $test_id = intval($_POST['test_id']);
        
        // Delete questions first
        $questions_table = $wpdb->prefix . 'qtest_questions';
        $wpdb->delete($questions_table, array('test_id' => $test_id), array('%d'));
        
        // Delete test
        $tests_table = $wpdb->prefix . 'qtest_tests';
        $result = $wpdb->delete($tests_table, array('id' => $test_id), array('%d'));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Test deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete test'));
        }
    }
    
    /**
     * Delete question
     */
    public function delete_question() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $question_id = intval($_POST['question_id']);
        
        // Validate question ID
        if ($question_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid question ID'));
        }
        
        $table = $wpdb->prefix . 'qtest_questions';
        
        // Check if question exists before attempting to delete
        $question = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE id = %d",
            $question_id
        ));
        
        if (!$question) {
            wp_send_json_error(array('message' => 'Question not found'));
        }
        
        // Clear any previous database errors
        $wpdb->last_error = '';
        
        // Use direct SQL DELETE query for more reliable deletion
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE id = %d",
            $question_id
        ));
        
        // Check for database errors first
        if (!empty($wpdb->last_error)) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }
        
        // Check if query execution failed
        if ($deleted === false) {
            wp_send_json_error(array('message' => 'Failed to execute delete query'));
        }
        
        // Verify deletion by checking if question still exists
        $still_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE id = %d",
            $question_id
        ));
        
        if ($still_exists > 0) {
            // Question still exists, deletion failed
            wp_send_json_error(array('message' => 'Question could not be deleted. Please try again or contact support.'));
        } else {
            // Question was successfully deleted
            wp_send_json_success(array('message' => 'Question deleted successfully'));
        }
    }
    
    /**
     * Submit test result
     */
    public function submit_result() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        $test_id = intval($_POST['test_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        $time_started = isset($_POST['time_started']) ? sanitize_text_field($_POST['time_started']) : null;
        $time_completed = isset($_POST['time_completed']) ? sanitize_text_field($_POST['time_completed']) : current_time('mysql');
        $time_taken = isset($_POST['time_taken']) ? intval($_POST['time_taken']) : 0;
        $sequence_mode = isset($_POST['sequence_mode']) && $_POST['sequence_mode'] === 'true';
        
        // For sequence mode (intermediate tests), allow placeholder data
        if ($sequence_mode) {
            // Use placeholder data for intermediate sequence tests
            if (empty($first_name) || $first_name === 'SEQUENCE_IN_PROGRESS') {
                $first_name = 'Sequence';
            }
            if (empty($last_name) || $last_name === 'SEQUENCE_IN_PROGRESS') {
                $last_name = 'In Progress';
            }
            if (empty($email) || $email === 'sequence@placeholder.local' || !is_email($email)) {
                $email = 'sequence@placeholder.local';
            }
        } else {
            // Normal mode: require valid user info
            if (empty($first_name) || empty($last_name) || empty($email) || !is_email($email)) {
                wp_send_json_error(array('message' => 'Please provide valid first name, last name, and email'));
            }
        }
        
        // Calculate score
        $questions = QTest_Database::get_questions($test_id);
        $score = 0;
        $total = count($questions);
        
        foreach ($questions as $question) {
            $user_answer = isset($answers[$question->id]) ? $answers[$question->id] : '';
            $question_type = isset($question->question_type) && !empty($question->question_type) ? $question->question_type : 'multiple_choice';
            $correct_answer = $question->correct_answer;
            
            $is_correct = false;
            
            if ($question_type === 'multiple_choice') {
                // Multiple choice: compare uppercase
                $is_correct = strtoupper($user_answer) === strtoupper($correct_answer);
            } elseif ($question_type === 'true_false') {
                // True/False: exact match (case-sensitive)
                $is_correct = $user_answer === $correct_answer;
            } elseif ($question_type === 'short_answer') {
                // Short answer: case-insensitive comparison, trim whitespace
                $is_correct = strtolower(trim($user_answer)) === strtolower(trim($correct_answer));
            }
            
            if ($is_correct) {
                $score++;
            }
        }
        
        // Save result
        $result_data = array(
            'test_id' => $test_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'score' => $score,
            'total_questions' => $total,
            'answers' => $answers
        );
        
        // Add time data if provided
        if ($time_started) {
            $result_data['time_started'] = $time_started;
        }
        if ($time_completed) {
            $result_data['time_completed'] = $time_completed;
        }
        if ($time_taken > 0) {
            $result_data['time_taken'] = $time_taken;
        }
        
        // Add question times if provided
        $question_times = isset($_POST['question_times']) ? $_POST['question_times'] : array();
        if (!empty($question_times) && is_array($question_times)) {
            $result_data['question_times'] = $question_times;
        }
        
        $saved = QTest_Database::save_result($result_data);
        
        if ($saved) {
            // Only send email for non-sequence mode or final sequence test (when real email is provided)
            $email_sent = false;
            $email_error = null;
            if (!$sequence_mode || ($email !== 'sequence@placeholder.local' && is_email($email))) {
                $email_sent = $this->send_result_email($result_data, $questions);
                if (!$email_sent) {
                    $email_error = 'Email could not be sent. Please check your email configuration.';
                }
            }
            
            wp_send_json_success(array(
                'message' => 'Result saved successfully' . ($email_error ? '. ' . $email_error : ''),
                'score' => $score,
                'total' => $total,
                'email_sent' => $email_sent
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save result'));
        }
    }
    
    /**
     * Get result
     */
    public function get_result() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $test_id = intval($_POST['test_id']);
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Please provide a valid email'));
        }
        
        $result = QTest_Database::get_result($email, $test_id);
        
        if ($result) {
            $answers = json_decode($result->answers, true);
            $questions = QTest_Database::get_questions($test_id);
            
            wp_send_json_success(array(
                'result' => $result,
                'questions' => $questions,
                'answers' => $answers
            ));
        } else {
            wp_send_json_error(array('message' => 'No result found for this email'));
        }
    }
    
    /**
     * Get average times per question
     */
    public function get_average_times() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        $test_id = intval($_POST['test_id']);
        $averages = QTest_Database::get_average_times_per_question($test_id);
        
        wp_send_json_success(array('averages' => $averages));
    }
    
    /**
     * Resend result email (Admin)
     */
    public function resend_email() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $result_id = intval($_POST['result_id']);
        $result = QTest_Database::get_result_by_id($result_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Result not found'));
        }
        
        $questions = QTest_Database::get_questions($result->test_id);
        $answers = json_decode($result->answers, true);
        
        $result_data = array(
            'first_name' => $result->first_name,
            'last_name' => $result->last_name,
            'email' => $result->email,
            'score' => $result->score,
            'total_questions' => $result->total_questions,
            'answers' => $answers
        );
        
        $sent = $this->send_result_email($result_data, $questions);
        
        if ($sent) {
            wp_send_json_success(array('message' => 'Email sent successfully to ' . $result->email));
        } else {
            wp_send_json_error(array('message' => 'Failed to send email'));
        }
    }
    
    /**
     * Delete result (Admin)
     */
    public function delete_result() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $result_id = intval($_POST['result_id']);
        $table = $wpdb->prefix . 'qtest_results';
        
        $result = $wpdb->delete($table, array('id' => $result_id), array('%d'));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Result deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete result'));
        }
    }
    
    /**
     * Send result email
     */
    private function send_result_email($result_data, $questions) {
        $to = $result_data['email'];
        $subject = 'Your QTest Results';
        
        $percentage = round(($result_data['score'] / $result_data['total_questions']) * 100, 2);
        
        $message = "Hello {$result_data['first_name']} {$result_data['last_name']},\n\n";
        $message .= "Thank you for completing the test!\n\n";
        $message .= "Your Results:\n";
        $message .= "Score: {$result_data['score']} out of {$result_data['total_questions']}\n";
        $message .= "Percentage: {$percentage}%\n\n";
        $message .= "Detailed Answers:\n\n";
        
        foreach ($questions as $question) {
            $user_answer = isset($result_data['answers'][$question->id]) ? strtoupper($result_data['answers'][$question->id]) : 'Not answered';
            $is_correct = $user_answer === $question->correct_answer;
            $status = $is_correct ? 'Correct' : 'Incorrect';
            
            $message .= "Question: {$question->question_text}\n";
            $message .= "Your Answer: {$user_answer} ({$status})\n";
            $message .= "Correct Answer: {$question->correct_answer}\n\n";
        }
        
        // Set proper From address using WordPress admin email
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        // Use filters to set proper From address
        $from_filter = function($from_email) use ($admin_email) {
            return !empty($admin_email) && is_email($admin_email) ? $admin_email : $from_email;
        };
        
        $from_name_filter = function($from_name) use ($site_name) {
            return !empty($site_name) ? $site_name : $from_name;
        };
        
        add_filter('wp_mail_from', $from_filter);
        add_filter('wp_mail_from_name', $from_name_filter);
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Capture any errors from wp_mail using a closure variable
        $wp_mail_error = null;
        $error_handler = function($wp_error) use (&$wp_mail_error) {
            $wp_mail_error = $wp_error;
        };
        add_action('wp_mail_failed', $error_handler);
        
        $result = wp_mail($to, $subject, $message, $headers);
        
        // Remove filters and action after sending
        remove_filter('wp_mail_from', $from_filter);
        remove_filter('wp_mail_from_name', $from_name_filter);
        remove_action('wp_mail_failed', $error_handler);
        
        return $result;
    }
    
    /**
     * Import questions from CSV
     */
    public function import_questions() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
        
        if (!$test_id) {
            wp_send_json_error(array('message' => 'Please select a test'));
        }
        
        // Check if test exists
        $test = QTest_Database::get_test($test_id);
        if (!$test) {
            wp_send_json_error(array('message' => 'Test not found'));
        }
        
        // Handle file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'Please upload a valid CSV file'));
        }
        
        $file = $_FILES['csv_file'];
        
        // Validate file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            wp_send_json_error(array('message' => 'Please upload a CSV file'));
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File size must be less than 2MB'));
        }
        
        // Read CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            wp_send_json_error(array('message' => 'Failed to read CSV file'));
        }
        
        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            wp_send_json_error(array('message' => 'CSV file is empty or invalid'));
        }
        
        // Normalize headers (trim and lowercase)
        $headers = array_map('trim', array_map('strtolower', $headers));
        
        // Required columns
        $required_columns = array('question_type', 'question_text', 'correct_answer');
        $missing_columns = array();
        foreach ($required_columns as $col) {
            if (!in_array($col, $headers)) {
                $missing_columns[] = $col;
            }
        }
        
        if (!empty($missing_columns)) {
            fclose($handle);
            wp_send_json_error(array('message' => 'Missing required columns: ' . implode(', ', $missing_columns)));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_questions';
        $imported = 0;
        $errors = array();
        $row_num = 1;
        
        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            $row_num++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $header_count = count($headers);
            $row_count = count($row);
            
            // Map row data to associative array
            $data = array();
            foreach ($headers as $index => $header) {
                $data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            // If row has one extra column, try a safe auto-repair for non-multiple-choice rows:
            // Some editors accidentally add an extra comma, shifting correct_answer into question_order.
            if (
                $row_count === ($header_count + 1)
                && isset($data['question_type'])
                && in_array($data['question_type'], array('true_false', 'short_answer'), true)
                && isset($data['correct_answer'], $data['question_order'])
                && $data['correct_answer'] === ''
                && $data['question_order'] !== ''
                && !is_numeric($data['question_order'])
                && isset($row[$header_count])
                && is_numeric(trim($row[$header_count]))
            ) {
                $data['correct_answer'] = $data['question_order'];
                $data['question_order'] = trim($row[$header_count]);
            } elseif ($row_count !== $header_count) {
                $errors[] = "Row $row_num: Column count mismatch (expected $header_count, got $row_count). Check for extra/missing commas.";
                continue;
            }
            
            // Validate question type
            $question_type = isset($data['question_type']) ? $data['question_type'] : 'multiple_choice';
            if (!in_array($question_type, array('multiple_choice', 'true_false', 'short_answer'))) {
                $errors[] = "Row $row_num: Invalid question_type. Must be multiple_choice, true_false, or short_answer";
                continue;
            }
            
            // Validate required fields
            if (empty($data['question_text'])) {
                $errors[] = "Row $row_num: question_text is required";
                continue;
            }
            
            if (empty($data['correct_answer'])) {
                $errors[] = "Row $row_num: correct_answer is required";
                continue;
            }
            
            // Validate based on question type
            if ($question_type === 'multiple_choice') {
                if (empty($data['option_a']) || empty($data['option_b']) || empty($data['option_c']) || empty($data['option_d'])) {
                    $errors[] = "Row $row_num: All options (A, B, C, D) are required for multiple_choice questions";
                    continue;
                }
                if (!in_array(strtoupper($data['correct_answer']), array('A', 'B', 'C', 'D'))) {
                    $errors[] = "Row $row_num: correct_answer must be A, B, C, or D for multiple_choice questions";
                    continue;
                }
                $data['correct_answer'] = strtoupper($data['correct_answer']);
            } elseif ($question_type === 'true_false') {
                if (!in_array($data['correct_answer'], array('True', 'False'))) {
                    $errors[] = "Row $row_num: correct_answer must be True or False for true_false questions";
                    continue;
                }
            }
            
            // Prepare insert data
            $insert_data = array(
                'test_id' => $test_id,
                'question_type' => $question_type,
                'question_text' => sanitize_textarea_field($data['question_text']),
                'question_image' => isset($data['question_image']) ? esc_url_raw($data['question_image']) : '',
                'option_a' => isset($data['option_a']) ? sanitize_text_field($data['option_a']) : '',
                'option_b' => isset($data['option_b']) ? sanitize_text_field($data['option_b']) : '',
                'option_c' => isset($data['option_c']) ? sanitize_text_field($data['option_c']) : '',
                'option_d' => isset($data['option_d']) ? sanitize_text_field($data['option_d']) : '',
                'correct_answer' => sanitize_text_field($data['correct_answer']),
                'question_order' => isset($data['question_order']) ? intval($data['question_order']) : 0
            );
            
            // Insert question
            $result = $wpdb->insert($table, $insert_data, array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'));
            
            if ($result !== false) {
                $imported++;
            } else {
                $errors[] = "Row $row_num: Failed to import question - " . $wpdb->last_error;
            }
        }
        
        fclose($handle);
        
        $message = "Imported $imported question(s) successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . count($errors) . " row(s) failed. " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " (and " . (count($errors) - 5) . " more)";
            }
        }
        
            wp_send_json_success(array(
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors
            ));
        }
    
    /**
     * Save sequence
     */
    public function save_sequence() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequences';
        
        $sequence_id = isset($_POST['sequence_id']) ? intval($_POST['sequence_id']) : 0;
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        
        if (empty($title)) {
            wp_send_json_error(array('message' => 'Title is required'));
        }
        
        if ($sequence_id) {
            $result = $wpdb->update(
                $table,
                array('title' => $title, 'description' => $description),
                array('id' => $sequence_id),
                array('%s', '%s'),
                array('%d')
            );
            if ($result === false) {
                wp_send_json_error(array('message' => 'Failed to save sequence: ' . $wpdb->last_error));
            } else {
                wp_send_json_success(array('sequence_id' => $sequence_id, 'message' => 'Sequence saved successfully'));
            }
        } else {
            $result = $wpdb->insert(
                $table,
                array('title' => $title, 'description' => $description),
                array('%s', '%s')
            );
            if ($result === false) {
                wp_send_json_error(array('message' => 'Failed to save sequence: ' . $wpdb->last_error));
            } else {
                $sequence_id = $wpdb->insert_id;
                wp_send_json_success(array('sequence_id' => $sequence_id, 'message' => 'Sequence saved successfully'));
            }
        }
    }
    
    /**
     * Delete sequence
     */
    public function delete_sequence() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $sequence_id = intval($_POST['sequence_id']);
        
        // Delete sequence tests first
        $sequence_tests_table = $wpdb->prefix . 'qtest_sequence_tests';
        $wpdb->delete($sequence_tests_table, array('sequence_id' => $sequence_id), array('%d'));
        
        // Delete sequence
        $sequences_table = $wpdb->prefix . 'qtest_sequences';
        $result = $wpdb->delete($sequences_table, array('id' => $sequence_id), array('%d'));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Sequence deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete sequence'));
        }
    }
    
    /**
     * Add test to sequence
     */
    public function add_sequence_test() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequence_tests';
        
        $sequence_id = intval($_POST['sequence_id']);
        $test_id = intval($_POST['test_id']);
        $test_order = intval($_POST['test_order']);
        $auto_continue = isset($_POST['auto_continue']) && $_POST['auto_continue'] == '1' ? 1 : 0;
        
        if (!$sequence_id || !$test_id) {
            wp_send_json_error(array('message' => 'Sequence ID and Test ID are required'));
        }
        
        // Check if test already in sequence
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE sequence_id = %d AND test_id = %d",
            $sequence_id,
            $test_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'This test is already in the sequence'));
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'sequence_id' => $sequence_id,
                'test_id' => $test_id,
                'test_order' => $test_order,
                'auto_continue' => $auto_continue
            ),
            array('%d', '%d', '%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Test added to sequence successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add test to sequence'));
        }
    }
    
    /**
     * Remove test from sequence
     */
    public function remove_sequence_test() {
        check_ajax_referer('qtest_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $sequence_test_id = intval($_POST['sequence_test_id']);
        
        // Validate sequence test ID
        if ($sequence_test_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid sequence test ID'));
        }
        
        $table = $wpdb->prefix . 'qtest_sequence_tests';
        
        // Check if sequence test exists before attempting to delete
        $sequence_test = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE id = %d",
            $sequence_test_id
        ));
        
        if (!$sequence_test) {
            wp_send_json_error(array('message' => 'Test not found in sequence'));
        }
        
        // Clear any previous database errors
        $wpdb->last_error = '';
        
        // Use direct SQL DELETE query for more reliable deletion
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE id = %d",
            $sequence_test_id
        ));
        
        // Check for database errors first
        if (!empty($wpdb->last_error)) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }
        
        // Check if query execution failed
        if ($deleted === false) {
            wp_send_json_error(array('message' => 'Failed to execute delete query'));
        }
        
        // Verify deletion by checking if sequence test still exists
        $still_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE id = %d",
            $sequence_test_id
        ));
        
        if ($still_exists > 0) {
            // Sequence test still exists, deletion failed
            wp_send_json_error(array('message' => 'Test could not be removed from sequence. Please try again or contact support.'));
        } else {
            // Sequence test was successfully deleted
            wp_send_json_success(array('message' => 'Test removed from sequence successfully'));
        }
    }
}

// AJAX handlers will be initialized by QTest_Frontend and QTest_Admin
