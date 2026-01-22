<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo $sequence ? 'Edit Sequence' : 'Add New Sequence'; ?></h1>
    
    <form id="qtest-sequence-form">
        <input type="hidden" id="sequence_id" name="sequence_id" value="<?php echo $sequence ? esc_attr($sequence->id) : 0; ?>">
        <input type="hidden" id="quicktestwp_nonce" value="<?php echo esc_attr(wp_create_nonce('quicktestwp_nonce')); ?>">
        
        <table class="form-table">
            <tr>
                <th><label for="sequence_title">Sequence Title</label></th>
                <td><input type="text" id="sequence_title" name="title" value="<?php echo $sequence ? esc_attr($sequence->title) : ''; ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="sequence_description">Description</label></th>
                <td><textarea id="sequence_description" name="description" rows="3" class="large-text"><?php echo $sequence ? esc_textarea($sequence->description) : ''; ?></textarea></td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php echo $sequence ? 'Update Sequence' : 'Save Sequence'; ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=quicktestwp-sequences')); ?>" class="button">Cancel</a>
        </p>
    </form>

<?php if ($sequence): ?>
    <hr>
    <h2>Tests in Sequence</h2>
    <p>Add tests to your sequence. Users will take these tests in order.</p>
    
    <div id="qtest-sequence-tests-list">
        <?php if (!empty($sequence_tests)): ?>
            <?php foreach ($sequence_tests as $seq_test): 
                $test = QuickTestWP_Database::get_test($seq_test->test_id);
            ?>
                <div class="qtest-sequence-test-item" data-sequence-test-id="<?php echo esc_attr($seq_test->id); ?>" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Order <?php echo esc_html($seq_test->test_order); ?>:</strong> 
                            <?php echo $test ? esc_html($test->title) : 'Test ID: ' . esc_html($seq_test->test_id); ?>
                            <br>
                            <small style="color: #666;">
                                Auto Continue: <?php echo $seq_test->auto_continue ? 'Yes' : 'No (User confirmation required)'; ?>
                            </small>
                        </div>
                        <button type="button" class="button button-small qtest-remove-sequence-test" data-sequence-test-id="<?php echo esc_attr($seq_test->id); ?>">Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="description">No tests in sequence yet. Add tests below.</p>
        <?php endif; ?>
    </div>
    
    <h3>Add Test to Sequence</h3>
    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
        <table class="form-table">
            <tr>
                <th><label for="add_test_id">Select Test</label></th>
                <td>
                    <select id="add_test_id" class="regular-text">
                        <option value="">-- Select Test --</option>
                        <?php foreach ($tests as $test): ?>
                            <option value="<?php echo esc_attr($test->id); ?>"><?php echo esc_html($test->title); ?> (ID: <?php echo esc_html($test->id); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="add_test_order">Order</label></th>
                <td>
                    <input type="number" id="add_test_order" class="small-text" min="1" value="<?php echo esc_attr(!empty($sequence_tests) ? (max(array_column($sequence_tests, 'test_order')) + 1) : 1); ?>">
                    <p class="description">Lower numbers appear first. Tests will be taken in this order.</p>
                </td>
            </tr>
            <tr>
                <th><label for="add_auto_continue">Auto Continue</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="add_auto_continue" value="1" checked>
                        Automatically continue to next test (if unchecked, user will see confirmation popup)
                    </label>
                </td>
            </tr>
        </table>
        <p>
            <button type="button" id="quicktestwp-add-sequence-test" class="button button-primary">Add Test to Sequence</button>
        </p>
    </div>
<?php else: ?>
    <div class="notice notice-info" style="margin-top: 20px;">
        <p><strong>Note:</strong> Please save the sequence first, then you can add tests.</p>
    </div>
<?php endif; ?>
</div>
