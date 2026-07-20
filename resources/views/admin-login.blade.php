<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center min-h-screen p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">🔐 Admin Login</h1>
        
        @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded mb-4 text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login.admin.submit') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
                <input type="text" name="username" class="w-full border rounded-xl p-3 text-sm" placeholder="Username" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" class="w-full border rounded-xl p-3 text-sm" placeholder="Password" required>
            </div>
            <button type="submit" class="w-full bg-gray-800 text-white py-3 rounded-xl font-semibold hover:bg-gray-700 transition">Masuk</button>
        </form>
    </div>
</body>
</html>
