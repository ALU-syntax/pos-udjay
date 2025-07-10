<!DOCTYPE html>
<html>
<head>
    <title>Test Kirim Email</title>
</head>
<body>
    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('email.sendTest') }}">
        @csrf
        <label for="email">Masukkan Email:</label><br>
        <input type="email" name="email" id="email" required>
        <button type="submit">Kirim Email Test</button>
    </form>
</body>
</html>
