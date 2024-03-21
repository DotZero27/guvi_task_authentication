const USER_STORAGE_KEY = 'session_id'

const user = localStorage.getItem(USER_STORAGE_KEY);

if (user) {
    window.location.href = '/profile'
}

$(document).ready(function () {
    $('#registerForm').click(function (e) {
        e.preventDefault(); // Prevent form submission

        // Get form data
        const formData = {
            'email': $('#email').val(),
            'password': $('#password').val(),
            'confirm_password': $('#confirm_password').val()
        };

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: '/php/register.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                // Check response
                if (response.success) {
                    const { session_id } = response

                    localStorage.setItem(USER_STORAGE_KEY, session_id);
                    window.location.href = '/profile';

                } else {
                    // Authentication failed
                    console.log('Authentication failed')
                    let errorMessage = response.error

                    if(response?.passwordWeak){
                        $('#pwRequirements').show()

                    }
                    
                    if (response?.missing_fields) {
                        errorMessage += ` (${response.missing_fields.join(', ')})`;
                    }

                    $('#message').text(errorMessage)
                }
            },
            error: function (xhr, status, error) {
                // Handle AJAX error
                console.error(xhr.responseText);
            }
        });
    });
});
