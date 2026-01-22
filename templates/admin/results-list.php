<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get test filter
$selected_test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : null;
?>
<div class="wrap">
    <input type="hidden" id="quicktestwp_nonce" value="<?php echo esc_attr(wp_create_nonce('quicktestwp_nonce')); ?>">
    <h1 class="wp-heading-inline">QuickTestWP - Test Results</h1>
    
    <hr class="wp-header-end">
    
    <div class="qtest-results-filter" style="margin: 20px 0;">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="qtest-results">
            <label for="filter_test_id">
                <strong>Filter by Test:</strong>
                <select name="test_id" id="filter_test_id" style="margin-left: 10px;">
                    <option value="">All Tests</option>
                    <?php foreach ($tests as $test): ?>
                        <option value="<?php echo esc_attr($test->id); ?>" <?php selected($selected_test_id, $test->id); ?>>
                            <?php echo esc_html($test->title); ?> (ID: <?php echo esc_html($test->id); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <input type="submit" class="button" value="Filter" style="margin-left: 10px;">
            <?php if ($selected_test_id): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=quicktestwp-results')); ?>" class="button">Clear Filter</a>
            <?php endif; ?>
        </form>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Test</th>
                <th>Score</th>
                <th>Time Taken</th>
                <th>Completed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="8">No results found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($results as $result): 
                    $test = QuickTestWP_Database::get_test($result->test_id);
                    $percentage = $result->total_questions > 0 ? round(($result->score / $result->total_questions) * 100, 2) : 0;
                    $time_taken_minutes = $result->time_taken > 0 ? round($result->time_taken / 60, 2) : 0;
                ?>
                    <tr>
                        <td><?php echo esc_html($result->id); ?></td>
                        <td><strong><?php echo esc_html($result->first_name . ' ' . $result->last_name); ?></strong></td>
                        <td><?php echo esc_html($result->email); ?></td>
                        <td>
                            <?php if ($test): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=quicktestwp-new&test_id=' . $test->id)); ?>">
                                    <?php echo esc_html($test->title); ?>
                                </a>
                            <?php else: ?>
                                Test ID: <?php echo esc_html($result->test_id); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($result->score); ?></strong> / <?php echo esc_html($result->total_questions); ?>
                            <br>
                            <small style="color: #666;"><?php echo esc_html($percentage); ?>%</small>
                        </td>
                        <td>
                            <?php if ($time_taken_minutes > 0): ?>
                                <?php echo esc_html($time_taken_minutes); ?> min
                                <br>
                                <small style="color: #666;"><?php echo esc_html($result->time_taken); ?> sec</small>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($result->completed_at); ?></td>
                        <td>
                            <button type="button" 
                                    class="button button-small qtest-send-email" 
                                    data-result-id="<?php echo esc_attr($result->id); ?>"
                                    data-email="<?php echo esc_attr($result->email); ?>"
                                    title="Send Email">
                                <span class="dashicons dashicons-email-alt"></span>
                            </button>
                            <button type="button" 
                                    class="button button-small qtest-delete-result" 
                                    data-result-id="<?php echo esc_attr($result->id); ?>"
                                    title="Delete Result"
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
