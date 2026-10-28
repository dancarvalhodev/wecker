const Login = (function () {

    const handleForm = () => {
        $('#login-form').submit(function (e){
            e.preventDefault();

            if(!validateLoginForm($(this))) {
                return;
            }

            const formData = $(this).serialize();

            $.ajax({
                url: '/login',
                type: 'POST',
                data: formData,
                success: function() {
                    window.location.href = "/dashboard";
                },
                error: function(xhr) {
                    // error is a list, a {field: message} object, or a plain string on a 500
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'An internal error occurred';
                    const messages = typeof error === 'object' ? Object.values(error) : [error];

                    Swal.fire({
                        icon: 'error',
                        title: 'Could not log in',
                        text: messages.join(' '),
                    });
                }
            });

        })
    };

    function validateLoginForm(data) {
        let values = []
        let emptyFields = [];

        let email = data.find('#email')
        let password = data.find('#password')

        values.push({id: 'E-mail', element: email})
        values.push({id: 'Password', element: password})

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

        return true;
    }

    const init = () => {
        handleForm();
    };

    return {
        init
    };
})();