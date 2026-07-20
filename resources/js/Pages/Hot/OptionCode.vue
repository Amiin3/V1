<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></Link>
      <h1 class="text-lg font-bold">Beli via Option Code</h1>
    </div>
    <div class="max-w-lg mx-auto p-4 space-y-4">
      <input v-model="optionCode" placeholder="Masukkan Option Code" class="w-full border p-3 rounded-xl text-sm">
      <button @click="cari" :disabled="loading" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold">Cari Paket</button>
      <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'; import { Link } from '@inertiajs/vue3'; import axios from 'axios';
const optionCode = ref(''), loading = ref(false), error = ref('');
const cari = async () => {
  loading.value = true; error.value = '';
  try {
    const res = await axios.post('/packages/detail-by-option', { option_code: optionCode.value });
    if (res.data) window.location.href = '/packages/detail-by-option?option_code=' + optionCode.value;
  } catch(e) { error.value = e.response?.data?.error || e.message; }
  loading.value = false;
};
</script>
