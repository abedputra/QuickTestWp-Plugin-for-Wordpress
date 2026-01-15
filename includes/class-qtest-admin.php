<?php

/**
 * Admin interface for QTest
 */

if (!defined('ABSPATH')) {
    exit;
}

class QTest_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add QTest button to post editor
        add_action('admin_init', array($this, 'add_editor_buttons'));
        add_action('add_meta_boxes', array($this, 'add_test_meta_box'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'QTest',
            'QTest',
            'manage_options',
            'qtest',
            array($this, 'render_tests_page'),
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'qtest',
            'Tests',
            'All Tests',
            'manage_options',
            'qtest',
            array($this, 'render_tests_page')
        );

        add_submenu_page(
            'qtest',
            'Add New Test',
            'Add New',
            'manage_options',
            'qtest-new',
            array($this, 'render_new_test_page')
        );

        add_submenu_page(
            'qtest',
            'Test Sequences',
            'All Sequences',
            'manage_options',
            'qtest-sequences',
            array($this, 'render_sequences_page')
        );

        add_submenu_page(
            'qtest',
            'Add New Sequence',
            'Add New Sequence',
            'manage_options',
            'qtest-sequence-new',
            array($this, 'render_new_sequence_page')
        );

        add_submenu_page(
            'qtest',
            'Test Results',
            'Results',
            'manage_options',
            'qtest-results',
            array($this, 'render_results_page')
        );

        add_submenu_page(
            'qtest',
            'Import Questions',
            'Import Questions',
            'manage_options',
            'qtest-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Enqueue for QTest pages
        if (strpos($hook, 'qtest') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
            wp_enqueue_style('qtest-popup', QTEST_PLUGIN_URL . 'assets/css/popup.css', array(), QTEST_VERSION);
            wp_enqueue_script('qtest-popup', QTEST_PLUGIN_URL . 'assets/js/qtest-popup.js', array('jquery'), QTEST_VERSION, true);
            wp_enqueue_style('qtest-admin', QTEST_PLUGIN_URL . 'assets/css/admin.css', array(), QTEST_VERSION);
            wp_enqueue_script('qtest-admin', QTEST_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'qtest-popup'), QTEST_VERSION, true);
        }

        // Enqueue for post editor
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('qtest-editor', QTEST_PLUGIN_URL . 'assets/js/editor.js', array('jquery', 'quicktags'), QTEST_VERSION, true);
            wp_localize_script('qtest-editor', 'qtestEditor', array(
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
        $tests = QTest_Database::get_tests();
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
                    QTags.addButton('qtest', 'QTest', function() {
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
                            QTags.insertContent('[qtest id="' + testId + '"]');
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
                'qtest_insert',
                'Insert QTest',
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
        $tests = QTest_Database::get_tests();
    ?>
        <div class="qtest-insert-box">
            <p>Select a test to insert into your post:</p>
            <select id="qtest-select-test" class="widefat">
                <option value="">-- Select Test --</option>
                <?php foreach ($tests as $test): ?>
                    <option value="<?php echo esc_attr($test->id); ?>"><?php echo esc_html($test->title); ?> (ID: <?php echo $test->id; ?>)</option>
                <?php endforeach; ?>
            </select>
            <p class="description">This will insert a shortcode into your post.</p>
            <p>
                <button type="button" id="qtest-insert-shortcode" class="button button-primary">Insert Test</button>
            </p>
            <p class="description">
                <strong>Or manually use:</strong><br>
                <code>[qtest id="1"]</code><br>
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
        $tests = QTest_Database::get_tests();

        include QTEST_PLUGIN_DIR . 'templates/admin/tests-list.php';
    }

    /**
     * Render new/edit test page
     */
    public function render_new_test_page()
    {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        $test = $test_id ? QTest_Database::get_test($test_id) : null;
        $questions = $test_id ? QTest_Database::get_questions($test_id) : array();

        include QTEST_PLUGIN_DIR . 'templates/admin/test-edit.php';
    }

    /**
     * Render results page
     */
    public function render_results_page()
    {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : null;
        $results = QTest_Database::get_all_results($test_id);
        $tests = QTest_Database::get_tests();

        include QTEST_PLUGIN_DIR . 'templates/admin/results-list.php';
    }

    /**
     * Render import page
     */
    public function render_import_page()
    {
        $tests = QTest_Database::get_tests();
        include QTEST_PLUGIN_DIR . 'templates/admin/import-questions.php';
    }

    /**
     * Render sequences page
     */
    public function render_sequences_page()
    {
        $sequence_id = isset($_GET['sequence_id']) ? intval($_GET['sequence_id']) : 0;
        $sequences = QTest_Database::get_sequences();
        $sequence = $sequence_id ? QTest_Database::get_sequence($sequence_id) : null;
        $sequence_tests = $sequence_id ? QTest_Database::get_sequence_tests($sequence_id) : array();
        $tests = QTest_Database::get_tests();

        // Show edit form if editing existing sequence
        if ($sequence_id && $sequence) {
            include QTEST_PLUGIN_DIR . 'templates/admin/sequence-edit.php';
        } else {
            include QTEST_PLUGIN_DIR . 'templates/admin/sequences-list.php';
        }
    }

    /**
     * Render new sequence page
     */
    public function render_new_sequence_page()
    {
        $tests = QTest_Database::get_tests();
        $sequence = null; // New sequence
        $sequence_tests = array(); // No tests yet
        include QTEST_PLUGIN_DIR . 'templates/admin/sequence-edit.php';
    }
}
