<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Import Questions from CSV</h1>
    
    <hr class="wp-header-end">
    
    <div class="notice notice-info" style="margin: 20px 0;">
        <p><strong>How to import questions:</strong></p>
        <ol>
            <li>Download the CSV template below</li>
            <li>Fill in your questions following the format</li>
            <li>Select the test where you want to import questions</li>
            <li>Upload the CSV file</li>
        </ol>
    </div>
    
    <div class="qtest-import-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
        <h2>Import Questions</h2>
        <form id="quicktestwp-import-form" enctype="multipart/form-data">
            <input type="hidden" id="quicktestwp_nonce" value="<?php echo esc_attr(wp_create_nonce('quicktestwp_nonce')); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="import_test_id">Select Test</label></th>
                    <td>
                        <select id="import_test_id" name="test_id" class="regular-text" required>
                            <option value="">-- Select Test --</option>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?php echo esc_attr($test->id); ?>"><?php echo esc_html($test->title); ?> (ID: <?php echo esc_html($test->id); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select the test where you want to import questions.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="import_csv_file">CSV File</label></th>
                    <td>
                        <input type="file" id="import_csv_file" name="csv_file" accept=".csv" required>
                        <p class="description">Upload a CSV file with questions. Maximum file size: 2MB.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Import Questions</button>
                <a href="<?php echo esc_url(QTEST_PLUGIN_URL . 'templates/admin/sample-questions.csv'); ?>" class="button" download>Download CSV Template</a>
            </p>
        </form>
    </div>
    
    <div class="qtest-csv-format-info" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px;">
        <h2>CSV Format</h2>
        <p>The CSV file should have the following columns (in order):</p>
        <table class="widefat" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Description</th>
                    <th>Required</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>question_type</strong></td>
                    <td>Type of question: multiple_choice, true_false, or short_answer</td>
                    <td>Yes</td>
                    <td>multiple_choice</td>
                </tr>
                <tr>
                    <td><strong>question_text</strong></td>
                    <td>The question text</td>
                    <td>Yes</td>
                    <td>What is 2+2?</td>
                </tr>
                <tr>
                    <td><strong>question_image</strong></td>
                    <td>URL of question image (optional)</td>
                    <td>No</td>
                    <td>https://example.com/image.jpg</td>
                </tr>
                <tr>
                    <td><strong>option_a</strong></td>
                    <td>Option A (required for multiple_choice)</td>
                    <td>For multiple_choice</td>
                    <td>3</td>
                </tr>
                <tr>
                    <td><strong>option_b</strong></td>
                    <td>Option B (required for multiple_choice)</td>
                    <td>For multiple_choice</td>
                    <td>4</td>
                </tr>
                <tr>
                    <td><strong>option_c</strong></td>
                    <td>Option C (required for multiple_choice)</td>
                    <td>For multiple_choice</td>
                    <td>5</td>
                </tr>
                <tr>
                    <td><strong>option_d</strong></td>
                    <td>Option D (required for multiple_choice)</td>
                    <td>For multiple_choice</td>
                    <td>6</td>
                </tr>
                <tr>
                    <td><strong>correct_answer</strong></td>
                    <td>Correct answer (A/B/C/D for multiple_choice, True/False for true_false, text for short_answer)</td>
                    <td>Yes</td>
                    <td>B</td>
                </tr>
                <tr>
                    <td><strong>question_order</strong></td>
                    <td>Display order (number, lower appears first)</td>
                    <td>No</td>
                    <td>1</td>
                </tr>
            </tbody>
        </table>
        
        <h3 style="margin-top: 20px;">Notes:</h3>
        <ul style="list-style: disc; margin-left: 20px;">
            <li>First row should be the header row with column names</li>
            <li>For <strong>true_false</strong> questions: Only question_text and correct_answer (True/False) are required</li>
            <li>For <strong>short_answer</strong> questions: Only question_text and correct_answer (expected text) are required</li>
            <li>For <strong>multiple_choice</strong> questions: All options (A, B, C, D) and correct_answer (A/B/C/D) are required</li>
        </ul>
    </div>
</div>
