jQuery(document).ready(function ($) {
    'use strict';

    // Quiz functionality (only if qtestData is defined)
    if (typeof qtestData !== 'undefined') {
        let currentQuestion = 0;
        let answers = {};
        const totalQuestions = qtestData.totalQuestions;
        let timeRemaining = qtestData.timeLimitSeconds || 0;
        let timerInterval = null;
        let timeStarted = null;
        let questionStartTimes = {}; // Track when user starts each question
        let questionTimes = {}; // Track time spent on each question
        let averageTimes = {}; // Store average times from server
        let questionTimeIntervals = {}; // Track intervals for each question
        let qtestUiStage = 'quiz'; // quiz | review | completion | result

        // Load average times from server
        $.ajax({
            url: qtestAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'qtest_get_average_times',
                nonce: qtestAjax.nonce,
                test_id: qtestData.testId
            },
            success: function (response) {
                if (response.success && response.data.averages) {
                    averageTimes = response.data.averages;
                }
            }
        });

        // Initialize timer if time limit is set
        if (qtestData.timeLimit > 0 && timeRemaining > 0) {
            timeStarted = new Date().toISOString();
            $('#qtest_time_started').val(timeStarted);
            startTimer();
        }

        // Initialize
        updateProgress();
        updateNavigation();

        // Initialize lazy loading for images
        initLazyLoading();

        // Track start time for first question
        const firstQuestionPage = $('.qtest-question-page.active');
        const firstQuestionId = firstQuestionPage.data('question-id');
        if (firstQuestionId) {
            trackQuestionStart(firstQuestionId);
        }

        // Lazy loading function
        function initLazyLoading() {
            // Check if Intersection Observer is supported
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            const dataSrc = img.getAttribute('data-src');
                            if (dataSrc) {
                                img.src = dataSrc;
                                img.removeAttribute('data-src');
                                img.classList.remove('qtest-lazy-image');
                                img.classList.add('qtest-loaded-image');
                                observer.unobserve(img);
                            }
                        }
                    });
                }, {
                    rootMargin: '50px' // Start loading 50px before image enters viewport
                });

                // Observe all lazy images
                document.querySelectorAll('.qtest-lazy-image').forEach(function (img) {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for browsers that don't support Intersection Observer
                // Load all images immediately
                document.querySelectorAll('.qtest-lazy-image').forEach(function (img) {
                    const dataSrc = img.getAttribute('data-src');
                    if (dataSrc) {
                        img.src = dataSrc;
                        img.removeAttribute('data-src');
                        img.classList.remove('qtest-lazy-image');
                        img.classList.add('qtest-loaded-image');
                    }
                });
            }
        }

        // Initialize security (anti-cheat) - delay to ensure DOM is ready
        setTimeout(function () {
            if (typeof QTestSecurity !== 'undefined' && $('.qtest-question-page.active').length > 0) {
                QTestSecurity.initSecurity();
                $(document).trigger('qtest:testStarted');
            }
        }, 300);

        // Handle force submit from security (tab switching too many times)
        $(document).on('qtest:forceSubmit', function () {
            // For sequences with next test, auto-submit instead of showing form
            // Also check that next_test.test_id is different from current test_id to prevent loops
            const currentTestId = parseInt($('#qtest_test_id').val(), 10);
            const nextTestId = qtestData.sequenceInfo && qtestData.sequenceInfo.next_test ? parseInt(qtestData.sequenceInfo.next_test.test_id, 10) : null;
            
            if (qtestData.sequenceInfo && qtestData.sequenceInfo.next_test && nextTestId && nextTestId !== currentTestId && nextTestId > 0) {
                submitTest();
                return;
            }

            // Show completion form immediately
            if (typeof showCompletionForm === 'function') {
                showCompletionForm();
            } else {
                $('.qtest-quiz-wrapper').hide();
                $('#qtest-completion-form').show();
                $('#qtest_answers').val(JSON.stringify(answers));
                $('#qtest-completion-message').html('<strong style="color: #d93025;">⚠️ Warning:</strong> You switched tabs/windows too many times. Please provide your information to submit your test:');
            }
        });

        // Track question start time when question is shown
        function trackQuestionStart(questionId) {
            questionStartTimes[questionId] = Date.now();
        }

        // Track question end time and compare with average
        function trackQuestionEnd(questionId) {
            if (questionStartTimes[questionId]) {
                // Calculate time spent with decimal precision (in seconds)
                const timeSpent = parseFloat(((Date.now() - questionStartTimes[questionId]) / 1000).toFixed(2));
                questionTimes[questionId] = timeSpent;

                // Compare with average if available
                if (qtestUiStage === 'quiz' && averageTimes[questionId] && timeSpent < averageTimes[questionId]) {
                    // Calculate faster by with 2 decimal precision
                    const fasterBy = parseFloat((averageTimes[questionId] - timeSpent).toFixed(2));
                    showSpeedMessage(fasterBy, averageTimes[questionId], timeSpent);
                }
            }
        }

        // Show popup message when user is faster than average
        function showSpeedMessage(fasterBy, averageTime, userTime) {
            // Format time with 2 decimal places
            const formatTime = function(totalSeconds) {
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = parseFloat((totalSeconds % 60).toFixed(2));
                
                if (minutes > 0) {
                    return minutes + 'm ' + seconds + 's';
                } else {
                    return seconds + 's';
                }
            };

            const averageFormatted = formatTime(averageTime);
            const userFormatted = formatTime(userTime);
            
            // Calculate faster by with 2 decimal places
            const fasterByFormatted = parseFloat(fasterBy.toFixed(2));

            let message = 'Great job! You answered this question faster than average.\n\n';
            message += 'Your time: ' + userFormatted + '\n';
            message += 'Average time: ' + averageFormatted + '\n';
            message += 'You were ' + fasterByFormatted + ' second' + (fasterByFormatted > 1 ? 's' : '') + ' faster!';

            // Show as a nice popup (you can customize this)
            const popup = $('<div class="qtest-speed-popup">' +
                '<div class="qtest-speed-popup-content">' +
                '<h3>⚡ Great Speed!</h3>' +
                '<p>' + message.replace(/\n/g, '<br>') + '</p>' +
                '<button class="qtest-popup-close">OK</button>' +
                '</div>' +
                '</div>');

            $('body').append(popup);

            setTimeout(function () {
                popup.addClass('show');
            }, 100);

            popup.find('.qtest-popup-close, .qtest-speed-popup').on('click', function (e) {
                if (e.target === this) {
                    popup.removeClass('show');
                    setTimeout(function () {
                        popup.remove();
                    }, 300);
                }
            });
        }

        // Answer selection for multiple choice and true/false
        $(document).on('click', '.qtest-answer-option', function () {
            const questionPage = $(this).closest('.qtest-question-page');
            const questionId = questionPage.data('question-id');
            const option = $(this).data('option');

            // Track question end time when answer is selected
            trackQuestionEnd(questionId);

            // Remove selected class from all options in this question
            questionPage.find('.qtest-answer-option').removeClass('selected');

            // Add selected class to clicked option
            $(this).addClass('selected');

            // Save answer
            answers[questionId] = option;

            // Enable next button
            $('#qtest-next-btn').prop('disabled', false);
        });

        // Handle short answer input
        $(document).on('input', '.qtest-short-answer-input', function () {
            const questionPage = $(this).closest('.qtest-question-page');
            const questionId = questionPage.data('question-id');
            const answer = $(this).val().trim();

            if (answer) {
                answers[questionId] = answer;
                $('#qtest-next-btn').prop('disabled', false);
            } else {
                delete answers[questionId];
                $('#qtest-next-btn').prop('disabled', true);
            }
        });

        // Back button
        $('#qtest-back-btn').on('click', function () {
            if (currentQuestion > 0) {
                currentQuestion--;
                showQuestion(currentQuestion);
                updateProgress();
                updateNavigation();
            }
        });

        // Next button
        $('#qtest-next-btn').on('click', function () {
            const questionPage = $('.qtest-question-page.active');
            const questionId = questionPage.data('question-id');

            // Check if answer is selected
            if (!answers[questionId]) {
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Please select an answer before proceeding.');
                } else {
                    alert('Please select an answer before proceeding.');
                }
                return;
            }

            if (currentQuestion < totalQuestions - 1) {
                currentQuestion++;
                showQuestion(currentQuestion);
                updateProgress();
                updateNavigation();
            }
        });

        // Submit button
        // Review button
        $('#qtest-review-btn').on('click', function () {
            showReviewPage();
        });

        // Back to questions from review
        $('#qtest-back-to-questions-btn').on('click', function () {
            $('.qtest-quiz-wrapper').show();
            $('#qtest-review-section').hide();
        });

        // Final submit from review
        $('#qtest-final-submit-btn').on('click', function () {
            submitTest();
        });

        // Show review page
        function showReviewPage() {
            const reviewList = $('#qtest-review-list');
            reviewList.empty();

            qtestData.questions.forEach(function (question, index) {
                const questionId = question.id;
                const userAnswer = answers[questionId] || 'Not answered';
                const questionType = question.question_type || 'multiple_choice';

                let answerDisplay = userAnswer;
                if (questionType === 'multiple_choice') {
                    const optionMap = {
                        'A': question.option_a,
                        'B': question.option_b,
                        'C': question.option_c,
                        'D': question.option_d
                    };
                    answerDisplay = optionMap[userAnswer] || userAnswer;
                }

                const reviewItem = $('<div class="qtest-review-item" data-question-index="' + index + '">' +
                    '<div class="qtest-review-question-number">Question ' + (index + 1) + '</div>' +
                    '<div class="qtest-review-question-text">' + question.question_text + '</div>' +
                    '<div class="qtest-review-answer">Your Answer: <strong>' + answerDisplay + '</strong></div>' +
                    '</div>');

                reviewItem.on('click', function () {
                    const questionIndex = $(this).data('question-index');
                    $('.qtest-quiz-wrapper').show();
                    $('#qtest-review-section').hide();
                    currentQuestion = questionIndex;
                    showQuestion(questionIndex);
                });

                reviewList.append(reviewItem);
            });

            $('.qtest-quiz-wrapper').hide();
            $('#qtest-review-section').show();
            qtestUiStage = 'review';
        }

        // Submit test function
        function submitTest() {
            // Stop timer
            if (timerInterval) {
                clearInterval(timerInterval);
            }

            // For sequences: if there's a next test, auto-submit without showing user info form
            // Also check that next_test.test_id is different from current test_id to prevent loops
            const currentTestId = parseInt($('#qtest_test_id').val(), 10);
            const nextTestId = qtestData.sequenceInfo && qtestData.sequenceInfo.next_test ? parseInt(qtestData.sequenceInfo.next_test.test_id, 10) : null;
            
            if (qtestData.sequenceInfo && qtestData.sequenceInfo.next_test && nextTestId && nextTestId !== currentTestId && nextTestId > 0) {
                // Auto-submit with placeholder data (user info will be collected at the end)
                const timeCompleted = new Date().toISOString();
                let timeTaken = 0;
                if (timeStarted && qtestData.timeLimit > 0) {
                    const startTime = new Date(timeStarted);
                    const endTime = new Date(timeCompleted);
                    timeTaken = Math.floor((endTime - startTime) / 1000);
                }

                const formData = {
                    action: 'qtest_submit_result',
                    nonce: $('#qtest_nonce').val(),
                    test_id: $('#qtest_test_id').val(),
                    first_name: 'SEQUENCE_IN_PROGRESS', // Placeholder - will be replaced at final submission
                    last_name: 'SEQUENCE_IN_PROGRESS',
                    email: 'sequence@placeholder.local',
                    answers: answers,
                    time_started: timeStarted || null,
                    time_completed: timeCompleted,
                    time_taken: timeTaken,
                    question_times: questionTimes,
                    sequence_mode: 'true' // Flag to indicate this is a sequence test
                };

                $.ajax({
                    url: qtestAjax.ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            // Mark test as completed (disable security)
                            if (typeof QTestSecurity !== 'undefined') {
                                QTestSecurity.markTestCompleted();
                                $(document).trigger('qtest:testCompleted');
                            }

                            // Clear session storage
                            const testSessionKey = 'qtest_session_' + qtestData.testId;
                            sessionStorage.removeItem(testSessionKey);

                            // Continue to next test
                            const nextTest = qtestData.sequenceInfo.next_test;
                            const autoContinue = nextTest.auto_continue == 1;

                            // Remove beforeunload handler before navigation (Bug 3 fix)
                            if (typeof QTestSecurity !== 'undefined') {
                                QTestSecurity.removeRefreshPrevention();
                            }

                            // Remove beforeunload handler BEFORE navigation (Bug 3 fix - must be done synchronously)
                            if (typeof QTestSecurity !== 'undefined') {
                                QTestSecurity.removeRefreshPrevention();
                            }
                            
                            if (autoContinue) {
                                // Auto continue to next test
                                if (typeof QTestPopup !== 'undefined') {
                                    QTestPopup.success('Test completed! Loading next test...', function () {
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('test_id', nextTest.test_id);
                                        url.searchParams.set('sequence_id', qtestData.sequenceInfo.sequence_id);
                                        window.location.href = url.toString();
                                    });
                                } else {
                                    // Fallback: direct redirect
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('test_id', nextTest.test_id);
                                    url.searchParams.set('sequence_id', qtestData.sequenceInfo.sequence_id);
                                    window.location.href = url.toString();
                                }
                            } else {
                                // Show confirmation popup
                                if (typeof QTestPopup !== 'undefined') {
                                    QTestPopup.confirm(
                                        'Test completed! Do you want to continue to the next test?',
                                        function (confirmed) {
                                            if (confirmed) {
                                                // Remove beforeunload handler before navigation (Bug 3 fix)
                                                if (typeof QTestSecurity !== 'undefined') {
                                                    QTestSecurity.removeRefreshPrevention();
                                                }
                                                const url = new URL(window.location.href);
                                                url.searchParams.set('test_id', nextTest.test_id);
                                                url.searchParams.set('sequence_id', qtestData.sequenceInfo.sequence_id);
                                                window.location.href = url.toString();
                                            } else {
                                                // User declined - show completion form for final submission
                                                showCompletionForm();
                                            }
                                        }
                                    );
                                } else {
                                    showCompletionForm();
                                }
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        // On error, show error message instead of form for sequences
                        if (typeof QTestPopup !== 'undefined') {
                            QTestPopup.error('Failed to submit test. Please try again.');
                        } else {
                            alert('Failed to submit test. Please try again.');
                        }
                        // Don't show completion form for intermediate sequence tests on error
                        // User can retry by clicking submit again
                    }
                });
                return; // Don't show completion form for intermediate tests
            }

            // Show completion form (only for single tests or final test in sequence)
            $('.qtest-quiz-wrapper').hide();
            $('#qtest-review-section').hide();
            $('#qtest-completion-form').show();
            $('#qtest_answers').val(JSON.stringify(answers));
            qtestUiStage = 'completion';
        }

        // Original submit button handler (for direct submit without review)
        $('#qtest-submit-btn').on('click', function () {
            const questionPage = $('.qtest-question-page.active');
            const questionId = questionPage.data('question-id');

            // Check if answer is selected
            if (!answers[questionId]) {
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Please select an answer before submitting.');
                } else {
                    alert('Please select an answer before submitting.');
                }
                return;
            }

            submitTest();
        });

        // Timer functions
        function startTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }

            updateTimerDisplay();

            timerInterval = setInterval(function () {
                timeRemaining--;
                updateTimerDisplay();

                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    timerExpired();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            if (timeRemaining <= 0) {
                $('#qtest-timer-display').text('00:00').addClass('qtest-timer-expired');
                return;
            }

            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const display = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            $('#qtest-timer-display').text(display);

            // Change color when time is running low
            if (timeRemaining <= 60) {
                $('#qtest-timer-display').addClass('qtest-timer-warning');
            } else {
                $('#qtest-timer-display').removeClass('qtest-timer-warning');
            }
        }

        function timerExpired() {
            // For sequences with next test, auto-submit instead of showing form
            // Also check that next_test.test_id is different from current test_id to prevent loops
            const currentTestId = parseInt($('#qtest_test_id').val(), 10);
            const nextTestId = qtestData.sequenceInfo && qtestData.sequenceInfo.next_test ? parseInt(qtestData.sequenceInfo.next_test.test_id, 10) : null;
            
            if (qtestData.sequenceInfo && qtestData.sequenceInfo.next_test && nextTestId && nextTestId !== currentTestId && nextTestId > 0) {
                submitTest();
                return;
            }

            // Show popup first, then automatically show completion form
            if (typeof QTestPopup !== 'undefined') {
                QTestPopup.warning('Time is up! Please complete your information below to submit your test.', function () {
                    // After popup closed, show completion form
                    showCompletionForm();
                });
                // Also show form immediately (in case user closes popup quickly)
                setTimeout(function () {
                    if ($('#qtest-completion-form').is(':hidden')) {
                        showCompletionForm();
                    }
                }, 500);
            } else {
                alert('Time is up! Please complete your information below to submit your test.');
                showCompletionForm();
            }
        }

        function showCompletionForm() {
            // Bug 1 fix: For sequences with next test, auto-submit instead of showing form
            // Also check that next_test.test_id is different from current test_id to prevent loops
            const currentTestId = parseInt($('#qtest_test_id').val(), 10);
            const nextTestId = qtestData.sequenceInfo && qtestData.sequenceInfo.next_test ? parseInt(qtestData.sequenceInfo.next_test.test_id, 10) : null;
            
            if (qtestData.sequenceInfo && qtestData.sequenceInfo.next_test && nextTestId && nextTestId !== currentTestId && nextTestId > 0) {
                // Call submitTest() which handles sequence auto-submit
                submitTest();
                return; // Don't show form for intermediate tests
            }

            // Stop timer if still running
            if (timerInterval) {
                clearInterval(timerInterval);
            }

            // Set stage to completion BEFORE tracking (Bug 4 fix: prevent speed popup during completion)
            qtestUiStage = 'completion';

            // Track time for current question (only if still in quiz stage)
            const currentQuestionPage = $('.qtest-question-page.active');
            const currentQuestionId = currentQuestionPage.data('question-id');
            // Don't track question end here - we're already in completion stage

            // Calculate time taken
            const timeCompleted = new Date().toISOString();
            let timeTaken = 0;
            if (timeStarted && qtestData.timeLimit > 0) {
                const startTime = new Date(timeStarted);
                const endTime = new Date(timeCompleted);
                timeTaken = Math.floor((endTime - startTime) / 1000); // in seconds
            }

            // Set time values in hidden fields
            $('#qtest_time_started').val(timeStarted || '');
            $('#qtest_time_completed').val(timeCompleted);
            $('#qtest_time_taken').val(timeTaken);

            // Update completion message if time expired
            if (timeRemaining <= 0) {
                $('#qtest-completion-message').html('<strong style="color: #d93025;">⚠️ Time is up!</strong> Please provide your information to submit your test:');
            }

            // Hide quiz wrapper and show completion form
            $('.qtest-quiz-wrapper').hide();
            $('#qtest-completion-form').show();
            $('#qtest_answers').val(JSON.stringify(answers));

            // Scroll to form smoothly
            setTimeout(function () {
                $('html, body').animate({
                    scrollTop: $('#qtest-completion-form').offset().top - 20
                }, 500);
            }, 100);
        }


        // Submit result form
        $('#qtest-result-form').on('submit', function (e) {
            e.preventDefault();
            qtestUiStage = 'completion';

            // Stop timer if still running
            if (timerInterval) {
                clearInterval(timerInterval);
            }

            // Calculate time taken
            const timeCompleted = new Date().toISOString();
            let timeTaken = 0;
            if (timeStarted && qtestData.timeLimit > 0) {
                const startTime = new Date(timeStarted);
                const endTime = new Date(timeCompleted);
                timeTaken = Math.floor((endTime - startTime) / 1000); // in seconds
            }

            // Don't track question end here - we're in completion stage (Bug 4 fix)

            const formData = {
                action: 'qtest_submit_result',
                nonce: $('#qtest_nonce').val(),
                test_id: $('#qtest_test_id').val(),
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                email: $('#email').val(),
                answers: answers,
                    time_started: timeStarted || null,
                    time_completed: timeCompleted,
                    time_taken: timeTaken,
                    question_times: questionTimes
                };

            $.ajax({
                url: qtestAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Mark test as completed (disable security)
                        if (typeof QTestSecurity !== 'undefined') {
                            QTestSecurity.markTestCompleted();
                            $(document).trigger('qtest:testCompleted');
                        }

                        // Clear session storage
                        const testSessionKey = 'qtest_session_' + qtestData.testId;
                        sessionStorage.removeItem(testSessionKey);

                        // Check if there's a next test in sequence (Bug 2 fix: ensure next_test is valid and not null)
                        // Also check that next_test.test_id is different from current test_id to prevent loops
                        const currentTestId = parseInt($('#qtest_test_id').val(), 10);
                        const hasSequenceInfo = qtestData.sequenceInfo && qtestData.sequenceInfo.sequence_id;
                        const nextTestObj = qtestData.sequenceInfo && qtestData.sequenceInfo.next_test ? qtestData.sequenceInfo.next_test : null;
                        const nextTestId = nextTestObj && nextTestObj.test_id ? parseInt(nextTestObj.test_id, 10) : null;
                        
                        // Only show next test popup if:
                        // 1. We're in a sequence
                        // 2. next_test exists and is not null
                        // 3. next_test.test_id is valid and different from current test
                        // 4. next_test.test_id is a positive number
                        if (hasSequenceInfo && nextTestObj && nextTestId && nextTestId !== currentTestId && nextTestId > 0 && !isNaN(nextTestId)) {
                            const nextTest = qtestData.sequenceInfo.next_test;
                            const autoContinue = nextTest.auto_continue == 1;

                            // Remove beforeunload handler BEFORE navigation (Bug 3 fix - must be done synchronously)
                            if (typeof QTestSecurity !== 'undefined') {
                                QTestSecurity.removeRefreshPrevention();
                            }
                            
                            if (autoContinue) {
                                // Auto continue to next test
                                if (typeof QTestPopup !== 'undefined') {
                                    QTestPopup.success('Test completed! Loading next test...', function () {
                                        // Reload page with next test
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('test_id', nextTest.test_id);
                                        url.searchParams.set('sequence_id', qtestData.sequenceInfo.sequence_id);
                                        window.location.href = url.toString();
                                    });
                                } else {
                                    // Fallback: direct redirect
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('test_id', nextTest.test_id);
                                    url.searchParams.set('sequence_id', qtestData.sequenceInfo.sequence_id);
                                    window.location.href = url.toString();
                                }
                            } else {
                                // Show confirmation popup
                                if (typeof QTestPopup !== 'undefined') {
                                    QTestPopup.confirm(
                                        'Test completed! Do you want to continue to the next test?',
                                        function (confirmed) {
                                            if (confirmed) {
                                                // Remove beforeunload handler before navigation (Bug 3 fix)
                                                if (typeof QTestSecurity !== 'undefined') {
                                                    QTestSecurity.removeRefreshPrevention();
                                                }
                                                const url = new URL(window.location.href);
                                                url.searchParams.set('test_id', nextTest.test_id);
                                                url.searchParams.set('sequence_id', qtestData.sequenceInfo.sequence_id);
                                                window.location.href = url.toString();
                                            } else {
                                                // Show results
                                                $('#qtest-completion-form').hide();
                                                $('#qtest-score-value').text(response.data.score);
                                                $('#qtest-total-value').text(response.data.total);
                                                const percentage = ((response.data.score / response.data.total) * 100).toFixed(2);
                                                $('#qtest-percentage-value').text(percentage);
                                                $('#qtest-result-display').show();
                                            }
                                        }
                                    );
                                } else {
                                    // Fallback: show results
                                    $('#qtest-completion-form').hide();
                                    $('#qtest-score-value').text(response.data.score);
                                    $('#qtest-total-value').text(response.data.total);
                                    const percentage = ((response.data.score / response.data.total) * 100).toFixed(2);
                                    $('#qtest-percentage-value').text(percentage);
                                    $('#qtest-result-display').show();
                                }
                                return; // Don't show results yet if confirmation needed
                            }
                        } else {
                            // No next test, show results
                            $('#qtest-completion-form').hide();
                            $('#qtest-score-value').text(response.data.score);
                            $('#qtest-total-value').text(response.data.total);
                            const percentage = ((response.data.score / response.data.total) * 100).toFixed(2);
                            $('#qtest-percentage-value').text(percentage);
                            $('#qtest-result-display').show();
                        }
                    } else {
                        if (typeof QTestPopup !== 'undefined') {
                            QTestPopup.error(response.data.message || 'An error occurred. Please try again.');
                        } else {
                            alert(response.data.message || 'An error occurred. Please try again.');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    if (typeof QTestPopup !== 'undefined') {
                        QTestPopup.error('An error occurred. Please try again. Error: ' + error);
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });


        function showQuestion(index) {
            // Track end time for previous question
            if (currentQuestion >= 0) {
                const prevQuestionPage = $('.qtest-question-page').eq(currentQuestion);
                const prevQuestionId = prevQuestionPage.data('question-id');
                if (prevQuestionId && questionStartTimes[prevQuestionId]) {
                    trackQuestionEnd(prevQuestionId);
                }
            }

            $('.qtest-question-page').removeClass('active');
            $('.qtest-question-page').eq(index).addClass('active');

            // Restore selected answer if exists
            const questionPage = $('.qtest-question-page').eq(index);
            const questionId = questionPage.data('question-id');

            // Track start time for new question
            if (questionId) {
                trackQuestionStart(questionId);
            }

            // Re-initialize lazy loading for newly visible images
            initLazyLoading();

            // Restore answer based on question type
            const questionType = questionPage.data('question-type') || 'multiple_choice';

            if (answers[questionId]) {
                if (questionType === 'multiple_choice' || questionType === 'true_false') {
                    questionPage.find('.qtest-answer-option').removeClass('selected');
                    questionPage.find('.qtest-answer-option[data-option="' + answers[questionId] + '"]').addClass('selected');
                } else if (questionType === 'short_answer') {
                    questionPage.find('.qtest-short-answer-input').val(answers[questionId]);
                }
            } else {
                if (questionType === 'multiple_choice' || questionType === 'true_false') {
                    questionPage.find('.qtest-answer-option').removeClass('selected');
                } else if (questionType === 'short_answer') {
                    questionPage.find('.qtest-short-answer-input').val('');
                }
            }
        }

        function updateProgress() {
            const progress = ((currentQuestion + 1) / totalQuestions) * 100;
            $('.qtest-progress-fill').css('width', progress + '%');
            $('.qtest-progress-indicator').css('left', progress + '%');
        }

        function updateNavigation() {
            // Back button
            if (currentQuestion === 0) {
                $('#qtest-back-btn').prop('disabled', true);
            } else {
                $('#qtest-back-btn').prop('disabled', false);
            }

            // Next/Review button
            if (currentQuestion === totalQuestions - 1) {
                $('#qtest-next-btn').hide();
                $('#qtest-review-btn').show();
                $('#qtest-submit-btn').hide();
            } else {
                $('#qtest-next-btn').show();
                $('#qtest-review-btn').hide();
                $('#qtest-submit-btn').hide();
            }

            // Enable next if answer already selected
            const questionPage = $('.qtest-question-page.active');
            const questionId = questionPage.data('question-id');
            const questionType = questionPage.data('question-type') || 'multiple_choice';

            if (answers[questionId]) {
                // For short answer, check if input has value
                if (questionType === 'short_answer') {
                    const inputValue = questionPage.find('.qtest-short-answer-input').val().trim();
                    $('#qtest-next-btn').prop('disabled', !inputValue);
                } else {
                    $('#qtest-next-btn').prop('disabled', false);
                }
            } else {
                $('#qtest-next-btn').prop('disabled', true);
            }
        }
    } // End of qtestData check

    // Result lookup functionality (always available)
    $('#qtest-lookup-form').on('submit', function (e) {
        e.preventDefault();

        const formData = {
            action: 'qtest_get_result',
            nonce: $('#qtest_nonce').val(),
            test_id: $('#lookup_test_id').val(),
            email: $('#lookup_email').val()
        };

        $.ajax({
            url: qtestAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    const result = response.data.result;
                    const questions = response.data.questions;
                    const userAnswers = response.data.answers;

                    $('#lookup-score').text(result.score);
                    $('#lookup-total').text(result.total_questions);
                    const percentage = ((result.score / result.total_questions) * 100).toFixed(2);
                    $('#lookup-percentage').text(percentage);

                    let detailsHtml = '<h4>Detailed Results:</h4><ul>';
                    questions.forEach(function (question) {
                        const userAnswer = userAnswers[question.id] || 'Not answered';
                        const isCorrect = userAnswer.toUpperCase() === question.correct_answer;
                        detailsHtml += '<li>';
                        detailsHtml += '<strong>Question:</strong> ' + question.question_text + '<br>';
                        detailsHtml += '<strong>Your Answer:</strong> ' + userAnswer + ' (' + (isCorrect ? 'Correct' : 'Incorrect') + ')<br>';
                        detailsHtml += '<strong>Correct Answer:</strong> ' + question.correct_answer;
                        detailsHtml += '</li>';
                    });
                    detailsHtml += '</ul>';

                    $('#lookup-details').html(detailsHtml);
                    $('#qtest-lookup-result').show();
                } else {
                    if (typeof QTestPopup !== 'undefined') {
                        QTestPopup.error(response.data.message || 'No result found.');
                    } else {
                        alert(response.data.message || 'No result found.');
                    }
                }
            },
            error: function () {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
