<?php

/**
 * Admin interface for QuickTestWP
 */

if (!defined('ABSPATH')) {
    exit;
}

class QuickTestWP_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add QuickTestWP button to post editor
        add_action('admin_init', array($this, 'add_editor_buttons'));
        add_action('add_meta_boxes', array($this, 'add_test_meta_box'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'QuickTestWP',
            'QuickTestWP',
            'manage_options',
            'quicktestwp',
            array($this, 'render_tests_page'),
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'quicktestwp',
            'Tests',
            'All Tests',
            'manage_options',
            'quicktestwp',
            array($this, 'render_tests_page')
        );

        add_submenu_page(
            'quicktestwp',
            'Add New Test',
            'Add New',
            'manage_options',
            'quicktestwp-new',
            array($this, 'render_new_test_page')
        );

        add_submenu_page(
            'quicktestwp',
            'Test Sequences',
            'All Sequences',
            'manage_options',
            'quicktestwp-sequences',
            array($this, 'render_sequences_page')
        );

        add_submenu_page(
            'quicktestwp',
            'Add New Sequence',
            'Add New Sequence',
            'manage_options',
            'quicktestwp-sequence-new',
            array($this, 'render_new_sequence_page')
        );

        add_submenu_page(
            'quicktestwp',
            'Test Results',
            'Results',
            'manage_options',
            'quicktestwp-results',
            array($this, 'render_results_page')
        );

        add_submenu_page(
            'quicktestwp',
            'Import Questions',
            'Import Questions',
            'manage_options',
            'quicktestwp-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Enqueue for QuickTestWP pages
        if (strpos($hook, 'quicktestwp') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
            wp_enqueue_style('quicktestwp-popup', QUICKTESTWP_PLUGIN_URL . 'assets/css/popup.css', array(), QUICKTESTWP_VERSION);
            wp_enqueue_script('quicktestwp-popup', QUICKTESTWP_PLUGIN_URL . 'assets/js/qtest-popup.js', array('jquery'), QUICKTESTWP_VERSION, true);
            wp_enqueue_style('quicktestwp-admin', QUICKTESTWP_PLUGIN_URL . 'assets/css/admin.css', array(), QUICKTESTWP_VERSION);
            wp_enqueue_script('quicktestwp-admin', QUICKTESTWP_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'quicktestwp-popup'), QUICKTESTWP_VERSION, true);
        }

        // Enqueue for post editor
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('quicktestwp-editor', QUICKTESTWP_PLUGIN_URL . 'assets/js/editor.js', array('jquery', 'quicktags'), QUICKTESTWP_VERSION, true);
            wp_localize_script('quicktestwp-editor', 'quicktestwpEditor', array(
                'tests' => $this->get_tests_for_editor()
            ));
        }

        // Enqueue quicktags for all admin pages (for quicktag button)
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('quicktags');
        }
    }

    /**
     * Get tests for editor dropdown
     */
    private function get_tests_for_editor()
    {
        $tests = QuickTestWP_Database::get_tests();
        $tests_array = array();
        foreach ($tests as $test) {
            $tests_array[] = array(
                'id' => $test->id,
                'title' => $test->title
            );
        }
        return $tests_array;
    }

    /**
     * Add editor buttons (Quicktag for Classic Editor)
     */
    public function add_editor_buttons()
    {
        // Add quicktag button for Classic Editor
        add_action('admin_print_footer_scripts', array($this, 'add_quicktag_button'), 100);
    }

    /**
     * Add quicktag button
     */
    public function add_quicktag_button()
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('post', 'post-new'))) {
            return;
        }

        $tests = $this->get_tests_for_editor();
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                if (typeof QTags !== 'undefined') {
                    var tests = <?php echo json_encode($tests); ?>;
                    QTags.addButton('quicktestwp', 'QuickTestWP', function() {
                        if (tests && tests.length > 0) {
                            var options = 'Select Test:\n\n';
                            tests.forEach(function(test) {
                                options += test.id + ' - ' + test.title + '\n';
                            });
                            options += '\nEnter Test ID:';
                            var testId = prompt(options);
                        } else {
                            var testId = prompt('Enter Test ID:');
                        }
                        if (testId !== null && testId !== '') {
                            QTags.insertContent('[quicktestwp id="' + testId + '"]');
                        }
                    });
                }
            });
        </script>
    <?php
    }

    /**
     * Add meta box to post editor
     */
    public function add_test_meta_box()
    {
        $post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'quicktestwp_insert',
                'Insert QuickTestWP',
                array($this, 'render_test_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render test meta box
     */
    public function render_test_meta_box($post)
    {
        $tests = QuickTestWP_Database::get_tests();
    ?>
        <div class="quicktestwp-insert-box">
            <p>Select a test to insert into your post:</p>
            <select id="quicktestwp-select-test" class="widefat">
                <option value="">-- Select Test --</option>
                <?php foreach ($tests as $test): ?>
                    <option value="<?php echo esc_attr($test->id); ?>"><?php echo esc_html($test->title); ?> (ID: <?php echo esc_html($test->id); ?>)</option>
                <?php endforeach; ?>
            </select>
            <p class="description">This will insert a shortcode into your post.</p>
            <p>
                <button type="button" id="quicktestwp-insert-shortcode" class="button button-primary">Insert Test</button>
            </p>
            <p class="description">
                <strong>Or manually use:</strong><br>
                <code>[quicktestwp id="1"]</code><br>
                Replace "1" with your test ID.
            </p>
        </div>
<?php
    }

    /**
     * Render tests list page
     */
    public function render_tests_page()
    {
        global $wpdb;
        $tests = QuickTestWP_Database::get_tests();

        include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/tests-list.php';
    }

    /**
     * Render new/edit test page
     */
    public function render_new_test_page()
    {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        $test = $test_id ? QuickTestWP_Database::get_test($test_id) : null;
        $questions = $test_id ? QuickTestWP_Database::get_questions($test_id) : array();

        include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/test-edit.php';
    }

    /**
     * Render results page
     */
    public function render_results_page()
    {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : null;
        $results = QuickTestWP_Database::get_all_results($test_id);
        $tests = QuickTestWP_Database::get_tests();

        include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/results-list.php';
    }

    /**
     * Render import page
     */
    public function render_import_page()
    {
        $tests = QuickTestWP_Database::get_tests();
        include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/import-questions.php';
    }

    /**
     * Render sequences page
     */
    public function render_sequences_page()
    {
        $sequence_id = isset($_GET['sequence_id']) ? intval($_GET['sequence_id']) : 0;
        $sequences = QuickTestWP_Database::get_sequences();
        $sequence = $sequence_id ? QuickTestWP_Database::get_sequence($sequence_id) : null;
        $sequence_tests = $sequence_id ? QuickTestWP_Database::get_sequence_tests($sequence_id) : array();
        $tests = QuickTestWP_Database::get_tests();

        // Show edit form if editing existing sequence
        if ($sequence_id && $sequence) {
            include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/sequence-edit.php';
        } else {
            include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/sequences-list.php';
        }
    }

    /**
     * Render new sequence page
     */
    public function render_new_sequence_page()
    {
        $tests = QuickTestWP_Database::get_tests();
        $sequence = null; // New sequence
        $sequence_tests = array(); // No tests yet
        include QUICKTESTWP_PLUGIN_DIR . 'templates/admin/sequence-edit.php';
    }
}
