<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-red-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></Link>
      <h1 class="text-lg font-bold">🔥 HOT-2</h1>
    </div>
    <div class="max-w-lg mx-auto p-4 space-y-3">
      <div v-if="error" class="bg-red-50 p-4 rounded-xl text-red-700">{{ error }}</div>
      <div v-for="(item, idx) in (hotList || [])" :key="idx" class="bg-white rounded-2xl shadow p-4">
        <p class="font-bold text-gray-800">{{ item.name }}</p>
        <p class="text-sm text-gray-600">Rp {{ item.price?.toLocaleString('id-ID') }}</p>
        <button @click="buyPackage(item)" :disabled="buying" class="mt-2 bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold disabled:opacity-50">Beli</button>
      </div>
      <p v-if="msg" class="text-sm p-3 rounded-xl" :class="msgError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">{{ msg }}</p>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'; import { Link } from '@inertiajs/vue3'; import axios from 'axios';
const props = defineProps({ hotList: Array, error: String });
const buying = ref(false), msg = ref(''), msgError = ref(false);
const buyPackage = async (item) => {
  if (!confirm(`Beli ${item.name} seharga Rp ${item.price?.toLocaleString('id-ID')}?`)) return;
  buying.value = true; msg.value = '';
  try {
    const res = await axios.post('/packages/hot2/buy', { packages: item.packages || [], payment_method: 'BALANCE' });
    if (res.data.status==='SUCCESS') { msg.value='✅  Berhasil!'; msgError.value=false; }
    else { msg.value='❌  '+(res.data.error||res.data.message||'Gagal'); msgError.value=true; }
  } catch(e) { msg.value='❌  '+(e.response?.data?.error||e.message); msgError.value=true; }
  buying.value = false;
};
</script>
