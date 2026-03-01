const Register = (function () {

    const handleForm = () => {
        $('#register-form').submit(function (e){
            e.preventDefault();
            validateRegisterForm($(this))
            return
            const formData = $(this).serialize();

            $.ajax({
                url: '/register',
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Success:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });

        })
    };

    function validateRegisterForm(data) {
        let name = data.find('#name')
        let email = data.find('#email')
        let password = data.find('#password')
        let checkPassword = data.find('#check-password')


    }

    const init = () => {
        handleForm();
    };

    return {
        init
    };

})();