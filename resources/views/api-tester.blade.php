<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Tester</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Prism.js (Tomorrow Theme) -->
    <link href="https://cdn.jsdelivr.net/npm/prismjs/themes/prism-tomorrow.min.css" rel="stylesheet"/>

    <style>
        .response-box {
            white-space: pre-wrap;
            min-height: 200px;
        }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <h2>🔧 API Tester</h2>

        <!-- Method & URL -->
        <div class="mb-3">
            <label class="form-label">HTTP Method</label>
            <select class="form-select" id="method">
                <option>GET</option>
                <option>POST</option>
                <option>PUT</option>
                <option>PATCH</option>
                <option>DELETE</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">API URL</label>
            <input type="text" id="url" class="form-control" placeholder="https://api.example.com/endpoint">
        </div>

        <!-- Headers -->
        <div class="mb-3">
            <label class="form-label">Headers</label>
            <div id="headers">
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control header-key" placeholder="Header Name">
                    <input type="text" class="form-control header-value" placeholder="Header Value">
                    <button class="btn btn-danger btn-sm remove-header">✖</button>
                </div>
            </div>
            <button class="btn btn-secondary btn-sm" id="add-header">+ Add Header</button>
        </div>

        <!-- Request Body -->
        <div class="mb-3">
            <label class="form-label">Request Body</label>
            <ul class="nav nav-tabs" id="bodyTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="json-tab" data-bs-toggle="tab" data-bs-target="#jsonBody" type="button" role="tab">JSON</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="form-tab" data-bs-toggle="tab" data-bs-target="#formBody" type="button" role="tab">Form Data</button>
                </li>
            </ul>

            <div class="tab-content border p-3" id="bodyTabContent">
                <!-- JSON -->
                <div class="tab-pane fade show active" id="jsonBody" role="tabpanel">
                    <textarea id="body" class="form-control" rows="6">{ "key": "value" }</textarea>
                </div>

                <!-- Form Data -->
                <div class="tab-pane fade" id="formBody" role="tabpanel">
                    <div id="form-data-container">
                        <div class="d-flex gap-2 mb-2">
                            <input type="text" class="form-control form-key" placeholder="Key">
                            <input type="text" class="form-control form-value" placeholder="Value">
                            <input type="file" class="form-control form-file d-none">
                            <button class="btn btn-warning toggle-file" title="Toggle File Input">📎</button>
                            <button class="btn btn-danger remove-field">✖</button>
                        </div>
                    </div>
                    <button class="btn btn-secondary btn-sm" id="add-form-field">+ Add Field</button>
                </div>
            </div>
        </div>

        <!-- Send Request Button -->
        <button class="btn btn-primary" id="sendRequest">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            <span class="btn-text">🚀 Send Request</span>
        </button>

        <!-- Response -->
        <hr>
        <h4>Response</h4>
        <pre class="language-json response-box"><code id="responseBox" class="language-json">Waiting for response...</code></pre>
    </div>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs/prism.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs/components/prism-json.min.js"></script>

    <!-- jQuery Logic -->
    <script>
        // Add header
        $('#add-header').click(() => {
            $('#headers').append(`
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control header-key" placeholder="Header Name">
                    <input type="text" class="form-control header-value" placeholder="Header Value">
                    <button class="btn btn-danger btn-sm remove-header">✖</button>
                </div>
            `);
        });

        $(document).on('click', '.remove-header', function () {
            $(this).closest('.d-flex').remove();
        });

        // Add/remove form field
        $('#add-form-field').click(() => {
            $('#form-data-container').append(`
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control form-key" placeholder="Key">
                    <input type="text" class="form-control form-value" placeholder="Value">
                    <input type="file" class="form-control form-file d-none">
                    <button class="btn btn-warning toggle-file">📎</button>
                    <button class="btn btn-danger remove-field">✖</button>
                </div>
            `);
        });

        $(document).on('click', '.remove-field', function () {
            $(this).closest('.d-flex').remove();
        });

        $(document).on('click', '.toggle-file', function () {
            const row = $(this).closest('.d-flex');
            row.find('.form-value').toggleClass('d-none');
            row.find('.form-file').toggleClass('d-none');
        });

        // Send request
        $('#sendRequest').click(() => {
            const $btn = $('#sendRequest');
            $btn.prop('disabled', true);
            $btn.find('.spinner-border').removeClass('d-none');
            $btn.find('.btn-text').text('Sending...');

            const method = $('#method').val();
            const url = $('#url').val();
            let headers = {};
            $('.header-key').each((i, el) => {
                const key = $(el).val();
                const val = $('.header-value').eq(i).val();
                if (key) headers[key] = val;
            });

            const isJson = $('#json-tab').hasClass('active');
            let ajaxOptions = {
                url: '/api-tester/send',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                complete: () => {
                    $btn.prop('disabled', false);
                    $btn.find('.spinner-border').addClass('d-none');
                    $btn.find('.btn-text').text('🚀 Send Request');
                },
                success: res => {
                    const formatted = JSON.stringify(res, null, 4);
                    $('#responseBox').text(formatted);
                    Prism.highlightElement(document.getElementById('responseBox'));
                },
                error: xhr => {
                    $('#responseBox').text(xhr.responseText);
                    Prism.highlightElement(document.getElementById('responseBox'));
                }
            };

            if (isJson) {
                try {
                    ajaxOptions.data = {
                        method,
                        url,
                        headers,
                        body: JSON.parse($('#body').val() || '{}'),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    };
                    ajaxOptions.contentType = 'application/x-www-form-urlencoded';
                } catch (e) {
                    alert("Invalid JSON body");
                    $btn.prop('disabled', false);
                    return;
                }
            } else {
                let formData = new FormData();
                $('#form-data-container .d-flex').each(function () {
                    const key = $(this).find('.form-key').val();
                    const value = $(this).find('.form-value').val();
                    const fileInput = $(this).find('.form-file')[0];
                    if (!$(fileInput).hasClass('d-none') && fileInput.files.length > 0) {
                        formData.append(key, fileInput.files[0]);
                    } else {
                        formData.append(key, value);
                    }
                });

                for (const [k, v] of Object.entries(headers)) {
                    formData.append(`headers[${k}]`, v);
                }
                formData.append('method', method);
                formData.append('url', url);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                ajaxOptions.data = formData;
                ajaxOptions.contentType = false;
                ajaxOptions.processData = false;
            }

            $.ajax(ajaxOptions);
        });
    </script>
</body>
</html>
