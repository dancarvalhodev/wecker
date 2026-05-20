const Create = (function () {

    const handleForm = () => {
        $('#create-form').submit(function (e) {
            e.preventDefault();

            if (!validateCreateForm($(this))) {
                return;
            }

            const formData = $(this).serialize();

            $.ajax({
                url: '/dashboard/create',
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
                        html: 'Container created successfully!'
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

    function validateCreateForm(data) {
        let values = []
        let emptyFields = [];

        let name = data.find('#name')
        let image = data.find('#image')
        let port = data.find('#port')

        values.push({id: 'Name', element: name})
        values.push({id: 'Image', element: image})
        values.push({id: 'Port', element: port})

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