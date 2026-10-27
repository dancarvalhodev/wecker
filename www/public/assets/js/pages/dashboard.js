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
                        data: 'name',
                        render: data => `<span class="wk-mono-strong">${escapeHtml(data)}</span>`
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            const modifier = data === 'running' ? ' wk-status--running' : '';

                            return `<span class="wk-status${modifier}">${escapeHtml(data)}</span>`;
                        }
                    },
                    {
                        data: 'image',
                        render: escapeHtml
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
                                button = `<button type="button" data-id="${row.id}" class="btn btn-sm btn-outline-stop stop-container">Stop</button>`;
                            } else {
                                button = `<button type="button" data-id="${row.id}" class="btn btn-sm btn-primary start-container">Start</button>`;
                            }

                            return `
                                ${button}
                                <button type="button" data-id="${row.id}" class="btn btn-sm btn-outline-muted log">Logs</button>
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

            $(document).on('click', '.log', function (e) {
                e.preventDefault();
                let id = $(this).data('id');
                startStopContainer(id, 'log');
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
                    let icon = defineIcon(jqXHR.status);

                    if (type === 'log') {
                        $('#logContent').text(response[0] || 'No logs yet.');

                        const modal = new bootstrap.Modal(document.getElementById('logModal'));
                        modal.show();
                        return;
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

        // Container names and images come from Docker and may contain HTML
        function escapeHtml(text) {
            return $('<div>').text(text).html();
        }

        function defineIcon(statusCode) {
            if (statusCode === 200) {
                return 'success';
            }

            if (statusCode === 304) {
                return 'warning';
            }

            return 'error'
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