<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <h1 class="text-lg font-bold">✅ Validasi Nomor</h1>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-4">
      <div class="bg-white rounded-2xl shadow p-5 space-y-4">
        <p class="text-sm text-gray-600">Cek status nomor (family plan, registrasi, dll).</p>
        <input v-model="msisdn" placeholder="628xxxxxxxxxx" class="w-full border p-3 rounded-xl text-sm">
        <button @click="validate" :disabled="loading" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
          {{ loading ? 'Memeriksa...' : 'Validasi' }}
        </button>
      </div>

      <div v-if="result" class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Hasil</h3>
        <pre class="text-xs bg-gray-100 p-3 rounded-xl overflow-x-auto">{{ JSON.stringify(result, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const msisdn = ref('');
const loading = ref(false);
const result = ref(null);

const validate = async () => {
  if (!msisdn.value.startsWith('628')) { alert('Nomor harus diawali 628.'); return; }
  loading.value = true;
  try {
    const res = await axios.post('/validate/check', { msisdn: msisdn.value });
    result.value = res.data;
  } catch (e) {
    result.value = { error: e.response?.data?.error || e.message };
  }
  loading.value = false;
};
</script>
