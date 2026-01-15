/**
 * QTest Popup Utility
 * Better popup replacement for alert() and confirm()
 */

(function($) {
    'use strict';
    
    window.QTestPopup = {
        /**
         * Show success message
         */
        success: function(message, callback) {
            this.show('success', 'Success', message, callback);
        },
        
        /**
         * Show error message
         */
        error: function(message, callback) {
            this.show('error', 'Error', message, callback);
        },
        
        /**
         * Show info message
         */
        info: function(message, callback) {
            this.show('info', 'Information', message, callback);
        },
        
        /**
         * Show warning message
         */
        warning: function(message, callback) {
            this.show('warning', 'Warning', message, callback);
        },
        
        /**
         * Show confirmation dialog
         */
        confirm: function(message, callback) {
            this.show('confirm', 'Confirm', message, callback, true);
        },
        
        /**
         * Main show function
         */
        show: function(type, title, message, callback, isConfirm) {
            const icons = {
                success: '✓',
                error: '✕',
                info: 'ℹ',
                warning: '⚠',
                confirm: '?'
            };
            
            const colors = {
                success: '#4caf50',
                error: '#f44336',
                info: '#2196F3',
                warning: '#ff9800',
                confirm: '#2196F3'
            };
            
            const popup = $('<div class="qtest-popup-overlay">' +
                '<div class="qtest-popup-container qtest-popup-' + type + '">' +
                '<div class="qtest-popup-icon" style="background-color: ' + colors[type] + '">' + icons[type] + '</div>' +
                '<div class="qtest-popup-content">' +
                '<h3 class="qtest-popup-title">' + title + '</h3>' +
                '<p class="qtest-popup-message">' + message + '</p>' +
                '</div>' +
                '<div class="qtest-popup-buttons">' +
                (isConfirm ? 
                    '<button class="qtest-popup-btn qtest-popup-cancel">Cancel</button>' +
                    '<button class="qtest-popup-btn qtest-popup-confirm" style="background-color: ' + colors[type] + '">OK</button>' :
                    '<button class="qtest-popup-btn qtest-popup-ok" style="background-color: ' + colors[type] + '">OK</button>'
                ) +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append(popup);
            
            // Animate in
            setTimeout(function() {
                popup.addClass('qtest-popup-show');
            }, 10);
            
            // Helper function to close popup and cleanup
            const closePopup = function(result) {
                popup.removeClass('qtest-popup-show');
                // Always remove ESC key listener when closing
                $(document).off('keydown.qtest-popup');
                setTimeout(function() {
                    popup.remove();
                    if (callback) callback(result);
                }, 300);
            };
            
            // Handle button clicks
            if (isConfirm) {
                popup.find('.qtest-popup-confirm').on('click', function() {
                    closePopup(true);
                });
                
                popup.find('.qtest-popup-cancel').on('click', function() {
                    closePopup(false);
                });
            } else {
                popup.find('.qtest-popup-ok').on('click', function() {
                    closePopup();
                });
            }
            
            // Close on overlay click (only for non-confirm)
            if (!isConfirm) {
                popup.on('click', function(e) {
                    if ($(e.target).hasClass('qtest-popup-overlay')) {
                        closePopup();
                    }
                });
            }
            
            // Close on ESC key
            $(document).on('keydown.qtest-popup', function(e) {
                if (e.keyCode === 27) { // ESC
                    closePopup(isConfirm ? false : undefined);
                }
            });
        }
    };
    
})(jQuery);
