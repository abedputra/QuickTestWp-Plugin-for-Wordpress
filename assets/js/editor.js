jQuery(document).ready(function($) {
    'use strict';
    
    // Insert shortcode button in meta box
    $('#qtest-insert-shortcode').on('click', function() {
        const testId = $('#qtest-select-test').val();
        
        if (!testId) {
            alert('Please select a test first.');
            return;
        }
        
        const shortcode = '[qtest id="' + testId + '"]';
        
        // Try to insert into editor
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            // Gutenberg Editor
            const editor = wp.data.select('core/editor');
            const content = editor.getEditedPostContent();
            const newContent = content + '\n\n' + shortcode;
            wp.data.dispatch('core/editor').editPost({ content: newContent });
            alert('Test shortcode inserted!');
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
            // Classic Editor (TinyMCE)
            tinyMCE.activeEditor.execCommand('mceInsertContent', false, shortcode);
            alert('Test shortcode inserted!');
        } else {
            // Fallback: try to insert into textarea
            const textarea = $('#content, #post_content, .wp-editor-area');
            if (textarea.length) {
                const currentContent = textarea.val();
                const cursorPos = textarea[0].selectionStart;
                const textBefore = currentContent.substring(0, cursorPos);
                const textAfter = currentContent.substring(cursorPos);
                const newContent = textBefore + '\n\n' + shortcode + '\n\n' + textAfter;
                textarea.val(newContent);
                textarea[0].setSelectionRange(cursorPos + shortcode.length + 4, cursorPos + shortcode.length + 4);
                alert('Test shortcode inserted!');
            } else {
                // Last resort: copy to clipboard
                const temp = $('<textarea>');
                $('body').append(temp);
                temp.val(shortcode).select();
                document.execCommand('copy');
                temp.remove();
                alert('Shortcode copied to clipboard: ' + shortcode + '\n\nPlease paste it into your post.');
            }
        }
    });
    
    // Add quicktag button if QTags is available
    if (typeof QTags !== 'undefined') {
        QTags.addButton('qtest_select', 'QTest', function() {
            if (typeof qtestEditor !== 'undefined' && qtestEditor.tests && qtestEditor.tests.length > 0) {
                let options = 'Select Test:\n';
                qtestEditor.tests.forEach(function(test) {
                    options += test.id + ' - ' + test.title + '\n';
                });
                const testId = prompt(options + '\nEnter Test ID:');
                if (testId !== null && testId !== '') {
                    QTags.insertContent('[qtest id="' + testId + '"]');
                }
            } else {
                const testId = prompt('Enter Test ID:');
                if (testId !== null && testId !== '') {
                    QTags.insertContent('[qtest id="' + testId + '"]');
                }
            }
        });
    }
});
