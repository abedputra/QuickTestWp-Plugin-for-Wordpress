<?php

/**
 * Frontend interface for QuickTestWP
 */

if (!defined('ABSPATH')) {
    exit;
}

class QuickTestWP_Frontend
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('quicktestwp', array($this, 'render_quiz_shortcode'));
        add_shortcode('quicktestwp_sequence', array($this, 'render_sequence_shortcode'));
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_custom_pages'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style('quicktestwp-popup', QUICKTESTWP_PLUGIN_URL . 'assets/css/popup.css', array(), QUICKTESTWP_VERSION);
        wp_enqueue_script('quicktestwp-popup', QUICKTESTWP_PLUGIN_URL . 'assets/js/qtest-popup.js', array('jquery'), QUICKTESTWP_VERSION, true);
        wp_enqueue_style('quicktestwp-frontend', QUICKTESTWP_PLUGIN_URL . 'assets/css/frontend.css', array(), QUICKTESTWP_VERSION);
        wp_enqueue_script('quicktestwp-security', QUICKTESTWP_PLUGIN_URL . 'assets/js/qtest-security.js', array('jquery', 'quicktestwp-popup'), QUICKTESTWP_VERSION, true);
        wp_enqueue_script('quicktestwp-frontend', QUICKTESTWP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'quicktestwp-popup', 'quicktestwp-security'), QUICKTESTWP_VERSION, true);

        wp_localize_script('quicktestwp-frontend', 'quicktestwpAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('quicktestwp_nonce')
        ));
    }

    /**
     * Register rewrite rules for custom pages
     */
    public function register_rewrite_rules()
    {
        add_rewrite_rule('^quicktestwp-result/?$', 'index.php?quicktestwp_page=result', 'top');
        add_rewrite_rule('^quicktestwp/([0-9]+)/?$', 'index.php?quicktestwp_page=quiz&test_id=$matches[1]', 'top');
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'quicktestwp_page';
        $vars[] = 'test_id';
        $vars[] = 'sequence_id';
        return $vars;
    }

    /**
     * Handle custom pages
     */
    public function handle_custom_pages()
    {
        $page = get_query_var('quicktestwp_page');

        if ($page === 'quiz') {
            $test_id = intval(get_query_var('test_id'));
            $sequence_id = isset($_GET['sequence_id']) ? intval($_GET['sequence_id']) : null;
            $this->render_quiz_page($test_id, $sequence_id);
            exit;
        } elseif ($page === 'result') {
            $this->render_result_page();
            exit;
        }
    }

    /**
     * Render quiz shortcode
     */
    public function render_quiz_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        $test_id = intval($atts['id']);
        if (!$test_id) {
            return '<p>Please provide a valid test ID.</p>';
        }

        ob_start();
        $this->render_quiz($test_id, null);
        return ob_get_clean();
    }

    /**
     * Render sequence shortcode
     */
    public function render_sequence_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        $sequence_id = intval($atts['id']);
        if (!$sequence_id) {
            return '<p>Please provide a valid sequence ID.</p>';
        }

        $sequence = QTest_Database::get_sequence($sequence_id);
        if (!$sequence) {
            return '<p>Sequence not found.</p>';
        }

        // Get test_id from URL parameter if provided (for navigation between tests in sequence)
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        
        // If test_id provided, verify it belongs to this sequence
        if ($test_id > 0) {
            global $wpdb;
            $table = $wpdb->prefix . 'quicktestwp_sequence_tests';
            $test_in_sequence = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE sequence_id = %d AND test_id = %d",
                $sequence_id,
                $test_id
            ));
            
            if ($test_in_sequence > 0) {
                // Test belongs to sequence, use it
                $current_test = QuickTestWP_Database::get_test($test_id);
                if ($current_test) {
                    ob_start();
                    $this->render_quiz($test_id, $sequence_id);
                    return ob_get_clean();
                }
            }
            // If test_id doesn't belong to sequence or test not found, fall through to first test
        }
        
        // Get first test in sequence (default or fallback)
        $first_test = QuickTestWP_Database::get_first_test_in_sequence($sequence_id);
        if (!$first_test) {
            return '<p>No tests found in this sequence.</p>';
        }

        ob_start();
        // Pass sequence_id via URL parameter for proper handling
        $this->render_quiz($first_test->test_id, $sequence_id);
        return ob_get_clean();
    }

    /**
     * Render quiz page
     */
    private function render_quiz_page($test_id, $sequence_id = null)
    {
        // Get sequence_id from URL if not provided
        if ($sequence_id === null) {
            $sequence_id = isset($_GET['sequence_id']) ? intval($_GET['sequence_id']) : null;
        }

        $test = QuickTestWP_Database::get_test($test_id);
        if (!$test) {
            wp_die('Test not found');
        }

        $this->render_quiz($test_id, $sequence_id);
    }

    /**
     * Render quiz interface
     */
    private function render_quiz($test_id, $sequence_id = null)
    {
        $test = QuickTestWP_Database::get_test($test_id);

        if (!$test) {
            echo '<p>Test not found.</p>';
            return;
        }

        // Check user role access
        $allowed_roles = isset($test->allowed_roles) && !empty($test->allowed_roles) ? json_decode($test->allowed_roles, true) : array();
        if (!empty($allowed_roles) && is_array($allowed_roles)) {
            // Only check if user is logged in
            if (!is_user_logged_in()) {
                echo '<p>You must be logged in to access this test.</p>';
                return;
            }

            $user = wp_get_current_user();
            $user_roles = $user->roles;
            $has_access = false;

            foreach ($user_roles as $role) {
                if (in_array($role, $allowed_roles)) {
                    $has_access = true;
                    break;
                }
            }

            if (!$has_access) {
                echo '<p>You do not have permission to access this test. Please contact the administrator.</p>';
                return;
            }
        }

        $questions = QuickTestWP_Database::get_questions($test_id);

        if (empty($questions)) {
            echo '<p>No questions found for this test.</p>';
            return;
        }

        // Pass sequence info to template
        $sequence_info = null;
        if ($sequence_id) {
            $sequence = QuickTestWP_Database::get_sequence($sequence_id);
            $next_test = QuickTestWP_Database::get_next_test_in_sequence($sequence_id, $test_id);
            
            // Only set next_test if it exists and is different from current test
            $next_test_data = null;
            if ($next_test && isset($next_test->test_id) && $next_test->test_id != $test_id) {
                $next_test_data = array(
                    'test_id' => intval($next_test->test_id),
                    'auto_continue' => intval($next_test->auto_continue)
                );
            }
            
            $sequence_info = array(
                'sequence_id' => intval($sequence_id),
                'sequence_title' => $sequence ? $sequence->title : '',
                'next_test' => $next_test_data
            );
        }

        include QUICKTESTWP_PLUGIN_DIR . 'templates/frontend/quiz.php';
    }

    /**
     * Render result page
     */
    private function render_result_page()
    {
        include QUICKTESTWP_PLUGIN_DIR . 'templates/frontend/result.php';
    }
}
