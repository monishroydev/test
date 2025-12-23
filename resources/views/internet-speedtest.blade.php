<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Speed Test</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117; 
            color: #c9d1d9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .speed-tester-card {
            background-color: #161b22; 
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            max-width: 600px;
            width: 95%;
        }
        .result-box {
            background-color: #0d1117;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
            height: 100%;
        }
        .result-box:hover {
            transform: translateY(-5px);
        }
        .result-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #58a6ff; 
            margin-top: 5px;
        }
        .result-label {
            font-size: 0.9rem;
            color: #8b949e;
            text-transform: uppercase;
        }
        #startButton {
            background-color: #238636;
            border-color: #238636;
            color: white;
            font-size: 1.5rem;
            padding: 15px 40px;
            border-radius: 50px;
            transition: background-color 0.2s;
            text-transform: uppercase;
        }
        #startButton:hover {
            background-color: #2ea043;
            border-color: #2ea043;
            transform: scale(1.05);
        }
        .spinner-border {
            color: #58a6ff !important;
        }
    </style>
</head>
<body>

    <div class="speed-tester-card">
        <h1 class="text-center mb-4 text-white">Full-Stack Laravel Speed Test</h1>
        <p class="text-center text-secondary mb-5">Measures real latency, download, and upload against your Laravel server.</p>

        <!-- Results Grid -->
        <div class="row g-3 mb-5 text-center" id="resultsContainer">

            <!-- Ping Result -->
            <div class="col-4">
                <div class="result-box">
                    <div class="result-label">Latency (Ping)</div>
                    <div class="result-value" id="pingResult">--</div>
                    <div class="result-label" id="pingUnit">ms</div>
                </div>
            </div>

            <!-- Download Result -->
            <div class="col-4">
                <div class="result-box">
                    <div class="result-label">Download Speed</div>
                    <div class="result-value" id="downloadResult">--</div>
                    <div class="result-label" id="downloadUnit">Mbps</div>
                </div>
            </div>

            <!-- Upload Result -->
            <div class="col-4">
                <div class="result-box">
                    <div class="result-label">Upload Speed</div>
                    <div class="result-value" id="uploadResult">--</div>
                    <div class="result-label" id="uploadUnit">Mbps</div>
                </div>
            </div>
        </div>

        <!-- Go Button -->
        <div class="d-grid gap-2 col-8 mx-auto">
            <button id="startButton" class="btn btn-primary" type="button">GO</button>
        </div>

        <!-- Status Message -->
        <div class="text-center mt-4">
            <div id="statusMessage" class="text-info" style="min-height: 20px;"></div>
        </div>

        <!-- Notice Modal (for successful test completion) -->
        <div id="noticeModal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header border-bottom border-secondary">
                        <h5 class="modal-title text-success">Test Complete!</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="noticeBody">
                        The speed test has finished running all three measurements.
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Speed Test Logic -->
    <script>
        $(document).ready(function() {
            // --- Configuration and Constants ---
            const $startButton = $('#startButton');
            const $statusMessage = $('#statusMessage');
            
            // API Endpoints (These routes MUST be configured in routes/api.php)
            const API_BASE = '/api/speedtest';
            const PING_URL = API_BASE + '/ping';
            const DOWNLOAD_URL = API_BASE + '/download';
            const UPLOAD_URL = API_BASE + '/upload';
            
            const DOWNLOAD_RUNS = 8; 
            const UPLOAD_DATA_SIZE_MB = 4; 
            
            const BIT_PER_BYTE = 8;
            const MB_TO_BIT = 1000 * 1000;

            // --- Utility Functions ---

            function resetDisplay() {
                $('#pingResult').text('--');
                $('#downloadResult').text('--');
                $('#uploadResult').text('--');
                $statusMessage.text('');
            }
            
            function bytesPerMsToMbps(bytesPerMs) {
                return (bytesPerMs * 1000 * BIT_PER_BYTE) / MB_TO_BIT;
            }

            // 1. Ping Test (Latency)
            async function runPingTest() {
                $statusMessage.text('Testing Latency (Ping)...');
                const startTime = performance.now();

                try {
                    await fetch(PING_URL, { cache: 'no-store' });
                    const endTime = performance.now();
                    
                    const rtt = endTime - startTime;
                    const pingMs = Math.round(rtt / 2);

                    $('#pingResult').text(pingMs);
                    return pingMs;
                } catch (error) {
                    console.error("Ping Test Failed:", error);
                    $('#pingResult').text('Err');
                    throw new Error("Ping failed. Check the Laravel server connection.");
                }
            }

            // 2. Download Test (Bandwidth)
            async function runDownloadTest() {
                $statusMessage.text('Testing Download Speed...');
                let totalBytes = 0;
                let totalTimeMs = 0;

                for (let i = 0; i < DOWNLOAD_RUNS; i++) {
                    const startTime = performance.now();
                    try {
                        const response = await fetch(DOWNLOAD_URL, { cache: 'no-store' });
                        
                        if (response.status !== 200) {
                             throw new Error("Server download error. Status: " + response.status);
                        }

                        const buffer = await response.arrayBuffer();
                        const endTime = performance.now();

                        totalBytes += buffer.byteLength;
                        totalTimeMs += (endTime - startTime);

                        const currentBps = totalBytes / totalTimeMs;
                        const currentMbps = bytesPerMsToMbps(currentBps);
                        $('#downloadResult').text(currentMbps.toFixed(2));
                        $statusMessage.text(`Downloading... (${i + 1}/${DOWNLOAD_RUNS})`);

                    } catch (error) {
                        console.error(`Download run ${i+1} failed:`, error);
                        break; 
                    }
                }

                if (totalTimeMs > 0) {
                    const averageBps = totalBytes / totalTimeMs;
                    const averageMbps = bytesPerMsToMbps(averageBps);
                    $('#downloadResult').text(averageMbps.toFixed(2));
                    return averageMbps;
                } else {
                    $('#downloadResult').text('Fail');
                    throw new Error("Download test failed to measure speed.");
                }
            }
            
            // 3. Upload Test (Bandwidth)
            async function runUploadTest() {
                $statusMessage.text('Testing Upload Speed...');
                
                // Generate a large data blob
                const dataSize = UPLOAD_DATA_SIZE_MB * 1024 * 1024;
                const uploadData = new Array(dataSize + 1).join('a');
                const payloadString = JSON.stringify({ data: uploadData, size: dataSize });

                const startTime = performance.now();
                
                try {
                    const response = await fetch(UPLOAD_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            // Laravel requires CSRF token for web routes, but generally not for API routes. 
                            // If you use this on a web route, you would need to include the token.
                        },
                        body: payloadString
                    });
                    
                    await response.json(); 
                    
                    if (!response.ok) {
                         throw new Error(`Upload failed with status: ${response.status}`);
                    }

                    const endTime = performance.now();
                    const totalTimeMs = endTime - startTime;
                    const totalBytes = new TextEncoder().encode(payloadString).length; 

                    const averageBps = totalBytes / totalTimeMs;
                    const averageMbps = bytesPerMsToMbps(averageBps);
                    
                    $('#uploadResult').text(averageMbps.toFixed(2));
                    return averageMbps;

                } catch (error) {
                    console.error("Upload Test Failed:", error);
                    $('#uploadResult').text('Err');
                    throw new Error("Upload test failed. (Check PHP post_max_size and upload_max_filesize).");
                }
            }


            // --- Main Runner Function ---
            async function startTest() {
                resetDisplay();
                $startButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Testing...');

                try {
                    await runPingTest();
                    await runDownloadTest();
                    await runUploadTest();
                    
                    $statusMessage.html('<span class="text-success fw-bold">Test Complete!</span>');
                    // Show the Bootstrap Modal
                    new bootstrap.Modal(document.getElementById('noticeModal')).show(); 

                } catch (error) {
                    console.error("Overall Test Error:", error.message);
                    $statusMessage.text('Test failed: ' + error.message);
                } finally {
                    $startButton.prop('disabled', false).html('TEST AGAIN');
                }
            }

            // Attach event listener using jQuery
            $startButton.on('click', startTest);

            $statusMessage.text('Click GO to start the full-stack speed test.');
        });
    </script>
</body>
</html>