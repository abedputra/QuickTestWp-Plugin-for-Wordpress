<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Test Sequences</h1>
    <a href="<?php echo admin_url('admin.php?page=qtest-sequence-new'); ?>" class="page-title-action">Add New Sequence</a>
    
    <hr class="wp-header-end">
    
    <div class="notice notice-info" style="margin: 20px 0;">
        <p><strong>What is a Test Sequence?</strong> A sequence is a bundle of multiple tests that users will take one after another in a specific order.</p>
        <p><strong>How to use:</strong> Insert a sequence into your post using the shortcode <code>[qtest_sequence id="SEQUENCE_ID"]</code>. Users will automatically start with the first test in the sequence.</p>
        <p><strong>Features:</strong></p>
        <ul style="margin-left: 20px; list-style: disc;">
            <li>Combine multiple tests into one flow</li>
            <li>Set the order in which tests are taken</li>
            <li>Configure auto-continue (automatic) or confirmation (user must confirm) between tests</li>
            <li>Users complete all tests in the sequence sequentially</li>
        </ul>
        <p><strong>Difference from Tests:</strong> A single test is standalone. A sequence combines multiple tests. Use sequences when you want users to take several tests in a specific order.</p>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Tests</th>
                <th>Shortcode</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sequences)): ?>
                <tr>
                    <td colspan="7">No sequences found. <a href="<?php echo admin_url('admin.php?page=qtest-sequence-new'); ?>">Create your first sequence</a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($sequences as $sequence): 
                    $seq_tests = QTest_Database::get_sequence_tests($sequence->id);
                ?>
                    <tr>
                        <td><?php echo esc_html($sequence->id); ?></td>
                        <td><strong><?php echo esc_html($sequence->title); ?></strong></td>
                        <td><?php echo esc_html($sequence->description); ?></td>
                        <td>
                            <?php if (!empty($seq_tests)): ?>
                                <?php echo count($seq_tests); ?> test(s)
                            <?php else: ?>
                                No tests
                            <?php endif; ?>
                        </td>
                        <td>
                            <code style="background: #f0f0f0; padding: 3px 6px; border-radius: 3px;">[qtest_sequence id="<?php echo esc_attr($sequence->id); ?>"]</code>
                            <button type="button" class="button button-small qtest-copy-shortcode" data-shortcode='[qtest_sequence id="<?php echo esc_attr($sequence->id); ?>"]' style="margin-left: 5px;">Copy</button>
                        </td>
                        <td><?php echo esc_html($sequence->created_at); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=qtest-sequences&sequence_id=' . $sequence->id); ?>" 
                               class="button button-small" title="Edit Sequence">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <button type="button" 
                                    class="button button-small qtest-delete-sequence" 
                                    data-sequence-id="<?php echo $sequence->id; ?>"
                                    title="Delete Sequence"
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
