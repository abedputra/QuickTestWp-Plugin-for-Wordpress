<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="qtest-container">
    <div class="qtest-header">
        <?php if ($test->time_limit > 0): ?>
        <div class="qtest-timer-wrapper">
            <div class="qtest-timer">
                <span class="qtest-timer-label">Time Remaining:</span>
                <span class="qtest-timer-display" id="qtest-timer-display">00:00</span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="qtest-progress-bar">
            <div class="qtest-progress-fill" style="width: 0%;"></div>
            <div class="qtest-progress-indicator"></div>
        </div>
    </div>
    
    <div class="qtest-quiz-wrapper">
        <div class="qtest-question-section" id="qtest-question-section">
            <?php foreach ($questions as $index => $question): 
                $question_type = isset($question->question_type) && !empty($question->question_type) ? $question->question_type : 'multiple_choice';
            ?>
                <div class="qtest-question-page <?php echo $index === 0 ? 'active' : ''; ?>" data-question-id="<?php echo $question->id; ?>" data-index="<?php echo $index; ?>" data-question-type="<?php echo esc_attr($question_type); ?>">
                    <div class="qtest-question-box">
                        <?php if ($question->question_image): ?>
                            <div class="qtest-question-image">
                                <img data-src="<?php echo esc_url($question->question_image); ?>" alt="Question Image" class="qtest-lazy-image" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="qtest-question-text">
                            <?php echo esc_html($question->question_text); ?>
                        </div>
                    </div>
                    
                    <div class="qtest-answers-box" data-question-type="<?php echo esc_attr($question_type); ?>">
                        <?php if ($question_type === 'multiple_choice'): ?>
                            <div class="qtest-answer-grid">
                                <div class="qtest-answer-option" data-option="A">
                                    <div class="qtest-answer-label">A</div>
                                    <div class="qtest-answer-box">
                                        <?php echo esc_html($question->option_a); ?>
                                    </div>
                                </div>
                                <div class="qtest-answer-option" data-option="B">
                                    <div class="qtest-answer-label">B</div>
                                    <div class="qtest-answer-box">
                                        <?php echo esc_html($question->option_b); ?>
                                    </div>
                                </div>
                                <div class="qtest-answer-option" data-option="C">
                                    <div class="qtest-answer-label">C</div>
                                    <div class="qtest-answer-box">
                                        <?php echo esc_html($question->option_c); ?>
                                    </div>
                                </div>
                                <div class="qtest-answer-option" data-option="D">
                                    <div class="qtest-answer-label">D</div>
                                    <div class="qtest-answer-box">
                                        <?php echo esc_html($question->option_d); ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($question_type === 'true_false'): ?>
                            <div class="qtest-answer-grid qtest-true-false">
                                <div class="qtest-answer-option" data-option="True">
                                    <div class="qtest-answer-label">True</div>
                                    <div class="qtest-answer-box">True</div>
                                </div>
                                <div class="qtest-answer-option" data-option="False">
                                    <div class="qtest-answer-label">False</div>
                                    <div class="qtest-answer-box">False</div>
                                </div>
                            </div>
                        <?php elseif ($question_type === 'short_answer'): ?>
                            <div class="qtest-short-answer-container">
                                <input type="text" class="qtest-short-answer-input" placeholder="Type your answer here..." data-question-id="<?php echo $question->id; ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="qtest-navigation">
            <button type="button" class="qtest-btn qtest-btn-back" id="qtest-back-btn" disabled>Back</button>
            <button type="button" class="qtest-btn qtest-btn-next" id="qtest-next-btn">Next</button>
            <button type="button" class="qtest-btn qtest-btn-review" id="qtest-review-btn" style="display: none;">Review Answers</button>
            <button type="button" class="qtest-btn qtest-btn-submit" id="qtest-submit-btn" style="display: none;">Submit Test</button>
        </div>
    </div>
    
    <div class="qtest-review-section" id="qtest-review-section" style="display: none;">
        <h2>Review Your Answers</h2>
        <p>Please review your answers before submitting. Click on any question to go back and change your answer.</p>
        <div class="qtest-review-list" id="qtest-review-list">
            <!-- Review items will be generated by JavaScript -->
        </div>
        <div class="qtest-review-navigation">
            <button type="button" class="qtest-btn qtest-btn-back-to-questions" id="qtest-back-to-questions-btn">Back to Questions</button>
            <button type="button" class="qtest-btn qtest-btn-primary" id="qtest-final-submit-btn">Submit Test</button>
        </div>
    </div>
    
    <div class="qtest-completion-form" id="qtest-completion-form" style="display: none;">
        <h2>Complete Your Test</h2>
        <p id="qtest-completion-message">Please provide your information to receive your results:</p>
        <form id="qtest-result-form">
            <input type="hidden" id="qtest_test_id" value="<?php echo $test->id; ?>">
            <input type="hidden" id="qtest_nonce" value="<?php echo wp_create_nonce('qtest_nonce'); ?>">
            <input type="hidden" id="qtest_answers" name="answers">
            <input type="hidden" id="qtest_time_started" name="time_started">
            <input type="hidden" id="qtest_time_completed" name="time_completed">
            <input type="hidden" id="qtest_time_taken" name="time_taken">
            
            <div class="qtest-form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="qtest-form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="qtest-form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit" class="qtest-btn qtest-btn-primary">Submit and View Results</button>
        </form>
    </div>
    
    <div class="qtest-result-display" id="qtest-result-display" style="display: none;">
        <h2>Your Results</h2>
        <div class="qtest-score">
            <p>Score: <span id="qtest-score-value"></span> out of <span id="qtest-total-value"></span></p>
            <p>Percentage: <span id="qtest-percentage-value"></span>%</p>
        </div>
        <p>Your results have been sent to your email.</p>
    </div>
</div>

<script>
var qtestData = {
    testId: <?php echo $test->id; ?>,
    totalQuestions: <?php echo count($questions); ?>,
    questions: <?php echo json_encode($questions); ?>,
    timeLimit: <?php echo intval($test->time_limit); ?>,
    timeLimitSeconds: <?php echo intval($test->time_limit) * 60; ?>,
    sequenceInfo: <?php echo $sequence_info ? json_encode($sequence_info) : 'null'; ?>
};
</script>
