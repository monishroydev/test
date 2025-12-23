<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap Generator Tool</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Optional: Custom styles -->
    <style>
        body { background-color: #f8f9fa; }
        .progress { height: 25px; }
        #debugLogs { max-height: 200px; overflow-y: auto; }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">  <!-- Wider for logs -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Sitemap Generator Tool</h4>
                    </div>
                    <div class="card-body">
                        <form id="sitemapForm">
                            <div class="mb-3">
                                <label for="url" class="form-label">Enter Website URL</label>
                                <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" placeholder="https://getbootstrap.com" required value="{{ old('url') }}">
                                @error('url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="generateBtn">
                                Generate Sitemap
                            </button>
                        </form>
                        
                        <!-- Progress Bar -->
                        <div id="progressContainer" class="mt-3" style="display: none;">
                            <div class="progress mb-2" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
                            </div>
                            <small id="progressText" class="text-muted">Starting crawl...</small>
                        </div>
                        
                        <!-- Debug Logs Section -->
                        <div class="mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="showLogs" checked>
                                <label class="form-check-label" for="showLogs">Show Debug Logs</label>
                            </div>
                            <div id="debugLogsContainer" class="mt-2" style="display: {{ config('app.debug') ? 'block' : 'none' }};">
                                <h6>Debug Logs:</h6>
                                <div id="debugLogs" class="border p-2 bg-light" style="font-family: monospace; font-size: 0.8em;"></div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3" role="alert">
                            <small><strong>Tip:</strong> For full crawls, test with multi-page sites like <a href="https://getbootstrap.com" target="_blank">getbootstrap.com</a>. Single-page sites (e.g., example.com) will only show 1 URL. Check logs below for details.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    $(document).ready(function() {
        let pollInterval;
        let crawlId = null;

        $('#showLogs').on('change', function() {
            $('#debugLogsContainer').toggle(this.checked);
        });

        $('#sitemapForm').on('submit', function(e) {
            e.preventDefault();
            
            var url = $('#url').val();
            var btn = $('#generateBtn');
            
            if (!url || !url.startsWith('http')) {
                alert('Please enter a valid URL starting with http/https.');
                return false;
            }
            
            btn.prop('disabled', true).text('Starting...');
            $('#progressContainer').show();
            $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
            $('#progressText').text('Starting crawl...');
            $('#debugLogs').empty();
            
            crawlId = Date.now() + Math.random().toString(36).substr(2, 9);
            
            $.ajax({
                url: '{{ route("sitemap.generate") }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    url: url,
                    crawl_id: crawlId
                },
                success: function(response) {
                    const blob = new Blob([response.xml], { type: 'application/xml' });
                    const link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = 'sitemap.xml';
                    link.click();
                    window.URL.revokeObjectURL(link.href);
                    
                    $('#progressText').text('Download ready! Check sitemap.xml for URLs.');
                    resetUI();
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred during crawling.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    resetUI();
                }
            });
            
            pollProgress();
        });
        
        function pollProgress() {
            if (!crawlId) return;
            
            pollInterval = setInterval(function() {
                $.get('{{ route("sitemap.progress") }}?crawl_id=' + crawlId, function(data) {
                    if (data.progress !== undefined) {
                        $('#progressBar').css('width', data.progress + '%').attr('aria-valuenow', data.progress);
                        $('#progressText').text(data.message || 'Crawling... (' + Math.round(data.progress) + '%)');
                        
                        if (data.progress >= 100) {
                            clearInterval(pollInterval);
                        }
                    }
                    
                    if (data.logs) {
                        $('#debugLogs').html(data.logs.map(log => log.replace(/\n/g, '<br>')).join('<br>'));
                        $('#debugLogs').scrollTop($('#debugLogs')[0].scrollHeight);
                    }
                }).fail(function() {
                    clearInterval(pollInterval);
                });
            }, 1000);
        }
        
        function resetUI() {
            clearInterval(pollInterval);
            $('#generateBtn').prop('disabled', false).text('Generate Sitemap');
            // Keep progress visible briefly
            setTimeout(() => $('#progressContainer').hide(), 3000);
            if (crawlId) {
                $.get('{{ route("sitemap.clear") }}?crawl_id=' + crawlId);
                crawlId = null;
            }
        }
    });
    </script>
</body>
</html>