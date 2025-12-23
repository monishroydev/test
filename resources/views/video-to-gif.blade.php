<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video to GIF Converter</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .file-input-label {
            cursor: pointer;
        }
        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            outline: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #4A90E2;
            cursor: pointer;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
        input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #4A90E2;
            cursor: pointer;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-4xl mx-auto border border-gray-200">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Video to GIF Converter</h1>
        <p class="text-center text-gray-600 mb-8">Upload a video, trim it, and create a beautiful GIF.</p>

        <!-- Upload and Preview Section -->
        <div class="mb-8">
            <label for="videoFile" class="file-input-label flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-500 transition-colors">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span class="mt-2 text-gray-600 font-medium">Click to upload a video</span>
                <input type="file" id="videoFile" accept="video/*" class="hidden">
            </label>
            
            <!-- Video Preview -->
            <video id="preview-video" class="w-full rounded-xl mt-4 hidden shadow-md border border-gray-300" controls></video>
        </div>

        <!-- Conversion Options Section -->
        <div id="options-section" class="mb-8 hidden">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Conversion Settings</h2>
            
            <!-- Start Time and Duration Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="startTime" class="block text-sm font-medium text-gray-700">Start Time (seconds)</label>
                    <input type="number" id="startTime" name="startTime" value="0" min="0" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700">Duration (seconds)</label>
                    <input type="number" id="duration" name="duration" value="3" min="1" max="10" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>

            <!-- Frame Selection Slider -->
            <div class="mb-6">
                <label for="trimSlider" class="block text-sm font-medium text-gray-700 mb-2">Trim Video Segment</label>
                <input type="range" id="trimSlider" name="trimSlider" min="0" value="0" step="0.1">
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span id="currentTimeDisplay">0.00s</span>
                    <span id="totalDurationDisplay">0.00s</span>
                </div>
            </div>

            <!-- GIF Properties -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="gifWidth" class="block text-sm font-medium text-gray-700">GIF Width (px)</label>
                    <input type="number" id="gifWidth" name="gifWidth" value="320" min="100" max="1920" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="fps" class="block text-sm font-medium text-gray-700">Frames Per Second (FPS)</label>
                    <input type="number" id="fps" name="fps" value="10" min="1" max="30" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>

            <!-- Convert Button -->
            <button id="convertBtn" class="mt-8 w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:scale-105">
                <span id="button-text">Convert to GIF</span>
                <svg id="loading" class="animate-spin -mr-1 ml-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </button>
        </div>

        <!-- GIF Preview Section -->
        <div id="gif-section" class="mt-8 hidden">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Your GIF is Ready!</h2>
            <img id="gif-preview" class="w-full rounded-xl shadow-md border border-gray-300" src="" alt="Converted GIF">
            <a id="downloadLink" href="#" download class="mt-4 w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-blue-600 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:scale-105">
                Download GIF
            </a>
        </div>

        <!-- Error Message Box -->
        <div id="message-box" class="mt-4 p-4 rounded-xl bg-red-100 text-red-700 border border-red-200 hidden">
            <p id="message-text" class="font-medium"></p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const videoFile = $('#videoFile');
            const previewVideo = $('#preview-video');
            const optionsSection = $('#options-section');
            const trimSlider = $('#trimSlider');
            const startTimeInput = $('#startTime');
            const durationInput = $('#duration');
            const currentTimeDisplay = $('#currentTimeDisplay');
            const totalDurationDisplay = $('#totalDurationDisplay');
            const convertBtn = $('#convertBtn');
            const buttonText = $('#button-text');
            const loading = $('#loading');
            const gifSection = $('#gif-section');
            const gifPreview = $('#gif-preview');
            const downloadLink = $('#downloadLink');
            const messageBox = $('#message-box');
            const messageText = $('#message-text');

            function showMessage(message, type = 'error') {
                messageText.text(message);
                messageBox.removeClass('hidden bg-red-100 text-red-700 bg-green-100 text-green-700').addClass(
                    type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
                ).slideDown();
            }

            // Handles video file selection and updates UI
            videoFile.on('change', function() {
                const file = this.files[0];
                if (!file) {
                    previewVideo.hide();
                    optionsSection.hide();
                    gifSection.hide();
                    return;
                }

                const videoUrl = URL.createObjectURL(file);
                previewVideo.attr('src', videoUrl).show();
                optionsSection.hide();
                gifSection.hide();
                messageBox.slideUp();

                // Wait for the video to load metadata to get the duration
                previewVideo.on('loadedmetadata', function() {
                    const videoDuration = this.duration;
                    trimSlider.attr('max', videoDuration);
                    totalDurationDisplay.text(videoDuration.toFixed(2) + 's');
                    
                    // Reset inputs
                    startTimeInput.val(0);
                    durationInput.val(Math.min(3, videoDuration).toFixed(1));
                    trimSlider.val(0);
                    currentTimeDisplay.text('0.00s');

                    optionsSection.slideDown();
                });
            });

            // Sync the video time with the slider and update the start time input
            trimSlider.on('input', function() {
                const newTime = parseFloat($(this).val());
                previewVideo[0].currentTime = newTime;
                startTimeInput.val(newTime.toFixed(1));
                currentTimeDisplay.text(newTime.toFixed(2) + 's');
            });
            
            // Sync the slider with the video when it's playing
            previewVideo.on('timeupdate', function() {
                trimSlider.val(this.currentTime);
                currentTimeDisplay.text(this.currentTime.toFixed(2) + 's');
            });

            // Prevent video from playing past the selected duration
            let isUserSeeking = false;
            previewVideo.on('seeking', function() {
                isUserSeeking = true;
            });
            previewVideo.on('seeked', function() {
                isUserSeeking = false;
            });
            previewVideo.on('timeupdate', function() {
                if (!isUserSeeking) {
                    const currentTime = this.currentTime;
                    const startTime = parseFloat(startTimeInput.val());
                    const duration = parseFloat(durationInput.val());
                    if (currentTime > startTime + duration) {
                        this.pause();
                        this.currentTime = startTime; // Loop back to the start
                    }
                }
            });

            // Handle the AJAX conversion request
            convertBtn.on('click', function() {
                const file = videoFile[0].files[0];
                if (!file) {
                    showMessage('Please select a video file first.');
                    return;
                }

                // Check if the duration is valid
                const startTime = parseFloat(startTimeInput.val());
                const duration = parseFloat(durationInput.val());
                const videoDuration = previewVideo[0].duration;
                if (startTime + duration > videoDuration) {
                    showMessage('The selected segment goes beyond the video duration.');
                    return;
                }

                // UI state: disable button and show loading
                convertBtn.prop('disabled', true);
                buttonText.text('Converting...');
                loading.removeClass('hidden');
                gifSection.slideUp();
                messageBox.slideUp();

                const formData = new FormData();
                formData.append('video', file);
                formData.append('startTime', startTime);
                formData.append('duration', duration);
                formData.append('gifWidth', $('#gifWidth').val());
                formData.append('fps', $('#fps').val());
                
                // Send the AJAX request to the Laravel API route
                $.ajax({
                    url: '/api/convert-to-gif',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // UI state: show success message and GIF
                            showMessage('Conversion successful!', 'success');
                            gifPreview.attr('src', response.gifUrl);
                            downloadLink.attr('href', response.gifUrl);
                            gifSection.slideDown();
                            
                            // UI state: hide loading and re-enable button
                            loading.addClass('hidden');
                            buttonText.text('Convert to GIF');
                            convertBtn.prop('disabled', false);

                        } else {
                            // UI state: show error message
                            showMessage('Conversion failed: ' + response.message);
                            loading.addClass('hidden');
                            buttonText.text('Convert to GIF');
                            convertBtn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        // UI state: show generic error
                        showMessage('An error occurred. Please ensure your video file is valid and try again.');
                        console.error(xhr.responseText);
                        loading.addClass('hidden');
                        buttonText.text('Convert to GIF');
                        convertBtn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>
