<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <h1 class="text-lg font-bold">⭐ Bookmark Paket</h1>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-3">
      <div v-if="bookmarks.length === 0" class="text-center bg-white rounded-2xl shadow p-6 text-gray-500">
        Tidak ada bookmark tersimpan.
      </div>

      <div v-for="(bm, idx) in bookmarks" :key="bm.id" class="bg-white rounded-2xl shadow p-4 flex justify-between items-center">
        <div class="flex-1">
          <p class="font-semibold text-gray-800">{{ idx+1 }}. {{ bm.family_name }}</p>
          <p class="text-sm text-gray-600">{{ bm.variant_name }} - {{ bm.option_name }}</p>
          <p class="text-xs text-gray-400">Order: {{ bm.order }}</p>
        </div>
        <div class="flex space-x-2">
          <Link :href="`/bookmarks/detail/${bm.family_code}/${bm.variant_name}/${bm.order}`" class="text-xs bg-blue-100 text-blue-700 px-3 py-1.5 rounded-full">Lihat</Link>
          <button @click="confirmRemove(bm)" class="text-xs bg-red-100 text-red-700 px-3 py-1.5 rounded-full">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ bookmarks: Array });

const confirmRemove = (bm) => {
  if (!confirm(`Hapus bookmark ${bm.family_name} - ${bm.variant_name}?`)) return;
  axios.post('/bookmarks/remove', {
    family_code: bm.family_code,
    is_enterprise: bm.is_enterprise,
    variant_name: bm.variant_name,
    order: bm.order,
  }).then(() => location.reload()).catch(e => alert('Gagal: ' + e.message));
};
</script>
