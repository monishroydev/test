<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Sharing Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Secure File Sharing Tool</h3>
                        <p class="mb-0">Upload multiple files (up to 200MB each). Share via 6-digit code. Expires in 24h.</p>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file" class="form-label">Select Files (Max 200MB each, multiple OK)</label>
                                <input type="file" class="form-control" id="file" name="file[]" multiple required>
                            </div>
                            <div class="progress mb-3" id="progressBar" style="display: none;">
                                <div class="progress-bar" role="progressbar" style="width: 0%;" id="progress">0%</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="uploadBtn">Upload & Generate Share Code</button>
                        </form>
                        <div id="result" class="mt-3" style="display: none;"></div>
                    </div>
                </div>

                <!-- View Section -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">View Shared Content</h5>
                        <small class="text-white-50">Paste a 6-digit share code and click View</small>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareCode" placeholder="Enter 6-digit share code" maxlength="6">
                            <button class="btn btn-outline-primary" id="viewBtn">View</button>
                        </div>
                        <div id="viewResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Copy to clipboard function
            function copyToClipboard(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('Copied to clipboard!');
                    });
                } else {
                    // Fallback
                    let textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('Copied to clipboard!');
                }
            }

            // Upload form with progress for multiple
            $('#uploadForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let xhr = new XMLHttpRequest();
                let progressBar = $('#progressBar');
                let progress = $('#progress');
                let uploadBtn = $('#uploadBtn');
                let totalLoaded = 0;
                let totalSize = 0;

                // Calculate total size
                Array.from(document.getElementById('file').files).forEach(file => {
                    totalSize += file.size;
                });

                // Show progress
                progressBar.show();
                uploadBtn.prop('disabled', true).text('Uploading...');

                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        totalLoaded = e.loaded;
                        let percent = totalSize > 0 ? Math.round((totalLoaded / totalSize) * 100) : 0;
                        progress.css('width', percent + '%').text(percent + '%');
                    }
                });

                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        let response = JSON.parse(xhr.responseText);
                        let fileList = response.files.map(f => '<li>' + f.name + '</li>').join('');
                        $('#result').html(
                            '<div class="alert alert-success">' +
                            '<p>' + response.message + '</p>' +
                            '<p>Total size: <strong>' + response.total_size + '</strong></p>' +
                            '<p>Files: <ul class="mb-0">' + fileList + '</ul></p>' +
                            '<div class="input-group mt-2">' +
                            '<input type="text" class="form-control" id="generatedCode" value="' + response.share_code + '" readonly>' +
                            '<button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(\'' + response.share_code + '\')">Copy Code</button>' +
                            '</div>' +
                            '<a href="/view/' + response.share_code + '" class="btn btn-link">View Share</a>' +
                            '</div>'
                        ).show();
                        $('#uploadForm')[0].reset();
                    } else {
                        $('#result').html('<div class="alert alert-danger">Upload failed.</div>').show();
                    }
                    progressBar.hide();
                    progress.css('width', '0%').text('0%');
                    uploadBtn.prop('disabled', false).text('Upload & Generate Share Code');
                });

                xhr.addEventListener('error', function() {
                    $('#result').html('<div class="alert alert-danger">Upload error.</div>').show();
                    progressBar.hide();
                    uploadBtn.prop('disabled', false).text('Upload & Generate Share Code');
                });

                xhr.open('POST', '/upload');
                xhr.send(formData);
            });

            // View button
            $('#viewBtn').click(function() {
                let code = $('#shareCode').val().trim();
                let viewResult = $('#viewResult');
                if (code.length !== 6 || !/^\d{6}$/.test(code)) {
                    viewResult.html('<div class="alert alert-warning">Enter a valid 6-digit code.</div>');
                    return;
                }
                viewResult.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                $.get('/view/' + code, function(data) {
                    // In the $.get('/view/' + code, function(data) { ... }
                    let filesHtml = data.files.map(file => 
                        '<div class="card mb-2">' +
                        '<div class="card-body d-flex justify-content-between align-items-center">' +
                        '<div>' +
                        '<h6 class="card-title">' + file.name + '</h6>' +
                        '<p class="card-text mb-0"><small class="text-muted">Size: ' + file.size + '</small></p>' +
                        '</div>' +
                        '<a href="' + file.download_url + '" class="btn btn-success" download="' + file.name + '">Download</a>' +
                        '</div>' +
                        '</div>'
                    ).join('');
                    viewResult.html(
                        '<div class="card">' +
                        '<div class="card-header">' +
                        '<h6>Total Files: ' + data.files.length + ' | Total Size: ' + data.total_size + '</h6>' +
                        '<small>Uploaded: ' + data.uploaded_at + ' | Expires: ' + data.expires_at + '</small>' +
                        '</div>' +
                        '<div class="card-body">' + filesHtml + '</div>' +
                        '</div>'
                    );
                }).fail(function() {
                    viewResult.html('<div class="alert alert-danger">Invalid or expired code.</div>');
                });
            });
        });
    </script>
</body>
</html>