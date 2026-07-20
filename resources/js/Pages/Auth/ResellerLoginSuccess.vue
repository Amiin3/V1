<template>
  <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full text-center">
      <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full mx-auto mb-4 flex items-center justify-center">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Login OTP Berhasil</h1>
      <p class="text-gray-500 mb-1">Nomor: <strong>{{ user?.number }}</strong></p>
      <p class="text-sm text-gray-400 mb-6">Sesi Anda telah tersimpan. Admin akan segera memproses.</p>
      
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <p class="text-sm text-blue-700">
          ✅ Sesi Anda sudah aktif. Silakan hubungi admin untuk melanjutkan.
        </p>
      </div>

      <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block w-full border-2 border-gray-300 text-gray-600 hover:bg-gray-50 font-semibold py-3 rounded-xl transition">
        Keluar
      </a>
      <form id="logout-form" method="POST" action="/logout" style="display:none">
        <input type="hidden" name="_token" value="">
      </form>
      
      <p class="text-xs text-gray-400 mt-6">Anda dapat menutup halaman ini.</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ user: Object });
</script>
