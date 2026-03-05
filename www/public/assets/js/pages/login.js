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
                success: function(response) {
                    if (!response.success) {
                        const errorList = Object.values(response.messages)
                            .map(message => `<li>${message}.</li>`)
                            .join('');

                        Swal.fire({
                            icon: 'error',
                            title: 'Ops! There are some validation errors.',
                            html: `<ul style="text-align:left">${errorList}</ul>`
                        });

                        return
                    }

                    window.location.href = "/dashboard";
                },
                error: function() {
                    Swal.fire({
                      icon: "error",
                      title: "It`s a shame",
                      text: 'A internal error occurred',
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