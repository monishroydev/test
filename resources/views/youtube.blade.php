<!DOCTYPE html>
<html>
<head>
    <title>YouTube Downloader</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #progress-container {
            width: 100%; border: 1px solid #000; margin-top: 10px;
        }
        #progress-bar {
            width: 0; height: 25px; background: green;
        }
    </style>
</head>
<body>
<h1>YouTube Downloader</h1>

<form id="download-form">
    <input type="url" name="url" placeholder="Enter YouTube URL" required>
    <button type="submit">Download</button>
</form>

<div id="progress-container">
    <div id="progress-bar"></div>
</div>
<p id="status"></p>

<script>
$('#download-form').on('submit', function(e){
    e.preventDefault();
    var formData = $(this).serialize();

    $('#progress-bar').css('width','0');
    $('#status').text('Downloading...');

    $.ajax({
        url: "{{ route('download.video') }}",
        method: "POST",
        data: formData,
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.addEventListener('progress', function(evt){
                if(evt.lengthComputable){
                    var percentComplete = (evt.loaded / evt.total) * 100;
                    $('#progress-bar').css('width', percentComplete + '%');
                    $('#status').text('Downloading... '+ Math.floor(percentComplete)+'%');
                }
            }, false);
            return xhr;
        },
        success: function(response){
            $('#status').text('Download completed');
            // optionally redirect to download file
        },
        error: function(response){
            $('#status').text('Error: '+ response.responseJSON.error);
        }
    });
});
</script>

</body>
</html>
