const Dashboard = (function () {

    const handleButtons = () => {
        $(document).on('click', '.start-container', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            startStopContainer(id, 'start');
        });

        $(document).on('click', '.stop-container', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            startStopContainer(id, 'stop');
        });
    };

    function startStopContainer(id, type) {
        $.ajax({
            url: '/dashboard/' + type,
            type: 'POST',
            data: {
                id: id,
            },
            success: function (response, textStatus, jqXHR) {
                let icon = 'error';

                if (jqXHR.status == 200) {
                    icon = 'success';
                }

                if (jqXHR.status == 304) {
                    icon = 'warning';
                }

                Swal.fire({
                    icon: icon,
                    html: response ? response[0] : 'Operation without return',
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
    }

    const init = () => {
        handleButtons();
    };

    return {
        init
    };
})();