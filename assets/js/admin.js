jQuery(document).ready(function($) {
    'use strict';
    
    let questionIndex = $('.qtest-question-item').length;
    
    // Save test
    $('#qtest-test-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get selected roles
        const allowedRoles = [];
        $('input[name="allowed_roles[]"]:checked').each(function() {
            allowedRoles.push($(this).val());
        });
        
        const formData = {
            action: 'qtest_save_test',
            nonce: $('#qtest_nonce').val(),
            test_id: $('#test_id').val(),
            title: $('#test_title').val(),
            description: $('#test_description').val(),
            time_limit: $('#test_time_limit').val() || 0,
            allowed_roles: JSON.stringify(allowedRoles)
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    QTestPopup.success('Test saved successfully! You can now add questions.', function() {
                        if (formData.test_id == 0) {
                            // Reload page with test_id to show question form
                            window.location.href = 'admin.php?page=qtest-new&test_id=' + response.data.test_id;
                        } else {
                            // Update hidden field
                            $('#test_id').val(response.data.test_id);
                            // Show question section if hidden
                            if ($('#qtest-questions-list').length === 0) {
                                location.reload();
                            }
                        }
                    });
                } else {
                    QTestPopup.error(response.data.message || 'Failed to save test');
                }
            },
            error: function(xhr, status, error) {
                QTestPopup.error('An error occurred. Please try again. Error: ' + error);
            }
        });
    });
    
    // Add question
    $('#qtest-add-question').on('click', function() {
        const template = $('#qtest-question-template').html();
        const newQuestion = $(template.replace(/\{\{INDEX\}\}/g, questionIndex));
        newQuestion.find('[data-question-id]').attr('data-question-id', 0);
        newQuestion.find('[data-index]').attr('data-index', questionIndex);
        newQuestion.find('.question-number').text(questionIndex + 1);
        
        // Update IDs to be unique
        const uniqueId = 'new_' + questionIndex;
        newQuestion.find('textarea, input, select, label').each(function() {
            const $this = $(this);
            const id = $this.attr('id');
            const forAttr = $this.attr('for');
            if (id) {
                // Replace all occurrences of _0 with uniqueId, handling all ID patterns
                let newId = id;
                // Replace specific patterns first
                newId = newId.replace(/question_text_0/g, 'question_text_' + uniqueId);
                newId = newId.replace(/option_a_0/g, 'option_a_' + uniqueId);
                newId = newId.replace(/option_b_0/g, 'option_b_' + uniqueId);
                newId = newId.replace(/option_c_0/g, 'option_c_' + uniqueId);
                newId = newId.replace(/option_d_0/g, 'option_d_' + uniqueId);
                newId = newId.replace(/correct_answer_0/g, 'correct_answer_' + uniqueId);
                newId = newId.replace(/correct_answer_sa_0/g, 'correct_answer_sa_' + uniqueId);
                newId = newId.replace(/correct_answer_tf_0/g, 'correct_answer_tf_' + uniqueId);
                newId = newId.replace(/question_order_0/g, 'question_order_' + uniqueId);
                newId = newId.replace(/question_type_0/g, 'question_type_' + uniqueId);
                // Fallback: replace _0 at the end if not already replaced
                if (newId === id && id.endsWith('_0')) {
                    newId = id.replace(/_0$/, '_' + uniqueId);
                }
                $this.attr('id', newId);
            }
            if (forAttr) {
                // Update 'for' attribute in labels
                let newFor = forAttr;
                newFor = newFor.replace(/question_text_0/g, 'question_text_' + uniqueId);
                newFor = newFor.replace(/option_a_0/g, 'option_a_' + uniqueId);
                newFor = newFor.replace(/option_b_0/g, 'option_b_' + uniqueId);
                newFor = newFor.replace(/option_c_0/g, 'option_c_' + uniqueId);
                newFor = newFor.replace(/option_d_0/g, 'option_d_' + uniqueId);
                newFor = newFor.replace(/correct_answer_0/g, 'correct_answer_' + uniqueId);
                newFor = newFor.replace(/correct_answer_sa_0/g, 'correct_answer_sa_' + uniqueId);
                newFor = newFor.replace(/correct_answer_tf_0/g, 'correct_answer_tf_' + uniqueId);
                newFor = newFor.replace(/question_order_0/g, 'question_order_' + uniqueId);
                newFor = newFor.replace(/question_type_0/g, 'question_type_' + uniqueId);
                if (newFor === forAttr && forAttr.endsWith('_0')) {
                    newFor = forAttr.replace(/_0$/, '_' + uniqueId);
                }
                $this.attr('for', newFor);
            }
        });
        
        $('#qtest-questions-list').append(newQuestion);
        questionIndex++;
        
        // Initialize image upload for new question
        initImageUpload(newQuestion);
        
        // Initialize question type handler for new question
        newQuestion.find('.qtest-question-type').trigger('change');
        
        // Scroll to new question
        $('html, body').animate({
            scrollTop: newQuestion.offset().top - 100
        }, 500);
    });
    
    // Handle question type change
    $(document).on('change', '.qtest-question-type', function() {
        const questionItem = $(this).closest('.qtest-question-item');
        const questionType = $(this).val();
        const optionsContainer = questionItem.find('.qtest-options-container');
        
        // Hide all option containers
        optionsContainer.hide();
        
        // Show relevant option container
        const activeContainer = questionItem.find('.qtest-options-container[data-question-type="' + questionType + '"]');
        activeContainer.show();
        
        // Update required attributes
        if (questionType === 'multiple_choice') {
            questionItem.find('.qtest-option-row input').prop('required', true);
            questionItem.find('.qtest-correct-answer-row select').prop('required', true);
            questionItem.find('.qtest-options-container[data-question-type="true_false"] input, .qtest-options-container[data-question-type="short_answer"] input').prop('required', false);
        } else if (questionType === 'true_false') {
            questionItem.find('.qtest-option-row input').prop('required', false);
            questionItem.find('.qtest-options-container[data-question-type="true_false"] select[name="correct_answer"]').prop('required', true);
            questionItem.find('.qtest-options-container[data-question-type="multiple_choice"] select, .qtest-options-container[data-question-type="short_answer"] input').prop('required', false);
        } else if (questionType === 'short_answer') {
            questionItem.find('.qtest-option-row input').prop('required', false);
            questionItem.find('.qtest-options-container[data-question-type="short_answer"] input[name="correct_answer"]').prop('required', true);
            questionItem.find('.qtest-options-container[data-question-type="multiple_choice"] select, .qtest-options-container[data-question-type="true_false"] select').prop('required', false);
        }
    });
    
    // Initialize question type on page load
    $('.qtest-question-type').each(function() {
        $(this).trigger('change');
    });
    
    // Save question
    $(document).on('click', '.qtest-save-question', function() {
        const questionItem = $(this).closest('.qtest-question-item');
        const questionId = questionItem.data('question-id');
        const testId = $('#test_id').val();
        const questionType = questionItem.find('.qtest-question-type').val();
        
        let correctAnswer = '';
        if (questionType === 'multiple_choice') {
            correctAnswer = questionItem.find('.qtest-correct-answer-row select[name="correct_answer"]').val() || '';
        } else if (questionType === 'true_false') {
            correctAnswer = questionItem.find('[data-question-type="true_false"] select[name="correct_answer"]').val() || '';
        } else if (questionType === 'short_answer') {
            // For short_answer, find the input in the short_answer container
            // Try multiple methods to ensure we find it
            
            // Method 1: Find by ID pattern (most reliable for existing questions)
            const qId = questionItem.data('question-id');
            if (qId && qId > 0) {
                const idInput = questionItem.find('#correct_answer_sa_' + qId);
                if (idInput.length > 0) {
                    correctAnswer = $.trim(idInput.val()) || '';
                }
            }
            
            // Method 2: Find any input with ID containing "correct_answer_sa" (for new questions or if ID method failed)
            if (!correctAnswer) {
                questionItem.find('input').each(function() {
                    const $input = $(this);
                    const id = $input.attr('id') || '';
                    if (id.indexOf('correct_answer_sa') >= 0) {
                        const val = $.trim($input.val());
                        // Accept any value, even if it's "0" (but not empty)
                        if (val !== '') {
                            correctAnswer = val;
                            return false; // break
                        }
                    }
                });
            }
            
            // Method 3: Find by container and name attribute (works even if container is hidden)
            if (!correctAnswer) {
                const shortAnswerContainer = questionItem.find('.qtest-options-container[data-question-type="short_answer"]');
                if (shortAnswerContainer.length > 0) {
                    const input = shortAnswerContainer.find('input[name="correct_answer"]');
                    if (input.length > 0) {
                        const val = $.trim(input.val());
                        if (val !== '') {
                            correctAnswer = val;
                        }
                    }
                }
            }
            
            // Method 4: Find all text inputs with name="correct_answer" and check container
            if (!correctAnswer) {
                questionItem.find('input[type="text"][name="correct_answer"]').each(function() {
                    const $input = $(this);
                    const $container = $input.closest('.qtest-options-container');
                    if ($container.length > 0 && $container.data('question-type') === 'short_answer') {
                        const val = $.trim($input.val());
                        if (val !== '') {
                            correctAnswer = val;
                            return false; // break
                        }
                    }
                });
            }
        }
        
        // Validate required fields based on question type
        // Note: correctAnswer can be "0" which is a valid value, so we check for empty string
        if (questionType === 'short_answer' && (correctAnswer === '' || correctAnswer === null || correctAnswer === undefined)) {
            QTestPopup.error('Please enter a correct answer for short answer questions');
            return;
        }
        
        const formData = {
            action: 'qtest_save_question',
            nonce: $('#qtest_nonce').val(),
            question_id: questionId,
            test_id: testId,
            question_type: questionType,
            question_text: questionItem.find('[name="question_text"]').val(),
            question_image: questionItem.find('.qtest-image-url').val(),
            option_a: questionItem.find('[name="option_a"]').val() || '',
            option_b: questionItem.find('[name="option_b"]').val() || '',
            option_c: questionItem.find('[name="option_c"]').val() || '',
            option_d: questionItem.find('[name="option_d"]').val() || '',
            correct_answer: correctAnswer,
            question_order: questionItem.find('[name="question_order"]').val() || 0
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const statusSpan = questionItem.find('.qtest-save-status');
                    statusSpan.show().fadeOut(3000);
                    
                    // Update question ID if new
                    if (questionId == 0) {
                        const newQuestionId = response.data.question_id;
                        questionItem.attr('data-question-id', newQuestionId);
                        questionItem.find('[data-question-id]').attr('data-question-id', newQuestionId);
                        questionItem.find('.qtest-delete-question').attr('data-question-id', newQuestionId);
                        
                        // Update all IDs in the question item to use the new question ID
                        questionItem.find('input, select, textarea, label').each(function() {
                            const $el = $(this);
                            const id = $el.attr('id');
                            const forAttr = $el.attr('for');
                            
                            if (id) {
                                // Replace patterns like _0, _new_X, etc. with new question ID
                                let newId = id;
                                // Handle specific patterns first
                                if (id.indexOf('_0') >= 0) {
                                    newId = id.replace(/_0/g, '_' + newQuestionId);
                                } else if (id.indexOf('_new_') >= 0) {
                                    newId = id.replace(/_new_\d+/g, '_' + newQuestionId);
                                }
                                // Also handle patterns like correct_answer_sa_0
                                if (id.indexOf('correct_answer_sa_0') >= 0) {
                                    newId = id.replace(/correct_answer_sa_0/g, 'correct_answer_sa_' + newQuestionId);
                                }
                                if (newId !== id) {
                                    $el.attr('id', newId);
                                }
                            }
                            if (forAttr) {
                                let newFor = forAttr;
                                if (forAttr.indexOf('_0') >= 0) {
                                    newFor = forAttr.replace(/_0/g, '_' + newQuestionId);
                                } else if (forAttr.indexOf('_new_') >= 0) {
                                    newFor = forAttr.replace(/_new_\d+/g, '_' + newQuestionId);
                                }
                                // Also handle patterns like correct_answer_sa_0
                                if (forAttr.indexOf('correct_answer_sa_0') >= 0) {
                                    newFor = forAttr.replace(/correct_answer_sa_0/g, 'correct_answer_sa_' + newQuestionId);
                                }
                                if (newFor !== forAttr) {
                                    $el.attr('for', newFor);
                                }
                            }
                        });
                    }
                } else {
                    QTestPopup.error(response.data.message || 'Failed to save question');
                }
            },
            error: function(xhr, status, error) {
                QTestPopup.error('An error occurred. Please try again. Error: ' + error);
            }
        });
    });
    
    // Delete question
    $(document).on('click', '.qtest-delete-question', function() {
        const questionId = parseInt($(this).data('question-id'), 10);
        const questionItem = $(this).closest('.qtest-question-item');
        
        QTestPopup.confirm('Are you sure you want to delete this question?', function(confirmed) {
            if (!confirmed) {
                return;
            }
            
            if (questionId == 0 || isNaN(questionId)) {
                questionItem.remove();
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qtest_delete_question',
                    nonce: $('#qtest_nonce').val(),
                    question_id: questionId
                },
                success: function(response) {
                    if (response.success) {
                        questionItem.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        QTestPopup.error(response.data.message || 'Failed to delete question');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (error) {
                        errorMessage += ' Error: ' + error;
                    }
                    QTestPopup.error(errorMessage);
                }
            });
        });
    });
    
    // Delete test
    $(document).on('click', '.qtest-delete-test', function(e) {
        e.preventDefault();
        
        const testId = $(this).data('test-id');
        
        QTestPopup.confirm('Are you sure you want to delete this test? All questions will also be deleted. This action cannot be undone.', function(confirmed) {
            if (!confirmed) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qtest_delete_test',
                    nonce: $('#qtest_nonce').val(),
                    test_id: testId
                },
                success: function(response) {
                    if (response.success) {
                        QTestPopup.success('Test deleted successfully!', function() {
                            location.reload();
                        });
                    } else {
                        QTestPopup.error(response.data.message || 'Failed to delete test');
                    }
                },
                error: function(xhr, status, error) {
                    QTestPopup.error('An error occurred. Please try again. Error: ' + error);
                }
            });
        });
    });
    
    // Image upload
    function initImageUpload(container) {
        container.find('.qtest-upload-image').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const imageUrlInput = button.siblings('.qtest-image-url');
            const imagePreview = button.siblings('.qtest-image-preview');
            const removeButton = button.siblings('.qtest-remove-image');
            
            const mediaUploader = wp.media({
                title: 'Select Question Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                imageUrlInput.val(attachment.url);
                imagePreview.html('<img src="' + attachment.url + '" style="max-width: 300px; display: block; margin-bottom: 10px;">');
                removeButton.show();
            });
            
            mediaUploader.open();
        });
        
        container.find('.qtest-remove-image').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const imageUrlInput = button.siblings('.qtest-image-url');
            const imagePreview = button.siblings('.qtest-image-preview');
            
            imageUrlInput.val('');
            imagePreview.html('');
            button.hide();
        });
    }
    
    // Initialize image upload for existing questions
    $('.qtest-question-item').each(function() {
        initImageUpload($(this));
    });
    
    // Copy shortcode button
    $(document).on('click', '.qtest-copy-shortcode', function() {
        const shortcode = $(this).data('shortcode');
        const temp = $('<textarea>');
        $('body').append(temp);
        temp.val(shortcode).select();
        document.execCommand('copy');
        temp.remove();
        
        // Show feedback
        const button = $(this);
        const originalText = button.text();
        button.text('Copied!').css('background-color', '#46b450').css('color', '#fff');
        setTimeout(function() {
            button.text(originalText).css('background-color', '').css('color', '');
        }, 2000);
    });
    
    // Send email button (Results page)
    $(document).on('click', '.qtest-send-email', function() {
        const button = $(this);
        const resultId = button.data('result-id');
        const email = button.data('email');
        
        // Disable button
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qtest_resend_email',
                nonce: $('#qtest_nonce').val(),
                result_id: resultId
            },
            success: function(response) {
                if (response.success) {
                    QTestPopup.success(response.data.message || 'Email sent successfully!');
                } else {
                    QTestPopup.error(response.data.message || 'Failed to send email');
                }
            },
            error: function(xhr, status, error) {
                QTestPopup.error('An error occurred. Please try again. Error: ' + error);
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false);
            }
        });
    });
    
    // Delete result button (Results page)
    $(document).on('click', '.qtest-delete-result', function() {
        const button = $(this);
        const resultId = button.data('result-id');
        const row = button.closest('tr');
        
        QTestPopup.confirm('Are you sure you want to delete this result? This action cannot be undone.', function(confirmed) {
            if (!confirmed) {
                return;
            }
            
            // Disable button
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qtest_delete_result',
                    nonce: $('#qtest_nonce').val(),
                    result_id: resultId
                },
                success: function(response) {
                    if (response.success) {
                        QTestPopup.success('Result deleted successfully!', function() {
                            row.fadeOut(300, function() {
                                $(this).remove();
                            });
                        });
                    } else {
                        QTestPopup.error(response.data.message || 'Failed to delete result');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    QTestPopup.error('An error occurred. Please try again. Error: ' + error);
                    button.prop('disabled', false);
                }
            });
        });
    });
    
    // Import questions form
    $('#qtest-import-form').on('submit', function(e) {
        e.preventDefault();
        
        const testId = $('#import_test_id').val();
        const fileInput = $('#import_csv_file')[0];
        
        if (!testId) {
            QTestPopup.error('Please select a test');
            return;
        }
        
        if (!fileInput.files || fileInput.files.length === 0) {
            QTestPopup.error('Please select a CSV file');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'qtest_import_questions');
        formData.append('nonce', $('#qtest_nonce').val());
        formData.append('test_id', testId);
        formData.append('csv_file', fileInput.files[0]);
        
        // Show loading
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Importing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    QTestPopup.success(response.data.message, function() {
                        if (response.data.imported > 0) {
                            // Redirect to test edit page
                            window.location.href = 'admin.php?page=qtest-new&test_id=' + testId;
                        }
                    });
                } else {
                    QTestPopup.error(response.data.message || 'Failed to import questions');
                }
            },
            error: function(xhr, status, error) {
                QTestPopup.error('An error occurred. Please try again. Error: ' + error);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Sequence form
    $('#qtest-sequence-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'qtest_save_sequence',
            nonce: $('#qtest_nonce').val(),
            sequence_id: $('#sequence_id').val(),
            title: $('#sequence_title').val(),
            description: $('#sequence_description').val()
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    QTestPopup.success('Sequence saved successfully!', function() {
                        if (formData.sequence_id == 0) {
                            window.location.href = 'admin.php?page=qtest-sequences&sequence_id=' + response.data.sequence_id;
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    QTestPopup.error(response.data.message || 'Failed to save sequence');
                }
            },
            error: function(xhr, status, error) {
                QTestPopup.error('An error occurred. Please try again. Error: ' + error);
            }
        });
    });
    
    // Add test to sequence
    $('#qtest-add-sequence-test').on('click', function() {
        const testId = $('#add_test_id').val();
        const testOrder = $('#add_test_order').val();
        const autoContinue = $('#add_auto_continue').is(':checked') ? 1 : 0;
        const sequenceId = $('#sequence_id').val();
        
        if (!testId) {
            QTestPopup.error('Please select a test');
            return;
        }
        
        if (!sequenceId) {
            QTestPopup.error('Please save the sequence first');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qtest_add_sequence_test',
                nonce: $('#qtest_nonce').val(),
                sequence_id: sequenceId,
                test_id: testId,
                test_order: testOrder,
                auto_continue: autoContinue
            },
            success: function(response) {
                if (response.success) {
                    QTestPopup.success('Test added to sequence!', function() {
                        location.reload();
                    });
                } else {
                    QTestPopup.error(response.data.message || 'Failed to add test to sequence');
                }
            },
            error: function(xhr, status, error) {
                QTestPopup.error('An error occurred. Please try again. Error: ' + error);
            }
        });
    });
    
    // Remove test from sequence
    $(document).on('click', '.qtest-remove-sequence-test', function() {
        const sequenceTestId = parseInt($(this).data('sequence-test-id'), 10);
        const item = $(this).closest('.qtest-sequence-test-item');
        
        if (isNaN(sequenceTestId) || sequenceTestId <= 0) {
            QTestPopup.error('Invalid sequence test ID');
            return;
        }
        
        QTestPopup.confirm('Are you sure you want to remove this test from the sequence?', function(confirmed) {
            if (!confirmed) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qtest_remove_sequence_test',
                    nonce: $('#qtest_nonce').val(),
                    sequence_test_id: sequenceTestId
                },
                success: function(response) {
                    if (response.success) {
                        item.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        QTestPopup.error(response.data.message || 'Failed to remove test from sequence');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (error) {
                        errorMessage += ' Error: ' + error;
                    }
                    QTestPopup.error(errorMessage);
                }
            });
        });
    });
    
    // Delete sequence
    $(document).on('click', '.qtest-delete-sequence', function() {
        const sequenceId = $(this).data('sequence-id');
        
        QTestPopup.confirm('Are you sure you want to delete this sequence? All tests in the sequence will be removed. This action cannot be undone.', function(confirmed) {
            if (!confirmed) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qtest_delete_sequence',
                    nonce: $('#qtest_nonce').val(),
                    sequence_id: sequenceId
                },
                success: function(response) {
                    if (response.success) {
                        QTestPopup.success('Sequence deleted successfully!', function() {
                            location.reload();
                        });
                    } else {
                        QTestPopup.error(response.data.message || 'Failed to delete sequence');
                    }
                },
                error: function(xhr, status, error) {
                    QTestPopup.error('An error occurred. Please try again. Error: ' + error);
                }
            });
        });
    });
});
