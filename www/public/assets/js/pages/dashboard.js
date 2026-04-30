const Dashboard = (function () {
        let table;

        const handleTable = () => {
            table = $('#dashtable').DataTable({
                ajax: {
                    url: '/dashboard/list',
                    type: 'POST',
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: 'name'
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            let color = 'bg-danger';

                            if (data === 'running') {
                                color = 'bg-success';
                            }

                            if (data === 'paused') {
                                color = 'bg-warning';
                            }

                            if (data === 'restarting') {
                                color = 'bg-info';
                            }

                            return `<span class="badge ${color}">${data}</span>`;
                        }
                    },
                    {
                        data: 'image'
                    },
                    {
                        data: 'ports',
                        render: data => data || '-'
                    },
                    {
                        data: null,
                        className: 'text-end',
                        render: function (row) {
                            let button = '';

                            if (row.status === 'running') {
                                button = `<button data-id="${row.id}" class="btn btn-sm btn-outline-danger stop-container">Stop</button>`;
                            } else {
                                button = `<button data-id="${row.id}" class="btn btn-sm btn-outline-success start-container">Start</button>`;
                            }

                            return `
                                ${button}
                                <button data-id="${row.id}" disabled class="btn btn-sm btn-outline-secondary">Logs</button>
                            `;
                        }
                    }
                ]
            });
        }

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
                    }).then(() => {
                        table.ajax.reload(null, false);
                    });
                },
                error: function (response) {
                    Swal.fire({
                        icon: "error",
                        title: "It`s a shame",
                        text: 'A internal error occurred',
                    }).then(() => {
                        console.error(response);
                        table.ajax.reload(null, false);
                    });
                }
            });
        }

        const init = () => {
            handleTable();
            handleButtons();
        };

        return {
            init
        };
    }
)
();