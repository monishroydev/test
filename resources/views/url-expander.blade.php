<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Short Preview Tool - Expand Redirects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .tool-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            margin-bottom: 30px;
        }
        .tool-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .tool-header h1 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .tool-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .input-group-custom {
            margin-bottom: 20px;
        }
        .btn-expand {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            font-weight: bold;
            color: white;
            transition: transform 0.2s;
        }
        .btn-expand:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-expand:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .result-container {
            display: none;
            margin-top: 30px;
        }
        .redirect-chain {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .redirect-item {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        .redirect-item:last-child {
            border-left-color: #28a745;
            margin-bottom: 0;
        }
        .redirect-number {
            position: absolute;
            left: -12px;
            top: 15px;
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .redirect-item:last-child .redirect-number {
            background: #28a745;
        }
        .url-text {
            word-break: break-all;
            font-family: 'Courier New', monospace;
            color: #495057;
            margin-left: 20px;
        }
        .final-url {
            background: #d4edda;
            border-color: #28a745;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .final-url h5 {
            color: #28a745;
            margin-bottom: 10px;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .stats-box {
            background: #e7f3ff;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }
        .copy-btn {
            cursor: pointer;
            color: #667eea;
            margin-left: 10px;
            transition: color 0.2s;
        }
        .copy-btn:hover {
            color: #764ba2;
        }
        .info-section {
            background: #fff3cd;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .info-section h6 {
            color: #856404;
            margin-bottom: 10px;
        }
        .info-section ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .info-section li {
            color: #856404;
            margin-bottom: 5px;
        }
        .api-config {
            background: #d1ecf1;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .api-config input {
            margin-top: 10px;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="main-container">
        <div class="tool-card">
            <div class="tool-header">
                <h1><i class="fas fa-link"></i> URL Short Preview Tool</h1>
                <p>Expand shortened URLs and preview redirect chains</p>
            </div>

            <div class="input-group-custom">
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="fas fa-external-link-alt"></i></span>
                    <input type="text" class="form-control" id="urlInput" placeholder="Enter shortened URL (e.g., bit.ly/xxx, tinyurl.com/xxx)">
                </div>
            </div>

            <div class="text-center">
                <button class="btn btn-expand btn-lg" id="expandBtn">
                    <i class="fas fa-expand-arrows-alt"></i> Expand URL
                </button>
            </div>

            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analyzing redirect chain...</p>
            </div>

            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-triangle"></i> <span id="errorText"></span>
            </div>

            <div class="result-container" id="resultContainer">
                <div class="stats-box">
                    <div class="stat-item">
                        <div class="stat-value" id="redirectCount">0</div>
                        <div class="stat-label">Total Redirects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="loadTime">0ms</div>
                        <div class="stat-label">Load Time</div>
                    </div>
                </div>

                <div class="redirect-chain" id="redirectChain">
                    <h5><i class="fas fa-route"></i> Redirect Chain:</h5>
                    <div id="chainContent"></div>
                </div>

                <div class="final-url">
                    <h5><i class="fas fa-check-circle"></i> Final Destination URL:</h5>
                    <div class="url-text">
                        <strong id="finalUrl"></strong>
                        <i class="fas fa-copy copy-btn" id="copyBtn" title="Copy to clipboard"></i>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h6><i class="fas fa-info-circle"></i> How to use:</h6>
                <ul>
                    <li>Make sure your Laravel backend is running (php artisan serve)</li>
                    <li>Configure the API URL above if using a different endpoint</li>
                    <li>Paste any shortened URL (bit.ly, tinyurl, goo.gl, etc.)</li>
                    <li>Click "Expand URL" to see the complete redirect chain</li>
                    <li>View all intermediate redirects and the final destination</li>
                    <li>Copy the final URL with one click</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
$(document).ready(function () {

    $('#expandBtn').on('click', expandURL);

    $('#urlInput').on('keypress', function (e) {
        if (e.which === 13) {
            expandURL();
        }
    });

    $('#copyBtn').on('click', function () {
        const finalUrl = $('#finalUrl').text();
        navigator.clipboard.writeText(finalUrl).then(() => {
            const icon = $('#copyBtn');
            icon.removeClass('fa-copy').addClass('fa-check');
            setTimeout(() => {
                icon.removeClass('fa-check').addClass('fa-copy');
            }, 2000);
        });
    });

    function expandURL() {
        let url = $('#urlInput').val().trim();

        if (!url) {
            showError('Please enter a URL');
            return;
        }

        // Add protocol if missing
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            url = 'https://' + url;
        }

        if (!isValidURL(url)) {
            showError('Invalid URL format');
            return;
        }

        $('#errorMessage').hide();
        $('#resultContainer').hide();
        $('#loadingSpinner').show();
        $('#expandBtn').prop('disabled', true);

        $.ajax({
            url: "{{ route('expand-url.expand') }}",
            type: "POST",
            data: JSON.stringify({ url: url }),
            contentType: "application/json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function (res) {
                if (res.success) {
                    displayResults(res.data);
                } else {
                    showError(res.message || 'Failed to expand URL');
                }
            },
            error: function (xhr) {
                showError(
                    xhr.responseJSON?.message ||
                    'Server error. Check Laravel route & controller.'
                );
            },
            complete: function () {
                $('#loadingSpinner').hide();
                $('#expandBtn').prop('disabled', false);
            }
        });
    }

    function displayResults(data) {
        $('#redirectCount').text(data.redirect_count);
        $('#loadTime').text(data.load_time + 'ms');

        let html = '';
        data.redirects.forEach((url, i) => {
            html += `
                <div class="redirect-item">
                    <div class="redirect-number">${i + 1}</div>
                    <div class="url-text">${escapeHtml(url)}</div>
                </div>
            `;
        });

        $('#chainContent').html(html);
        $('#finalUrl').text(data.final_url);
        $('#resultContainer').fadeIn();
    }

    function showError(msg) {
        $('#errorText').text(msg);
        $('#errorMessage').fadeIn();
    }

    function isValidURL(str) {
        try {
            new URL(str);
            return true;
        } catch {
            return false;
        }
    }

    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[m]);
    }
});
</script>

</body>
</html>