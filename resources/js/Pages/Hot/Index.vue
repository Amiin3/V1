<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-red-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <h1 class="text-lg font-bold">🔥 Paket HOT</h1>
    </div>
    <div class="max-w-2xl mx-auto p-4 space-y-3">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>
      <div v-if="(hotList || []).length === 0 && !error" class="text-center bg-white rounded-2xl shadow p-6 text-gray-500">Tidak ada paket HOT.</div>
      <div v-for="(item, idx) in (hotList || [])" :key="idx" class="bg-white rounded-2xl shadow p-4">
        <p class="font-bold text-gray-800">{{ item.family_name }} - {{ item.variant_name }}</p>
        <p class="text-sm text-gray-600">{{ item.option_name }}</p>
        <!-- HANYA INI YANG DIUBAH: variant_name -> variant_code agar URL valid -->
        <Link :href="`/packages/detail/${item.family_code}/${item.variant_code || item.variant_name}/${item.order}`" class="mt-2 inline-block bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">Lihat Detail</Link>
      </div>
    </div>
  </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ hotList: Array, error: String });
</script>
