const USER_STORAGE_KEY = 'session_id'

function getLocalSessionId() {

    const user = localStorage.getItem(USER_STORAGE_KEY);

    if (!user) {
        console.log('No User found')
        redirectToLogin();
    }

    return user;
}

function redirectToLogin() {
    window.location.href = '/login';
}


function logOut() {
    const user = localStorage.getItem(USER_STORAGE_KEY);

    if (user) {
        localStorage.removeItem(USER_STORAGE_KEY);
        redirectToLogin();
    }
}

function updateSessionExpirationTime(timeInSeconds) {
    const minutes = Math.floor(timeInSeconds / 60);
    const seconds = timeInSeconds % 60;
    $('#sessionTimer').text(`${minutes} minutes ${seconds} seconds`);
}

function sessionInvalidLogout() {
    $('#sessionInvalid').toast('show');

    setTimeout(function () {
        logOut();
    }, 1000);
}

function setInputValue(inputElement, value) {
    if (value === "" || value === 'Not Set') {
        inputElement.attr('placeholder', 'Not Set');
        inputElement.val(''); // Clear input value
    } else {
        inputElement.val(value);
    }
}

function formatDateForInput(inputType, value) {
    if (inputType === "date") {
        const dateParts = value.split('/');
        if (dateParts.length === 3) {
            return `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
        }
    }
    return value;
}

function validateTelephoneNumber(inputType, value) {
    const pattern = /^(?:\+91)?[6789]\d{9}$/;

    if (inputType !== "tel" || !pattern.test(value)) {
        console.error('Invalid phone number format');
        $('#invalidPhone').toast('show');
        return false;
    }

    return value;
}

function removeSpaces(inputType, value) {
    // Remove all spaces from the value using jQuery
    const newValue = $.trim(value).replace(/\s+/g, '');

    if (newValue.length > 0) {
        return newValue;
    }

    $('#invalidInput').toast('show');
    return false;
}


function createAccountField(options) {
    const {
        containerId,
        fieldId,
        value = 'Not Set',
        label = fieldId,
        inputType = "text",
        editable = true,
        inputValidate = null,
        submitValidate = null
    } = options;

    const userContainer = $(containerId);
    const labelText = $('<h5>').text(label);
    const containerDiv = $('<div>').addClass("mb-4").attr("id", `${fieldId}-container`);
    const inputElement = $("<input>").addClass("form-control form-control-sm").attr("id", fieldId).attr("type", inputType).prop('disabled', true);

    setInputValue(inputElement, inputValidate ? inputValidate(inputType, value) : value);

    const editBtnId = `${fieldId}-edit-btn`;
    const cancelBtnId = `${fieldId}-cancel-btn`;
    const submitBtnId = `${fieldId}-submit-btn`;

    const editBtn = $("<button>").addClass("btn btn-sm btn-outline-secondary").attr("id", editBtnId).text('Edit');
    const cancelBtn = $("<button>").addClass("btn btn-sm btn-outline-dark").attr("id", cancelBtnId).text('Cancel').css("display", "none");
    const submitBtn = $("<button>").addClass("btn btn-sm btn-success").attr("id", submitBtnId).text('Submit').css("display", "none");

    function editMode() {
        editBtn.hide();
        inputElement.prop('disabled', false);
        cancelBtn.show();
        submitBtn.show();
    }

    function reset(updatedValue) {
        cancelBtn.hide();
        submitBtn.hide();
        editBtn.show();
        inputElement.prop('disabled', true);
        setInputValue(inputElement, updatedValue || value);
    }

    editBtn.click(function () {
        editMode();
    });

    cancelBtn.click(function () {
        reset();
    });

    submitBtn.click(function () {
        let updatedValue = inputElement.val();

        if (submitValidate) {
            const validatedValue = submitValidate(inputType, updatedValue);
            if (!validatedValue) {
                return;
            }
            updatedValue = validatedValue;
        }

        const sessionId = getLocalSessionId();

        $.ajax({
            url: '/php/profile.php',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + sessionId,
            },
            data: {
                fieldId: fieldId,
                value: updatedValue
            },
            success: function (response) {
                $('#updateSuccess').toast('show');
                reset(updatedValue);
            },
            error: function (xhr) {
                console.error('Error updating data:', error);

                if (xhr.status === 401) {
                    sessionInvalidLogout();
                }

                reset();
            }
        });
    });

    containerDiv.append(labelText);

    const detailDiv = $('<div>').addClass("d-flex gap-2");
    detailDiv.append(inputElement);

    if (editable) {
        detailDiv.append(editBtn, cancelBtn, submitBtn);
    }

    containerDiv.append(detailDiv);
    userContainer.append(containerDiv);
}


function fetchUserProfile() {
    const sessionId = getLocalSessionId()

    $.ajax({
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionId,
        },
        url: '/php/profile.php',
        dataType: 'json',
        success: (response) => {
            const { user, profile } = response?.data;

            let expiration_time = response.expiration_time

            updateSessionExpirationTime(expiration_time);

            let expirationTimer = setInterval(() => {
                expiration_time--;
                updateSessionExpirationTime(expiration_time);

                if (expiration_time === 0) {
                    clearInterval(expirationTimer);

                    sessionInvalidLogout()
                }
            }, 1000);

            console.log(response)

            $('#id').append(user.id.toString().padStart(4, '0'));
            $('#email').append(user.email);
            $('#createdAt').append(new Date(user.created_at).toLocaleString());
            $('#updatedAt').append(new Date(user.updated_at).toLocaleString());

            const profile_container = '#profile-container'

            createAccountField({
                containerId: profile_container,
                fieldId: 'firstName',
                value: profile?.firstName,
                label: "First Name",
                submitValidate: removeSpaces
            });

            createAccountField({
                containerId: profile_container,
                fieldId: 'lastName',
                value: profile?.lastName,
                label: "Last Name",
                submitValidate: removeSpaces
            });

            createAccountField({
                containerId: profile_container,
                fieldId: 'dob',
                value: profile?.dob,
                label: "Date of Birth",
                inputType: "date",
                inputValidate: formatDateForInput
            });

            createAccountField({
                containerId: profile_container,
                fieldId: 'address',
                value: profile?.address,
                label: "Address",
            });

            createAccountField({
                containerId: profile_container,
                fieldId: 'contact',
                value: profile?.contact,
                label: "Contact (Indian Phone Number only)",
                inputType: "tel",
                submitValidate: validateTelephoneNumber
            });


        },
        error: (xhr) => {
            // Handle AJAX error
            console.error(xhr.responseText);
            logOut()
        }
    })
}

getLocalSessionId();

$(document).ready(function () {
    $(window).on('pageshow', function (event) {
        if (event.originalEvent.persisted) {
            getLocalSessionId();
        }
    });

    fetchUserProfile();
});