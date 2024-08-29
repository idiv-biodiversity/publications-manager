jQuery(document).ready(function($) {

    $('#publication-search-search-input').attr('placeholder', 'Search publications...');

    $('#search-submit').removeClass('button').addClass('btn btn-outline-primary');

    // Handle click event on the trash icon
    $('.delete-action').click(function() {
        // Get the entry ID
        var id = $(this).data('id');

        // Confirm deletion
        if (confirm('Are you sure you want to delete this publication?')) {
            // Send AJAX request to delete entry
            $.ajax({
                url: ajaxurl, // WordPress AJAX endpoint
                type: 'POST',
                data: {
                    action: 'delete_entry', // Action hook
                    id: id // Entry ID
                },
                success: function(response) {
                    // Handle success response
                    alert(response.data);
                    // Reload the table
                    location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error
                    console.error(xhr.responseText);
                }
            });
        }
    });

    // Handle click event on the trash icon
    $('.update-action').click(function() {
        // Get the entry ID
        var id = $(this).data('id');

        
            // Send AJAX request to delete entry
            $.ajax({
                url: ajaxurl, // WordPress AJAX endpoint
                type: 'POST',
                data: {
                    action: 'update_entry', // Action hook
                    id: id // Entry ID
                },
                success: function(response) {
                    // Handle success response
                    alert(response.data);
                    // Reload the table
                    location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error
                    console.error(xhr.responseText);
                }
            });
        
    }); 

});

// Document on loaded
document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('update-form');

    // Check if the form element exists on pages before proceeding
    if (form) {
        form.addEventListener('submit', function(event) {
            var isValid = true;

            // Check each TinyMCE editor
            tinymce.editors.forEach(function(editor) {
                var content = editor.getContent({format: 'text'}).trim();
                var required = document.getElementById(editor.id + '_required').value === 'true';
                var errorElement = document.getElementById(editor.id + '_error');

                if (required && !content) {
                    errorElement.style.display = 'block';
                    isValid = false;
                } else {
                    errorElement.style.display = 'none';
                }
            });

            // Prevent form submission if any field is invalid
            if (!isValid) {
                event.preventDefault();
            }
        });

        form.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                // Prevent form submission on Enter key press
                event.preventDefault();
                return false;
            }
        });
    }

});






