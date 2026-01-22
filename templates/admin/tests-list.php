<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">QTest - All Tests</h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=quicktestwp-new')); ?>" class="page-title-action">Add New</a>
    
    <hr class="wp-header-end">
    
    <div class="notice notice-info" style="margin: 20px 0;">
        <p><strong>What is a Test?</strong> A test is a single quiz with multiple questions. Users take one test at a time.</p>
        <p><strong>How to use:</strong> Insert a test into your post using the shortcode <code>[quicktestwp id="TEST_ID"]</code>. When editing a post, you'll see an "Insert QuickTestWP" meta box on the right sidebar. Select a test and click "Insert Test" button.</p>
        <p><strong>Difference from Sequences:</strong> A test is standalone. If you want to combine multiple tests that users take one after another, use <strong>Test Sequences</strong> instead (see Sequences menu).</p>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Shortcode</th>
                <th>Description</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tests)): ?>
                <tr>
                    <td colspan="6">No tests found. <a href="<?php echo esc_url(admin_url('admin.php?page=quicktestwp-new')); ?>">Create your first test</a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?php echo esc_html($test->id); ?></td>
                        <td><strong><?php echo esc_html($test->title); ?></strong></td>
                        <td>
                            <code style="background: #f0f0f0; padding: 3px 6px; border-radius: 3px;">[quicktestwp id="<?php echo esc_attr($test->id); ?>"]</code>
                            <button type="button" class="button button-small quicktestwp-copy-shortcode" data-shortcode='[quicktestwp id="<?php echo esc_attr($test->id); ?>"]' style="margin-left: 5px;">Copy</button>
                        </td>
                        <td><?php echo esc_html($test->description); ?></td>
                        <td><?php echo esc_html($test->created_at); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=quicktestwp-new&test_id=' . $test->id)); ?>" 
                               class="button button-small" 
                               title="Edit Test">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <button type="button" 
                                    class="button button-small qtest-delete-test" 
                                    data-test-id="<?php echo esc_attr($test->id); ?>"
                                    title="Delete Test"
                                    style="color: #a00; margin-left: 5px;">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
