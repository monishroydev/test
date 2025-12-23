<!DOCTYPE html>
<html>

<head>
    <title>Image Converter</title>
</head>

<body>
    <h2>Image Converter</h2>
    @if (session('success'))
        <p>{{ session('success') }}</p>
        <p><a href="{{ session('converted_image') }}" target="_blank">Download Converted Image</a></p>
    @endif

    <form action="{{ route('image.convert') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label>Upload Image:</label>
        <input type="file" name="image" required>
        <br><br>

        <label>Select Format:</label>
        <select name="format" required>
            <option value="webp">WEBP</option>
            <option value="jpg">JPG</option>
            <option value="jpeg">JPEG</option>
            <option value="png">PNG</option>
            <option value="bmp">BMP</option>
            <option value="gif">GIF</option>
        </select>
        <br><br>

        <button type="submit">Convert</button>
    </form>
</body>

</html>
