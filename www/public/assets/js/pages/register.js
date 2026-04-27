const Register = (function () {

    const handleForm = () => {
        $('#register-form').submit(function (e) {
            e.preventDefault();

            if (!validateRegisterForm($(this))) {
                return;
            }

            const formData = $(this).serialize();

            $.ajax({
                url: '/register',
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (!response.success) {
                        const errorList = Object.values(response.messages)
                            .map(message => `<li>${message}.</li>`)
                            .join('');

                        Swal.fire({
                            icon: 'error',
                            title: 'Ops! There are some validation errors.',
                            html: `<ul style="text-align:left">${errorList}</ul>`
                        });

                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Nice',
                        html: 'User registered successfully!'
                    }).then(() => {
                        window.location.href = "/dashboard";
                    });
                },
                error: function (response) {
                    Swal.fire({
                        icon: "error",
                        title: "It`s a shame",
                        text: 'A internal error occurred',
                    }).then(() => {
                        console.error(response);
                    });
                }
            });

        })
    };

    function validateRegisterForm(data) {
        let values = []
        let emptyFields = [];

        let name = data.find('#name')
        let email = data.find('#email')
        let password = data.find('#password')
        let checkPassword = data.find('#check-password')

        values.push({id: 'Name', element: name})
        values.push({id: 'E-mail', element: email})
        values.push({id: 'Password', element: password})
        values.push({id: 'Confirm Password', element: checkPassword})

        values.forEach(value => {
            if (value.element.val().trim() === '') {
                emptyFields.push(value.id);
            }
        });

        if (emptyFields.length > 0) {
            Swal.fire({
                icon: "warning",
                title: "Empty Value",
                text: emptyFields.join(', ') + ` cannot be empty`,
            });

            return false;
        }

        if (password.val() !== checkPassword.val()) {
            Swal.fire({
                icon: "warning",
                title: "Not equal password",
                text: 'The password and confirm password must be equal.',
            });

            return false;
        }

        return true;
    }

    const init = () => {
        handleForm();
    };

    return {
        init
    };
})();