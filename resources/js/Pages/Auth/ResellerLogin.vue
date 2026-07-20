<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center p-6">
    <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl p-8 max-w-md w-full">
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl">
          📱
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Login OTP</h1>
        <p class="text-sm text-gray-500 mt-1">Masuk sebagai reseller</p>
      </div>

      <!-- Notifikasi Error / Sukses -->
      <div v-if="message.text" class="mb-4 p-3 rounded-lg text-sm" :class="message.type === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 'bg-red-50 border-l-4 border-red-500 text-red-700'">
        {{ message.text }}
      </div>

      <!-- Form Minta OTP -->
      <form @submit.prevent="requestOtp" class="mb-6">
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor HP</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-sm">+62</span>
            <input v-model="form.number" placeholder="8xxxxxxxxxx" class="w-full border border-gray-300 rounded-xl py-3 pl-12 pr-4 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
          </div>
        </div>
        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition disabled:opacity-50">
          {{ form.processing ? '⏳ Mengirim...' : '📩 Kirim OTP' }}
        </button>
      </form>

      <!-- Separator -->
      <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
          <span class="px-3 bg-white text-gray-400">Verifikasi</span>
        </div>
      </div>

      <!-- Form Verifikasi OTP -->
      <form @submit.prevent="login">
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Kode OTP</label>
          <input v-model="form.password" type="text" maxlength="6" placeholder="123456" class="w-full border border-gray-300 rounded-xl py-3 px-4 text-sm text-center tracking-widest focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
          <p class="text-xs text-gray-400 mt-1 text-center">Masukkan 6 digit kode OTP</p>
        </div>
        <button type="submit" :disabled="form.processing" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition disabled:opacity-50">
          {{ form.processing ? '🔐 Memverifikasi...' : '✅ Verifikasi & Masuk' }}
        </button>
      </form>

      <p class="text-xs text-gray-400 text-center mt-6">
        Login sebagai admin? <a href="/admin/login" class="text-blue-600 underline">Klik di sini</a>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ number: '', password: '' });
const message = ref({ text: '', type: '' });

const requestOtp = () => {
  message.value = { text: '', type: '' };
  if (!form.number.startsWith('62')) {
    message.value = { text: 'Nomor harus diawali dengan 62. Contoh: 62812xxxx', type: 'error' };
    return;
  }
  form.post('/login/request-otp', {
    preserveScroll: true,
    onSuccess: () => {
      message.value = { text: '✅ OTP berhasil dikirim! Silakan cek SMS Anda.', type: 'success' };
    },
    onError: (errors) => {
      message.value = { text: '❌ Gagal mengirim OTP: ' + (errors.number || 'Silakan coba lagi.'), type: 'error' };
    }
  });
};

const login = () => {
  message.value = { text: '', type: '' };
  form.post('/login', {
    onError: (errors) => {
      message.value = { text: '❌ ' + (errors.password || 'Login gagal.'), type: 'error' };
    }
  });
};
</script>
