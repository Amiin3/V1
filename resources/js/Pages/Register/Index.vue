<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <h1 class="text-lg font-bold">📝 Register (Dukcapil)</h1>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-4">
      <div class="bg-white rounded-2xl shadow p-5 space-y-4">
        <p class="text-sm text-gray-600">Masukkan data untuk registrasi via Dukcapil.</p>
        <div>
          <label class="text-sm font-medium text-gray-700">MSISDN</label>
          <input v-model="form.msisdn" placeholder="628xxxxxxxxxx" class="w-full border p-3 rounded-xl mt-1 text-sm">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">NIK (16 digit)</label>
          <input v-model="form.nik" maxlength="16" placeholder="NIK" class="w-full border p-3 rounded-xl mt-1 text-sm">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">KK (16 digit)</label>
          <input v-model="form.kk" maxlength="16" placeholder="KK" class="w-full border p-3 rounded-xl mt-1 text-sm">
        </div>
        <button @click="submit" :disabled="loading" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
          {{ loading ? 'Memproses...' : 'Registrasi' }}
        </button>
      </div>

      <!-- Hasil -->
      <div v-if="result" class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Hasil Registrasi</h3>
        <pre class="text-xs bg-gray-100 p-3 rounded-xl overflow-x-auto">{{ JSON.stringify(result, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const form = ref({ msisdn: '', nik: '', kk: '' });
const loading = ref(false);
const result = ref(null);

const submit = async () => {
  if (!form.value.msisdn.startsWith('628')) { alert('MSISDN harus diawali 628.'); return; }
  if (form.value.nik.length !== 16) { alert('NIK harus 16 digit.'); return; }
  if (form.value.kk.length !== 16) { alert('KK harus 16 digit.'); return; }
  if (!confirm('Kirim data Dukcapil?')) return;

  loading.value = true;
  try {
    const res = await axios.post('/register/dukcapil', form.value);
    result.value = res.data;
  } catch (e) {
    result.value = { error: e.response?.data?.error || e.message };
  }
  loading.value = false;
};
</script>
