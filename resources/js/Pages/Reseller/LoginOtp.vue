<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        MILASTORE Reseller
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Login untuk mengamankan sesi transaksi
      </p>
    </div>
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <!-- Step 1: Input Nomor HP -->
        <div v-if="step === 1" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nomor HP (08xxx)</label>
            <div class="mt-1">
              <input v-model="phone" type="text" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-800 focus:border-blue-800 sm:text-sm" placeholder="Contoh: 081234567890">
            </div>
          </div>
          <button @click="requestOtp" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 disabled:opacity-50">
            {{ loading ? 'Mengirim...' : 'Kirim OTP' }}
          </button>
        </div>
        
        <!-- Step 2: Input Kode OTP -->
        <div v-if="step === 2" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Masukkan OTP</label>
            <div class="mt-1">
              <input v-model="otp" type="text" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-800 focus:border-blue-800 sm:text-sm" placeholder="Ketik OTP di sini">
            </div>
          </div>
          <button @click="verifyOtp" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-700 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-700 disabled:opacity-50">
            {{ loading ? 'Memverifikasi...' : 'Verifikasi & Simpan Sesi' }}
          </button>
        </div>

        <!-- Pesan Alert -->
        <div v-if="message" class="mt-4 p-3 rounded-md text-sm text-center font-medium" :class="isError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">
          {{ message }}
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import axios from 'axios';

const step = ref(1);
const phone = ref('');
const otp = ref('');
const loading = ref(false);
const message = ref('');
const isError = ref(false);

const requestOtp = async () => {
  if(!phone.value) return;
  loading.value = true;
  message.value = '';
  try {
    const res = await axios.post('/reseller/request-otp', { phone: phone.value });
    step.value = 2;
    message.value = res.data.message;
    isError.value = false;
  } catch (e) {
    message.value = e.response?.data?.message || 'Gagal mengirim OTP. Pastikan nomor valid.';
    isError.value = true;
  }
  loading.value = false;
};

const verifyOtp = async () => {
  if(!otp.value) return;
  loading.value = true;
  message.value = '';
  try {
    const res = await axios.post('/reseller/verify-otp', { phone: phone.value, otp: otp.value });
    message.value = res.data.message;
    isError.value = false;
    step.value = 3; 
  } catch (e) {
    message.value = e.response?.data?.message || 'Gagal verifikasi OTP. Kode mungkin salah.';
    isError.value = true;
  }
  loading.value = false;
};
</script>
