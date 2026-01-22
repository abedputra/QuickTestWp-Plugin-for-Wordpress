<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="qtest-container">
    <div class="qtest-result-lookup">
        <h2>View Your Test Results</h2>
        <p>Enter your email address and test ID to view your results:</p>
        
        <form id="qtest-lookup-form">
            <input type="hidden" id="quicktestwp_nonce" value="<?php echo esc_attr(wp_create_nonce('quicktestwp_nonce')); ?>">
            
            <div class="qtest-form-group">
                <label for="lookup_email">Email</label>
                <input type="email" id="lookup_email" name="email" required>
            </div>
            
            <div class="qtest-form-group">
                <label for="lookup_test_id">Test ID</label>
                <input type="number" id="lookup_test_id" name="test_id" required>
            </div>
            
            <button type="submit" class="qtest-btn qtest-btn-primary">View Results</button>
        </form>
        
        <div id="qtest-lookup-result" style="display: none;">
            <h3>Your Results</h3>
            <div class="qtest-score">
                <p>Score: <span id="lookup-score"></span> out of <span id="lookup-total"></span></p>
                <p>Percentage: <span id="lookup-percentage"></span>%</p>
            </div>
            <div id="lookup-details"></div>
        </div>
    </div>
</div>
