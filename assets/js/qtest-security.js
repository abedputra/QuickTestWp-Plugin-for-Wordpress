/**
 * QTest Security - Anti-cheat and Anti-refresh
 */

(function($) {
    'use strict';
    
    // Only apply security if quiz is active
    if (typeof qtestData === 'undefined') {
        return;
    }
    
    let testStarted = false;
    let testCompleted = false;
    const testSessionKey = 'qtest_session_' + qtestData.testId;
    
    // Tab switch detection variables (shared across all calls)
    let tabSwitchListenersAdded = false;
    let switchCount = 0;
    let isShowingPopup = false;
    const maxSwitches = 3;
    let tabSwitchHandler = null;
    
    // Right-click detection variables (shared across all calls)
    let rightClickListenersAdded = false;
    let rightClickHandler = null;
    let isShowingRightClickPopup = false;
    
    // Copy/paste detection variables
    let copyPasteListenersAdded = false;
    let copyHandler = null;
    let pasteHandler = null;
    let cutHandler = null;
    let selectStartHandler = null;
    
    // Dev tools detection variables
    let devToolsListenersAdded = false;
    let devToolsHandler = null;
    
    // Print screen detection variables
    let printScreenListenersAdded = false;
    let printScreenHandler = null;
    
    // Check if test was already started
    const sessionData = sessionStorage.getItem(testSessionKey);
    if (sessionData) {
        const data = JSON.parse(sessionData);
        if (data.started && !data.completed) {
            testStarted = true;
            // Show warning
            if (typeof QTestPopup !== 'undefined') {
                QTestPopup.warning('You have an active test session. Continuing from where you left off.');
            }
        } else if (data.completed) {
            testCompleted = true;
        }
    }
    
    // Mark test as started
    function markTestStarted() {
        testStarted = true;
        sessionStorage.setItem(testSessionKey, JSON.stringify({
            started: true,
            completed: false,
            startTime: new Date().toISOString()
        }));
    }
    
    // Mark test as completed
    function markTestCompleted() {
        testCompleted = true;
        sessionStorage.setItem(testSessionKey, JSON.stringify({
            started: true,
            completed: true,
            completedTime: new Date().toISOString()
        }));
    }
    
    // Prevent page refresh/close
    let beforeUnloadHandler = null;
    let allowNavigation = false; // Flag to allow legitimate navigation
    
    function preventRefresh() {
        if (!testStarted || testCompleted || allowNavigation) {
            return;
        }
        
        beforeUnloadHandler = function(e) {
            // Don't show prompt if navigation is allowed
            if (allowNavigation || testCompleted) {
                return;
            }
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your test progress may be lost.';
            return e.returnValue;
        };
        
        window.addEventListener('beforeunload', beforeUnloadHandler);
    }
    
    function removeRefreshPrevention() {
        // Set flag to allow navigation
        allowNavigation = true;
        testCompleted = true;
        
        if (beforeUnloadHandler) {
            window.removeEventListener('beforeunload', beforeUnloadHandler);
            beforeUnloadHandler = null;
        }
    }
    
    // Disable right click
    function disableRightClick() {
        if (testCompleted) return;
        
        // Prevent adding multiple event listeners
        if (rightClickListenersAdded) {
            return;
        }
        rightClickListenersAdded = true;
        
        rightClickHandler = function(e) {
            if (testStarted && !testCompleted) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event from bubbling to other listeners
                e.stopImmediatePropagation(); // Stop all other listeners on the same element
                
                // Prevent showing multiple popups at the same time
                if (isShowingRightClickPopup) {
                    return false;
                }
                
                isShowingRightClickPopup = true;
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Right-click is disabled during the test.', function() {
                        isShowingRightClickPopup = false;
                    });
                } else {
                    // Reset flag after a delay if no popup system
                    setTimeout(function() {
                        isShowingRightClickPopup = false;
                    }, 1000);
                }
                return false;
            }
        };
        
        document.addEventListener('contextmenu', rightClickHandler);
    }
    
    // Disable copy/paste
    function disableCopyPaste() {
        if (testCompleted) return;
        
        // Prevent adding multiple event listeners
        if (copyPasteListenersAdded) return;
        copyPasteListenersAdded = true;
        
        // Disable copy (Ctrl+C, Cmd+C)
        copyHandler = function(e) {
            if (testStarted && !testCompleted) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Copying is disabled during the test.');
                }
                return false;
            }
        };
        document.addEventListener('copy', copyHandler);
        
        // Disable paste (Ctrl+V, Cmd+V)
        pasteHandler = function(e) {
            if (testStarted && !testCompleted) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Pasting is disabled during the test.');
                }
                return false;
            }
        };
        document.addEventListener('paste', pasteHandler);
        
        // Disable cut (Ctrl+X, Cmd+X)
        cutHandler = function(e) {
            if (testStarted && !testCompleted) {
                e.preventDefault();
                return false;
            }
        };
        document.addEventListener('cut', cutHandler);
        
        // Disable text selection (except in form inputs)
        selectStartHandler = function(e) {
            if (testStarted && !testCompleted) {
                // Allow selection in input fields and completion form
                const target = e.target;
                if (target.tagName === 'INPUT' || 
                    target.tagName === 'TEXTAREA' || 
                    $(target).closest('.qtest-completion-form').length > 0 ||
                    $(target).closest('.qtest-result-display').length > 0) {
                    return true;
                }
                e.preventDefault();
                return false;
            }
        };
        document.addEventListener('selectstart', selectStartHandler);
    }
    
    // Disable developer tools shortcuts
    function disableDevTools() {
        if (testCompleted) return;
        
        // Prevent adding multiple event listeners
        if (devToolsListenersAdded) return;
        devToolsListenersAdded = true;
        
        devToolsHandler = function(e) {
            if (!testStarted || testCompleted) return;
            
            // Disable F12
            if (e.keyCode === 123) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Developer tools are disabled during the test.');
                }
                return false;
            }
            
            // Disable Ctrl+Shift+I (Chrome DevTools)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Developer tools are disabled during the test.');
                }
                return false;
            }
            
            // Disable Ctrl+Shift+J (Chrome Console)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Developer tools are disabled during the test.');
                }
                return false;
            }
            
            // Disable Ctrl+Shift+C (Chrome Element Inspector)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Developer tools are disabled during the test.');
                }
                return false;
            }
            
            // Disable Ctrl+U (View Source)
            if (e.ctrlKey && e.keyCode === 85) {
                e.preventDefault();
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('View source is disabled during the test.');
                }
                return false;
            }
            
            // Disable Ctrl+S (Save Page)
            if (e.ctrlKey && e.keyCode === 83 && !e.shiftKey) {
                e.preventDefault();
                return false;
            }
        };
        document.addEventListener('keydown', devToolsHandler);
    }
    
    // Detect tab/window switching
    function detectTabSwitch() {
        if (testCompleted) return;
        
        // Prevent adding multiple event listeners
        if (tabSwitchListenersAdded) return;
        tabSwitchListenersAdded = true;
        
        let isTabActive = true;
        let lastSwitchTime = 0;
        const debounceTime = 500; // Prevent multiple triggers within 500ms
        
        // Use only visibilitychange event (more reliable than blur)
        tabSwitchHandler = function() {
            if (document.hidden) {
                isTabActive = false;
                if (testStarted && !testCompleted && !isShowingPopup) {
                    // Debounce: prevent multiple triggers in quick succession
                    const now = Date.now();
                    if (now - lastSwitchTime < debounceTime) {
                        return;
                    }
                    lastSwitchTime = now;
                    
                    switchCount++;
                    
                    // Prevent showing multiple popups at the same time
                    if (isShowingPopup) return;
                    isShowingPopup = true;
                    
                    if (switchCount > maxSwitches) {
                        if (typeof QTestPopup !== 'undefined') {
                            QTestPopup.error('You have switched tabs/windows too many times. The test will be submitted automatically.', function() {
                                isShowingPopup = false;
                                // Trigger form submission
                                if ($('#qtest-submit-btn').length && $('#qtest-submit-btn').is(':visible')) {
                                    $('#qtest-submit-btn').click();
                                } else {
                                    // Show completion form
                                    $(document).trigger('qtest:forceSubmit');
                                }
                            });
                        } else {
                            isShowingPopup = false;
                            // Show completion form directly
                            $(document).trigger('qtest:forceSubmit');
                        }
                    } else {
                        if (typeof QTestPopup !== 'undefined') {
                            QTestPopup.warning('Warning: You switched tabs/windows. (' + switchCount + '/' + maxSwitches + ' warnings)', function() {
                                isShowingPopup = false;
                            });
                        } else {
                            isShowingPopup = false;
                        }
                    }
                }
            } else {
                isTabActive = true;
                // Reset popup flag when tab becomes visible again
                setTimeout(function() {
                    isShowingPopup = false;
                }, 1000);
            }
        };
        document.addEventListener('visibilitychange', tabSwitchHandler);
    }
    
    // Disable print screen
    function disablePrintScreen() {
        if (testCompleted) return;
        
        // Prevent adding multiple event listeners
        if (printScreenListenersAdded) return;
        printScreenListenersAdded = true;
        
        printScreenHandler = function(e) {
            if (!testStarted || testCompleted) return;
            
            // Disable Print Screen
            if (e.keyCode === 44) { // Print Screen key
                if (typeof QTestPopup !== 'undefined') {
                    QTestPopup.warning('Screenshots are not allowed during the test.');
                }
            }
        };
        document.addEventListener('keyup', printScreenHandler);
    }
    
    // Initialize security when test starts
    function initSecurity() {
        if (testCompleted) {
            return; // Don't enable security if test is already completed
        }
        
        markTestStarted();
        preventRefresh();
        disableRightClick();
        disableCopyPaste();
        disableDevTools();
        detectTabSwitch();
        disablePrintScreen();
        
        // Add visual indicator that security is active
        if ($('.qtest-security-indicator').length === 0) {
            $('body').append('<div class="qtest-security-indicator" title="Security mode active" style="position: fixed; bottom: 10px; right: 10px; width: 12px; height: 12px; background-color: #34a853; border-radius: 50%; z-index: 9999; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>');
        }
    }
    
    // Remove security indicator
    function removeSecurityIndicator() {
        $('.qtest-security-indicator').remove();
    }
    
    // Initialize security when page loads (if test is active)
    $(document).ready(function() {
        // Wait a bit for quiz to initialize
        setTimeout(function() {
            if ($('.qtest-question-page.active').length > 0 && !testCompleted) {
                initSecurity();
            }
        }, 500);
        
        // Also initialize when first question is shown
        $(document).on('qtest:testStarted', function() {
            if (!testStarted) {
                initSecurity();
            }
        });
    });
    
    // Remove right-click listener
    function removeRightClickPrevention() {
        if (rightClickHandler) {
            document.removeEventListener('contextmenu', rightClickHandler);
            rightClickHandler = null;
            rightClickListenersAdded = false;
            isShowingRightClickPopup = false;
        }
    }
    
    // Remove copy/paste listeners
    function removeCopyPastePrevention() {
        if (copyHandler) {
            document.removeEventListener('copy', copyHandler);
            copyHandler = null;
        }
        if (pasteHandler) {
            document.removeEventListener('paste', pasteHandler);
            pasteHandler = null;
        }
        if (cutHandler) {
            document.removeEventListener('cut', cutHandler);
            cutHandler = null;
        }
        if (selectStartHandler) {
            document.removeEventListener('selectstart', selectStartHandler);
            selectStartHandler = null;
        }
        copyPasteListenersAdded = false;
    }
    
    // Remove dev tools listener
    function removeDevToolsPrevention() {
        if (devToolsHandler) {
            document.removeEventListener('keydown', devToolsHandler);
            devToolsHandler = null;
            devToolsListenersAdded = false;
        }
    }
    
    // Remove print screen listener
    function removePrintScreenPrevention() {
        if (printScreenHandler) {
            document.removeEventListener('keyup', printScreenHandler);
            printScreenHandler = null;
            printScreenListenersAdded = false;
        }
    }
    
    // Remove tab switch listener
    function removeTabSwitchPrevention() {
        if (tabSwitchHandler) {
            document.removeEventListener('visibilitychange', tabSwitchHandler);
            tabSwitchHandler = null;
            tabSwitchListenersAdded = false;
        }
    }
    
    // Mark test as completed when form is submitted
    $(document).on('qtest:testCompleted', function() {
        markTestCompleted();
        // Re-enable all features
        testCompleted = true;
        removeSecurityIndicator();
        removeRefreshPrevention();
        removeRightClickPrevention();
        removeCopyPastePrevention();
        removeDevToolsPrevention();
        removePrintScreenPrevention();
        removeTabSwitchPrevention();
    });
    
    // Export functions for use in main script
    window.QTestSecurity = {
        markTestStarted: markTestStarted,
        markTestCompleted: markTestCompleted,
        initSecurity: initSecurity,
        removeRefreshPrevention: removeRefreshPrevention
    };
    
})(jQuery);
