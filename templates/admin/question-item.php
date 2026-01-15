<?php
if (!defined('ABSPATH')) {
    exit;
}
$index = isset($index) ? $index : '{{INDEX}}';
$question_id = isset($question->id) ? $question->id : 0;
$current_type = isset($question->question_type) && !empty($question->question_type) ? $question->question_type : 'multiple_choice';
?>

<div class="qtest-question-item" data-question-id="<?php echo $question_id; ?>" data-index="<?php echo is_numeric($index) ? $index : 0; ?>" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
    <div class="qtest-question-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;">
        <h3 style="margin: 0;">Question #<span class="question-number"><?php echo (is_numeric($index) ? intval($index) : 0) + 1; ?></span></h3>
        <button type="button" class="button qtest-delete-question" data-question-id="<?php echo $question_id; ?>">Delete</button>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row"><label for="question_type_<?php echo $question_id; ?>">Question Type <span class="description">(required)</span></label></th>
            <td>
                <select id="question_type_<?php echo $question_id; ?>" name="question_type" class="regular-text qtest-question-type" required>
                    <option value="multiple_choice" <?php selected($current_type, 'multiple_choice'); ?>>Multiple Choice (A, B, C, D)</option>
                    <option value="true_false" <?php selected($current_type, 'true_false'); ?>>True/False</option>
                    <option value="short_answer" <?php selected($current_type, 'short_answer'); ?>>Short Answer (Text Input)</option>
                </select>
                <p class="description">Select the type of question. This will determine how the question is displayed and answered.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="question_text_<?php echo $question_id; ?>">Question Text <span class="description">(required)</span></label></th>
            <td>
                <textarea id="question_text_<?php echo $question_id; ?>" name="question_text" class="large-text" rows="4" required placeholder="Enter your question here..."><?php echo esc_textarea($question->question_text); ?></textarea>
                <p class="description">Enter the question text that will be displayed to users.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Question Image <span class="description">(optional)</span></label></th>
            <td>
                <div class="qtest-image-upload">
                    <input type="hidden" name="question_image" class="qtest-image-url" value="<?php echo esc_url($question->question_image); ?>">
                    <div class="qtest-image-preview" style="margin-bottom: 10px;">
                        <?php if ($question->question_image): ?>
                            <img src="<?php echo esc_url($question->question_image); ?>" style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px; display: block; margin-bottom: 10px;">
                        <?php else: ?>
                            <p class="description" style="color: #666;">No image uploaded. Click "Upload Image" to add an image for this question.</p>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button qtest-upload-image">Upload Image</button>
                    <button type="button" class="button qtest-remove-image" style="<?php echo $question->question_image ? '' : 'display:none;'; ?>">Remove Image</button>
                    <p class="description">Upload an image to accompany this question (optional).</p>
                </div>
            </td>
        </tr>
        <tbody class="qtest-options-container" data-question-type="multiple_choice" style="<?php echo ($current_type === 'multiple_choice') ? '' : 'display: none;'; ?>">
            <tr class="qtest-option-row" data-option="a">
                <th scope="row"><label for="option_a_<?php echo $question_id; ?>">Option A <span class="description">(required)</span></label></th>
                <td>
                    <input type="text" id="option_a_<?php echo $question_id; ?>" name="option_a" value="<?php echo esc_attr($question->option_a); ?>" class="regular-text" placeholder="Enter option A">
                </td>
            </tr>
            <tr class="qtest-option-row" data-option="b">
                <th scope="row"><label for="option_b_<?php echo $question_id; ?>">Option B <span class="description">(required)</span></label></th>
                <td>
                    <input type="text" id="option_b_<?php echo $question_id; ?>" name="option_b" value="<?php echo esc_attr($question->option_b); ?>" class="regular-text" placeholder="Enter option B">
                </td>
            </tr>
            <tr class="qtest-option-row" data-option="c">
                <th scope="row"><label for="option_c_<?php echo $question_id; ?>">Option C <span class="description">(required)</span></label></th>
                <td>
                    <input type="text" id="option_c_<?php echo $question_id; ?>" name="option_c" value="<?php echo esc_attr($question->option_c); ?>" class="regular-text" placeholder="Enter option C">
                </td>
            </tr>
            <tr class="qtest-option-row" data-option="d">
                <th scope="row"><label for="option_d_<?php echo $question_id; ?>">Option D <span class="description">(required)</span></label></th>
                <td>
                    <input type="text" id="option_d_<?php echo $question_id; ?>" name="option_d" value="<?php echo esc_attr($question->option_d); ?>" class="regular-text" placeholder="Enter option D">
                </td>
            </tr>
            <tr class="qtest-correct-answer-row">
                <th scope="row"><label for="correct_answer_<?php echo $question_id; ?>">Correct Answer <span class="description">(required)</span></label></th>
                <td>
                    <select id="correct_answer_<?php echo $question_id; ?>" name="correct_answer" class="regular-text">
                        <option value="A" <?php selected($question->correct_answer, 'A'); ?>>A</option>
                        <option value="B" <?php selected($question->correct_answer, 'B'); ?>>B</option>
                        <option value="C" <?php selected($question->correct_answer, 'C'); ?>>C</option>
                        <option value="D" <?php selected($question->correct_answer, 'D'); ?>>D</option>
                    </select>
                    <p class="description">Select which option (A, B, C, or D) is the correct answer.</p>
                </td>
            </tr>
        </tbody>
        <tbody class="qtest-options-container" data-question-type="true_false" style="<?php echo ($current_type === 'true_false') ? '' : 'display: none;'; ?>">
            <tr>
                <th scope="row"><label for="correct_answer_tf_<?php echo $question_id; ?>">Correct Answer <span class="description">(required)</span></label></th>
                <td>
                    <select id="correct_answer_tf_<?php echo $question_id; ?>" name="correct_answer" class="regular-text">
                        <option value="True" <?php selected($question->correct_answer, 'True'); ?>>True</option>
                        <option value="False" <?php selected($question->correct_answer, 'False'); ?>>False</option>
                    </select>
                    <p class="description">Select whether the statement is True or False.</p>
                </td>
            </tr>
        </tbody>
        <tbody class="qtest-options-container" data-question-type="short_answer" style="<?php echo ($current_type === 'short_answer') ? '' : 'display: none;'; ?>">
            <tr>
                <th scope="row"><label for="correct_answer_sa_<?php echo $question_id; ?>">Correct Answer <span class="description">(required)</span></label></th>
                <td>
                    <input type="text" id="correct_answer_sa_<?php echo $question_id; ?>" name="correct_answer" value="<?php echo esc_attr($question->correct_answer); ?>" class="regular-text" placeholder="Enter the correct answer">
                    <p class="description">Enter the expected answer. User's answer will be compared (case-insensitive).</p>
                </td>
            </tr>
        </tbody>
        <tr>
            <th scope="row"><label for="question_order_<?php echo $question_id; ?>">Order</label></th>
            <td>
                <input type="number" id="question_order_<?php echo $question_id; ?>" name="question_order" value="<?php echo esc_attr($question->question_order); ?>" class="small-text" min="0" placeholder="0">
                <p class="description">Set the display order (lower numbers appear first). Leave as 0 for default order.</p>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <button type="button" class="button button-primary qtest-save-question">Save Question</button>
        <span class="qtest-save-status" style="margin-left: 10px; color: #46b450; display: none;">✓ Saved</span>
    </p>
    
    <hr style="margin: 20px 0;">
</div>
