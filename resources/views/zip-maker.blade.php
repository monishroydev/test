<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZIP File Maker</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Upload Multiple Files & Download as ZIP</h4>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="files" class="form-label">Select Files (Multiple Allowed)</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple required>
                            </div>
                            <button type="submit" class="btn btn-primary">Create & Download ZIP</button>
                        </form>
                        <div id="progress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%;">Uploading...</div>
                            </div>
                        </div>
                        <div id="message" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var messageDiv = $('#message');
                
                var files = $('#files')[0].files;
                if (files.length === 0) {
                    e.preventDefault();
                    $('#message').html('<div class="alert alert-warning">Please select at least one file.</div>');
                    return;
                }
                
                messageDiv.html('<div class="alert alert-info">Uploading and creating ZIP...</div>');
                
                fetch('/upload-and-zip', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Upload failed: ' + response.statusText);
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'uploaded_files.zip';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    messageDiv.html('<div class="alert alert-success">ZIP downloaded successfully!</div>');
                    $('#uploadForm')[0].reset();
                })
                .catch(error => {
                    messageDiv.html('<div class="alert alert-danger">Error: ' + error.message + '</div>');
                });
            });
        });
    </script>
</body>
</html>