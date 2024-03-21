const USER_STORAGE_KEY = 'session_id'


function checkUser() {

    const user = localStorage.getItem(USER_STORAGE_KEY);

    if (user) {
        window.location.href = '/profile'
    }
}

checkUser()


$(document).ready(function () {
    $(window).on('pageshow', function (event) {
        if (event.originalEvent.persisted) {
            checkUser();
        }
    });

    $('#loginForm').click(function (e) {
        e.preventDefault(); // Prevent form submission

        // Get form data
        const formData = {
            'email': $('#email').val(),
            'password': $('#password').val()
        };

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: '/php/login.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                // Check response
                if (response.success) {
                    // Authentication successful
                    const { session_id } = response
                    localStorage.setItem(USER_STORAGE_KEY, session_id);
                    window.location.href = '/profile';

                } else {
                    console.log('Authentication failed')
                    // Authentication failed
                    $('#message').text(response.error);
                }
            },
            error: function (xhr, status, error) {
                // Handle AJAX error
                console.error(xhr.responseText);
            }
        });
    });
});
