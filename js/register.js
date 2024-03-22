const USER_STORAGE_KEY = 'session_id'

const user = localStorage.getItem(USER_STORAGE_KEY);

if (user) {
    window.location.href = '/profile'
}

$(document).ready(function () {

    $('#registerForm').click(function (e) {
        e.preventDefault();

        const formData = {
            'email': $('#email').val(),
            'password': $('#password').val(),
            'confirm_password': $('#confirm_password').val()
        };

        $.ajax({
            type: 'POST',
            url: '/php/register.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                // Check response
                if (response.success) {
                    $('#registerSuccess').toast('show');

                    setTimeout(function () {
                        window.location.href = '/login';
                    }, 1000);

                } else {
                    console.log('Authentication failed')
                    let errorMessage = response.error

                    if (response?.passwordWeak) {
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
