const Create = (function () {

    const handleForm = () => {
        $('#create-form').submit(function (e) {
            e.preventDefault();

            const submit = $('#create-submit');
            const label = submit.html();

            // Pulling a missing image can take a while
            submit.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Creating…');

            $.ajax({
                url: '/dashboard/create',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        text: response[0],
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    // error is a list, a {field: message} object, or a plain string on a 500
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'An internal error occurred';
                    const messages = typeof error === 'object' ? Object.values(error) : [error];

                    Swal.fire({
                        icon: 'error',
                        title: 'Could not create the container',
                        text: messages.join(' '),
                    });
                },
                complete: function () {
                    submit.prop('disabled', false).html(label);
                }
            });
        });
    };

    const init = () => {
        handleForm();
    };

    return {
        init
    };
})();
