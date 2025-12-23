<!-- resources/views/bg-remover.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Background Remover</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="text-center mb-4">Image Background Remover</h2>

    <form id="bgRemoveForm" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <input type="file" class="form-control" name="image" required>
        </div>
        <button type="submit" class="btn btn-primary">Remove Background</button>
    </form>

    <div class="mt-4" id="result" style="display: none;">
        <h4>Result:</h4>
        <img id="outputImage" src="" alt="Result Image" class="img-fluid rounded shadow">
        <a id="downloadBtn" class="btn btn-success mt-3" href="#" download>Download</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $('#bgRemoveForm').on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('remove.background') }}",
            method: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#result').hide();
            },
            success: function (res) {
                if (res.status === 'success') {
                    $('#outputImage').attr('src', res.image);
                    $('#downloadBtn').attr('href', res.image);
                    $('#result').show();
                }
            },
            error: function () {
                alert('Something went wrong. Try again.');
            }
        });
    });
</script>
</body>
</html>
