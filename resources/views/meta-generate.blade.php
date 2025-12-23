<!doctype html>
<html lang="en">

<head>
    <title>SEO Meta Generator</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }
        .char-count {
            font-size: 12px;
        }
        textarea {
            resize: vertical;
        }
        pre {
            background: #111;
            color: #0f0;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            max-height: 260px;
            overflow: auto;
        }
    </style>
</head>

<body>

<div class="container py-4">
    <div class="row g-4">

        <!-- INPUT -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">SEO Meta Generator</h5>

                    <input class="form-control mb-2" id="topic" placeholder="Page title / topic">

                    <textarea class="form-control mb-2" id="keywords" rows="2"
                        placeholder="Target keywords (optional)"></textarea>

                    <select class="form-select mb-2" id="content_type">
                        <option>Blog</option>
                        <option>Tool Page</option>
                        <option>Landing Page</option>
                    </select>

                    <select class="form-select mb-3" id="tone">
                        <option>Professional</option>
                        <option>Marketing</option>
                        <option>Friendly</option>
                    </select>

                    <button id="generate" class="btn btn-primary w-100">
                        Generate Meta Data
                    </button>
                </div>
            </div>
        </div>

        <!-- OUTPUT -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Generated Result</h5>

                    <label>Meta Title</label>
                    <textarea id="meta_title" class="form-control" rows="2"></textarea>
                    <div class="char-count text-muted" id="titleCount">0 / 60</div>

                    <label class="mt-3">Meta Description</label>
                    <textarea id="meta_description" class="form-control" rows="3"></textarea>
                    <div class="char-count text-muted" id="descCount">0 / 160</div>

                    <label class="mt-3">Meta Keywords</label>
                    <textarea id="meta_keywords" class="form-control" rows="2"></textarea>

                    <label class="mt-3">Schema (JSON-LD)</label>
                    <pre id="schema_output">// Schema will appear here</pre>

                    <button class="btn btn-success w-100 mt-3" id="copyAll">
                        Copy Meta + Schema
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$('#generate').on('click', function () {

    let btn = $(this);
    btn.prop('disabled', true).text('Generating...');

    $.ajax({
        url: '/api/meta-generate',
        type: 'POST',
        data: {
            topic: $('#topic').val(),
            keywords: $('#keywords').val(),
            tone: $('#tone').val(),
            content_type: $('#content_type').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            $('#meta_title').val(res.title);
            $('#meta_description').val(res.description);
            $('#meta_keywords').val(res.keywords);
            $('#schema_output').text(res.schema);

            updateCounts();
        },
        error: function () {
            alert('Failed to generate meta data');
        },
        complete: function () {
            btn.prop('disabled', false).text('Generate Meta Data');
        }
    });
});

// Character counters
function updateCounts() {
    $('#titleCount').text($('#meta_title').val().length + ' / 60');
    $('#descCount').text($('#meta_description').val().length + ' / 160');
}

$('#meta_title, #meta_description').on('input', updateCounts);

// Copy all
$('#copyAll').on('click', function () {
    let text =
        "Title:\n" + $('#meta_title').val() + "\n\n" +
        "Description:\n" + $('#meta_description').val() + "\n\n" +
        "Keywords:\n" + $('#meta_keywords').val() + "\n\n" +
        "Schema:\n" + $('#schema_output').text();

    navigator.clipboard.writeText(text);
    alert('Copied successfully!');
});
</script>

</body>
</html>
