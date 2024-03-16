$(document).ready(function () {
    $('#loginForm').click(function (e) {
        e.preventDefault(); // Prevent form submission

        // Get form data
        const formData = {
            'username': $('#username').val(),
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
                    localStorage.setItem('loggedIn', true);
                    alert(response.success);
                    // Redirect to dashboard or desired page
                    window.location.href = 'profile.html';
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
