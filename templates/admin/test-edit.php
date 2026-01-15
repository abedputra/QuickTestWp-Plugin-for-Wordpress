<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo $test ? 'Edit Test' : 'Add New Test'; ?></h1>
    
    <form id="qtest-test-form">
        <input type="hidden" id="test_id" name="test_id" value="<?php echo $test ? $test->id : 0; ?>">
        <input type="hidden" id="qtest_nonce" value="<?php echo wp_create_nonce('qtest_nonce'); ?>">
        
        <table class="form-table">
            <tr>
                <th><label for="test_title">Test Title</label></th>
                <td><input type="text" id="test_title" name="title" value="<?php echo $test ? esc_attr($test->title) : ''; ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="test_description">Description</label></th>
                <td><textarea id="test_description" name="description" rows="3" class="large-text"><?php echo $test ? esc_textarea($test->description) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="test_time_limit">Time Limit (minutes)</label></th>
                <td>
                    <input type="number" id="test_time_limit" name="time_limit" value="<?php echo $test ? esc_attr($test->time_limit) : 0; ?>" class="small-text" min="0" step="1">
                    <p class="description">
                        <strong>Total time for entire test</strong> (recommended). Set time limit in minutes for the complete test. Enter 0 for no time limit.<br>
                        Example: 30 = 30 minutes total, 60 = 1 hour total.<br>
                        <em>Note: This is the total time allowed to complete all questions, not per question.</em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="test_allowed_roles">Allowed User Roles</label></th>
                <td>
                    <?php
                    global $wp_roles;
                    $allowed_roles = $test && isset($test->allowed_roles) ? json_decode($test->allowed_roles, true) : array();
                    if (!is_array($allowed_roles)) {
                        $allowed_roles = array();
                    }
                    ?>
                    <fieldset>
                        <legend class="screen-reader-text"><span>Select which user roles can access this test</span></legend>
                        <?php foreach ($wp_roles->roles as $role_key => $role): ?>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $allowed_roles)); ?>>
                                <?php echo esc_html($role['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description">
                        <strong>Access Control:</strong> Select which WordPress user roles can access this test. Leave all unchecked to allow all users (including guests).<br>
                        <em>Note: Only logged-in users' roles will be checked. Guests can access if no roles are selected.</em>
                    </p>
                </td>
            </tr>
        </table>
        
    <p class="submit">
        <button type="submit" class="button button-primary">Save Test</button>
        <a href="<?php echo admin_url('admin.php?page=qtest'); ?>" class="button">Cancel</a>
    </p>
</form>

<?php if ($test): ?>
    <hr>
    <h2>Questions</h2>
    <p>Add questions to your test. You can upload images for each question and set the correct answer.</p>
    
    <div id="qtest-questions-list">
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $index => $question): ?>
                <?php include QTEST_PLUGIN_DIR . 'templates/admin/question-item.php'; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="description">No questions yet. Click "Add Question" below to add your first question.</p>
        <?php endif; ?>
    </div>
    
    <p>
        <button type="button" id="qtest-add-question" class="button button-primary">Add Question</button>
    </p>
    
    <template id="qtest-question-template">
        <?php 
        $question = (object)array(
            'id' => 0,
            'question_type' => 'multiple_choice',
            'question_text' => '',
            'question_image' => '',
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'option_d' => '',
            'correct_answer' => 'A',
            'question_order' => 0
        );
        $index = '{{INDEX}}';
        include QTEST_PLUGIN_DIR . 'templates/admin/question-item.php';
        ?>
    </template>
    
    <script type="text/javascript">
    // Ensure correct_answer value is cleared when switching to short_answer
    jQuery(document).ready(function($) {
        $(document).on('change', '.qtest-question-type', function() {
            const questionItem = $(this).closest('.qtest-question-item');
            const questionType = $(this).val();
            
            // If switching to short_answer, clear the correct_answer value from other types
            if (questionType === 'short_answer') {
                // Clear values from multiple_choice and true_false
                questionItem.find('.qtest-correct-answer-row select[name="correct_answer"]').val('');
                questionItem.find('[data-question-type="true_false"] select[name="correct_answer"]').val('');
            } else if (questionType === 'multiple_choice') {
                // Clear short_answer input
                questionItem.find('.qtest-options-container[data-question-type="short_answer"] input[name="correct_answer"]').val('');
            } else if (questionType === 'true_false') {
                // Clear short_answer input
                questionItem.find('.qtest-options-container[data-question-type="short_answer"] input[name="correct_answer"]').val('');
            }
        });
    });
    </script>
<?php else: ?>
    <div class="notice notice-info" style="margin-top: 20px;">
        <p><strong>Note:</strong> Please save the test first, then you can add questions.</p>
    </div>
<?php endif; ?>
</div>
