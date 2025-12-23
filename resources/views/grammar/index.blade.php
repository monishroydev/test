<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Grammar Correction Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Grammar Correction Tool</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Input Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Original Text</h2>
                <textarea 
                    id="inputText" 
                    class="w-full h-96 p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your text here to check grammar..."
                ></textarea>
                
                <div class="mt-4 flex items-center gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="detailedMode" class="mr-2">
                        <span class="text-sm">Show detailed changes</span>
                    </label>
                </div>
                
                <button 
                    id="correctBtn"
                    class="mt-4 w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                    Correct Grammar
                </button>
                
                <div id="stats" class="mt-4 text-sm text-gray-600 hidden"></div>
            </div>
            
            <!-- Output Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Corrected Text</h2>
                <div 
                    id="outputText" 
                    class="w-full h-96 p-4 border border-gray-300 rounded-lg bg-gray-50 overflow-auto"
                >
                    <p class="text-gray-400">Corrected text will appear here...</p>
                </div>
                
                <div id="changes" class="mt-4 hidden">
                    <h3 class="font-semibold mb-2">Changes Made:</h3>
                    <ul id="changesList" class="list-disc list-inside text-sm text-gray-700"></ul>
                </div>
                
                <button 
                    id="copyBtn"
                    class="mt-4 w-full bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700 transition hidden"
                >
                    Copy to Clipboard
                </button>
            </div>
        </div>
        
        <!-- Loading Spinner -->
        <div id="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg p-8 flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-gray-700">Correcting grammar...</p>
            </div>
        </div>
        
        <!-- Error Message -->
        <div id="error" class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded hidden"></div>
    </div>

    <script>
        const correctBtn = document.getElementById('correctBtn');
        const copyBtn = document.getElementById('copyBtn');
        const inputText = document.getElementById('inputText');
        const outputText = document.getElementById('outputText');
        const loading = document.getElementById('loading');
        const error = document.getElementById('error');
        const stats = document.getElementById('stats');
        const changes = document.getElementById('changes');
        const changesList = document.getElementById('changesList');
        const detailedMode = document.getElementById('detailedMode');

        correctBtn.addEventListener('click', async () => {
            const text = inputText.value.trim();
            
            if (!text) {
                showError('Please enter some text to correct.');
                return;
            }

            loading.classList.remove('hidden');
            error.classList.add('hidden');
            correctBtn.disabled = true;

            try {
                const response = await fetch('{{ route('grammar.correct') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        text: text,
                        detailed: detailedMode.checked
                    })
                });

                const data = await response.json();

                if (data.success) {
                    outputText.textContent = data.corrected;
                    copyBtn.classList.remove('hidden');
                    
                    // Show stats
                    let statsText = '';
                    if (data.tokens_used) {
                        statsText += `Tokens used: ${data.tokens_used}`;
                    }
                    if (data.chunks_processed) {
                        statsText += ` | Chunks: ${data.chunks_processed}`;
                    }
                    if (statsText) {
                        stats.textContent = statsText;
                        stats.classList.remove('hidden');
                    }
                    
                    // Show changes if detailed mode
                    if (data.changes && data.changes.length > 0) {
                        changesList.innerHTML = data.changes.map(change => 
                            `<li>${change}</li>`
                        ).join('');
                        changes.classList.remove('hidden');
                    } else {
                        changes.classList.add('hidden');
                    }
                } else {
                    showError(data.error || 'Failed to correct grammar');
                }
            } catch (err) {
                showError('Network error: ' + err.message);
            } finally {
                loading.classList.add('hidden');
                correctBtn.disabled = false;
            }
        });

        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(outputText.textContent);
            copyBtn.textContent = 'Copied!';
            setTimeout(() => {
                copyBtn.textContent = 'Copy to Clipboard';
            }, 2000);
        });

        function showError(message) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
    </script>
</body>
</html>