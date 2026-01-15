<?php
/**
 * Database operations for QTest
 */

if (!defined('ABSPATH')) {
    exit;
}

class QTest_Database {
    
    /**
     * Helper function to add column if it doesn't exist
     */
    public static function add_column_if_not_exists($table, $column, $definition, $after = null) {
        global $wpdb;
        
        // Check if column exists using multiple methods for compatibility
        $column_exists = false;
        
        // Method 1: Check using INFORMATION_SCHEMA
        $check_query = $wpdb->prepare(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table,
            $column
        );
        $result = $wpdb->get_var($check_query);
        
        if ($result > 0) {
            $column_exists = true;
        } else {
            // Method 2: Try to describe table (fallback)
            $columns = $wpdb->get_results("DESCRIBE $table");
            foreach ($columns as $col) {
                if ($col->Field === $column) {
                    $column_exists = true;
                    break;
                }
            }
        }
        
        // Add column if it doesn't exist
        if (!$column_exists) {
            $after_clause = $after ? " AFTER $after" : '';
            $query = "ALTER TABLE $table ADD COLUMN $column $definition$after_clause";
            $wpdb->query($query);
        }
    }
    
    /**
     * Create database tables on plugin activation
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tests table
        $table_tests = $wpdb->prefix . 'qtest_tests';
        $sql_tests = "CREATE TABLE IF NOT EXISTS $table_tests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            time_limit int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Check if time_limit column exists, if not add it
        self::add_column_if_not_exists($table_tests, 'time_limit', "int(11) DEFAULT 0", 'description');
        
        // Questions table
        $table_questions = $wpdb->prefix . 'qtest_questions';
        $sql_questions = "CREATE TABLE IF NOT EXISTS $table_questions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL,
            question_text text NOT NULL,
            question_image varchar(255),
            option_a varchar(255) NOT NULL,
            option_b varchar(255) NOT NULL,
            option_c varchar(255) NOT NULL,
            option_d varchar(255) NOT NULL,
            correct_answer varchar(255) NOT NULL,
            question_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $charset_collate;";
        
        // Results table
        $table_results = $wpdb->prefix . 'qtest_results';
        $sql_results = "CREATE TABLE IF NOT EXISTS $table_results (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            score int(11) NOT NULL,
            total_questions int(11) NOT NULL,
            answers text,
            time_started datetime,
            time_completed datetime,
            time_taken int(11) DEFAULT 0,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY email (email)
        ) $charset_collate;";
        
        // Check if time columns exist, if not add them
        self::add_column_if_not_exists($table_results, 'time_started', 'datetime', 'answers');
        self::add_column_if_not_exists($table_results, 'time_completed', 'datetime', 'time_started');
        self::add_column_if_not_exists($table_results, 'time_taken', "int(11) DEFAULT 0", 'time_completed');
        self::add_column_if_not_exists($table_results, 'question_times', 'text', 'time_taken');
        
        // Sequences table (for test bundles)
        $table_sequences = $wpdb->prefix . 'qtest_sequences';
        $sql_sequences = "CREATE TABLE IF NOT EXISTS $table_sequences (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Sequence tests table (tests in a sequence)
        $table_sequence_tests = $wpdb->prefix . 'qtest_sequence_tests';
        $sql_sequence_tests = "CREATE TABLE IF NOT EXISTS $table_sequence_tests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sequence_id bigint(20) NOT NULL,
            test_id bigint(20) NOT NULL,
            test_order int(11) NOT NULL DEFAULT 0,
            auto_continue tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sequence_id (sequence_id),
            KEY test_id (test_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tests);
        dbDelta($sql_questions);
        dbDelta($sql_results);
        dbDelta($sql_sequences);
        dbDelta($sql_sequence_tests);
    }
    
    /**
     * Get all tests
     */
    public static function get_tests() {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_tests';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Get test by ID
     */
    public static function get_test($test_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_tests';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $test_id));
    }
    
    /**
     * Get questions for a test
     */
    public static function get_questions($test_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_questions';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE test_id = %d ORDER BY question_order ASC, id ASC",
            $test_id
        ));
    }
    
    /**
     * Save test result
     */
    public static function save_result($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_results';
        
        $insert_data = array(
            'test_id' => $data['test_id'],
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => sanitize_email($data['email']),
            'score' => intval($data['score']),
            'total_questions' => intval($data['total_questions']),
            'answers' => json_encode($data['answers'])
        );
        
        $format = array('%d', '%s', '%s', '%s', '%d', '%d', '%s');
        
        // Add time fields if provided
        if (isset($data['time_started'])) {
            $insert_data['time_started'] = sanitize_text_field($data['time_started']);
            $format[] = '%s';
        }
        if (isset($data['time_completed'])) {
            $insert_data['time_completed'] = sanitize_text_field($data['time_completed']);
            $format[] = '%s';
        }
        if (isset($data['time_taken'])) {
            $insert_data['time_taken'] = intval($data['time_taken']);
            $format[] = '%d';
        }
        if (isset($data['question_times'])) {
            $insert_data['question_times'] = json_encode($data['question_times']);
            $format[] = '%s';
        }
        
        return $wpdb->insert($table, $insert_data, $format);
    }
    
    /**
     * Get average time per question for a test
     */
    public static function get_average_times_per_question($test_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_results';
        
        // Get all results for this test that have question_times
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT question_times FROM $table WHERE test_id = %d AND question_times IS NOT NULL AND question_times != ''",
            $test_id
        ));
        
        if (empty($results)) {
            return array();
        }
        
        // Calculate average time for each question
        // Store all times for each question to calculate accurate average
        $question_times_list = array();
        
        foreach ($results as $result) {
            $times = json_decode($result->question_times, true);
            if (is_array($times)) {
                foreach ($times as $question_id => $time) {
                    // Convert to float to preserve decimal precision
                    $time_float = floatval($time);
                    if (!isset($question_times_list[$question_id])) {
                        $question_times_list[$question_id] = array();
                    }
                    $question_times_list[$question_id][] = $time_float;
                }
            }
        }
        
        // Calculate averages with proper precision
        $averages = array();
        foreach ($question_times_list as $question_id => $times) {
            if (count($times) > 0) {
                $sum = array_sum($times);
                $count = count($times);
                // Round to 2 decimal places for display
                $averages[$question_id] = round($sum / $count, 2);
            }
        }
        
        return $averages;
    }
    
    /**
     * Upgrade database - add missing columns
     */
    public static function upgrade_database() {
        global $wpdb;
        
        $table_tests = $wpdb->prefix . 'qtest_tests';
        $table_questions = $wpdb->prefix . 'qtest_questions';
        $table_results = $wpdb->prefix . 'qtest_results';
        
        // Check if tables exist first
        $tests_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_tests'") === $table_tests;
        $questions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_questions'") === $table_questions;
        $results_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_results'") === $table_results;
        
        if ($tests_table_exists) {
            // Add time_limit to tests table
            self::add_column_if_not_exists($table_tests, 'time_limit', "int(11) DEFAULT 0", 'description');
            // Add allowed_roles for user role restriction
            self::add_column_if_not_exists($table_tests, 'allowed_roles', 'text', 'time_limit');
        }
        
        if ($questions_table_exists) {
            // Add question_type for different question types
            self::add_column_if_not_exists($table_questions, 'question_type', "varchar(50) DEFAULT 'multiple_choice'", 'question_text');
            
            // Modify correct_answer column to support longer text for short_answer questions
            // Check current column definition
            $column_info = $wpdb->get_row($wpdb->prepare(
                "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'correct_answer'",
                DB_NAME,
                $table_questions
            ));
            
            if ($column_info && strpos($column_info->COLUMN_TYPE, 'varchar(1)') !== false) {
                // Column is still varchar(1), need to alter it to varchar(255) to support short_answer
                $wpdb->query("ALTER TABLE $table_questions MODIFY COLUMN correct_answer varchar(255) NOT NULL");
            }
        }
        
        if ($results_table_exists) {
            // Add time columns to results table
            self::add_column_if_not_exists($table_results, 'time_started', 'datetime', 'answers');
            self::add_column_if_not_exists($table_results, 'time_completed', 'datetime', 'time_started');
            self::add_column_if_not_exists($table_results, 'time_taken', "int(11) DEFAULT 0", 'time_completed');
            self::add_column_if_not_exists($table_results, 'question_times', 'text', 'time_taken');
        }
        
        // Create sequences tables if they don't exist
        $table_sequences = $wpdb->prefix . 'qtest_sequences';
        $table_sequence_tests = $wpdb->prefix . 'qtest_sequence_tests';
        $sequences_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_sequences'") === $table_sequences;
        $sequence_tests_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_sequence_tests'") === $table_sequence_tests;
        
        if (!$sequences_table_exists || !$sequence_tests_table_exists) {
            // Re-run create_tables to ensure sequences tables are created
            self::create_tables();
        }
    }
    
    /**
     * Get result by email and test ID
     */
    public static function get_result($email, $test_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_results';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s AND test_id = %d ORDER BY completed_at DESC LIMIT 1",
            $email,
            $test_id
        ));
    }
    
    /**
     * Get all results (optionally filtered by test_id)
     */
    public static function get_all_results($test_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_results';
        
        if ($test_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE test_id = %d ORDER BY completed_at DESC",
                $test_id
            ));
        } else {
            return $wpdb->get_results("SELECT * FROM $table ORDER BY completed_at DESC");
        }
    }
    
    /**
     * Get result by ID
     */
    public static function get_result_by_id($result_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_results';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $result_id
        ));
    }
    
    /**
     * Get all sequences
     */
    public static function get_sequences() {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequences';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Get sequence by ID
     */
    public static function get_sequence($sequence_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequences';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $sequence_id));
    }
    
    /**
     * Get tests in a sequence
     */
    public static function get_sequence_tests($sequence_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequence_tests';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE sequence_id = %d ORDER BY test_order ASC",
            $sequence_id
        ));
    }
    
    /**
     * Get first test in sequence
     */
    public static function get_first_test_in_sequence($sequence_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequence_tests';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE sequence_id = %d ORDER BY test_order ASC LIMIT 1",
            $sequence_id
        ));
    }
    
    /**
     * Get next test in sequence after a given test
     */
    public static function get_next_test_in_sequence($sequence_id, $current_test_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qtest_sequence_tests';
        
        // Get current test order
        $current = $wpdb->get_row($wpdb->prepare(
            "SELECT test_order FROM $table WHERE sequence_id = %d AND test_id = %d",
            $sequence_id,
            $current_test_id
        ));
        
        if (!$current) {
            return null;
        }
        
        // Get next test
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE sequence_id = %d AND test_order > %d ORDER BY test_order ASC LIMIT 1",
            $sequence_id,
            $current->test_order
        ));
    }
}
