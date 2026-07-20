<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Reseller</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:system-ui,sans-serif}</style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-blue-50 min-h-screen">
    <div class="max-w-xl mx-auto px-4 py-8">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-700 to-blue-500 px-6 py-5 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-bold">👥 Pilih Reseller</h1>
                        <p class="text-xs opacity-70 mt-1">Pilih nomor untuk mengelola akun</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg text-xs transition">Keluar</button>
                    </form>
                </div>
            </div>
            <div class="p-6">
                @if($sessions->isEmpty())
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl">📭</div>
                        <p class="text-gray-500">Belum ada sesi reseller</p>
                        <p class="text-xs text-gray-400 mt-1">Reseller login via <a href="/login" class="text-blue-600 underline">/login</a></p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($sessions as $s)
                        <div class="border border-gray-200 rounded-2xl p-4 flex justify-between items-center hover:bg-blue-50/50 transition">
                            <div>
                                <p class="font-bold text-gray-800">{{ $s->phone_number }}</p>
                                <p class="text-xs text-gray-400">Terakhir: {{ $s->updated_at }}</p>
                            </div>
                            <form method="POST" action="{{ route('login.auto') }}">
                                @csrf
                                <input type="hidden" name="number" value="{{ $s->phone_number }}">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl text-sm transition">
                                    ➡️ Login
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
