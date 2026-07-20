<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white pb-10">
    <!-- Header Profil -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg px-4 py-6 text-white">
      <div class="max-w-3xl mx-auto flex items-center justify-between">
        <Link href="/" class="p-2 bg-white/20 hover:bg-white/30 rounded-full transition flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
        <div class="text-center flex-1">
          <h1 class="text-2xl font-bold tracking-tight">📦 Paket Saya</h1>
          <p class="text-sm opacity-90 mt-1">{{ profile.number }} • {{ profile.type }}</p>
        </div>
        <div class="w-10"></div> <!-- spacer -->
      </div>
    </div>

    <div class="max-w-3xl mx-auto mt-6 px-4">
      <!-- Pesan Error -->
      <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow flex items-start space-x-3">
        <svg class="h-5 w-5 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <p class="font-bold">Terjadi Kesalahan</p>
          <p class="text-sm">{{ error }}</p>
        </div>
      </div>

      <!-- Tidak ada paket -->
      <div v-if="!error && quotas.length === 0" class="text-center bg-white rounded-xl shadow border p-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        <p class="mt-2 text-gray-500">Tidak ada paket aktif.</p>
      </div>

      <!-- List Paket -->
      <div v-else-if="!error" class="space-y-6">
        <div v-for="(quota, index) in quotas" :key="quota.quota_code" class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden transition hover:shadow-lg">
          <!-- Header Paket -->
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-5 py-4 text-white">
            <h2 class="font-bold text-lg">Paket {{ index + 1 }}: {{ quota.name }}</h2>
            <p class="text-sm opacity-80 mt-1">Group: {{ quota.group_name }}</p>
          </div>

          <!-- Benefits -->
          <div class="p-5 space-y-3">
            <h3 class="font-semibold text-gray-700 text-sm flex items-center space-x-2">
              <svg class="h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
              <span>Rincian Kuota</span>
            </h3>
            <div v-for="benefit in quota.benefits" :key="benefit.id" class="bg-gray-50 rounded-xl p-3 border border-gray-100">
              <div class="flex justify-between items-baseline mb-2">
                <span class="font-medium text-gray-800 text-sm">{{ benefit.name }}</span>
                <span class="text-xs text-gray-500 font-mono">{{ benefit.data_type }}</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="progressBarColor(benefit.data_type)"
                    :style="{ width: percentage(benefit.remaining, benefit.total) + '%' }"
                  ></div>
                </div>
                <span class="text-xs font-bold text-gray-600 w-24 text-right">
                  {{ formatQuota(benefit.remaining, benefit.data_type) }} / {{ formatQuota(benefit.total, benefit.data_type) }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-1 text-right">
                {{ percentage(benefit.remaining, benefit.total) }}% tersisa
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
const props = defineProps({
    quotas: { type: Array, default: () => [] },
    error: String,
    profile: { type: Object, default: () => ({ number: '', type: '' }) }
});

const formatQuota = (amount, type) => {
    if (amount === undefined || amount === null) return '0';
    if (type === 'DATA') {
        if (amount >= 1073741824) return (amount / 1073741824).toFixed(2) + ' GB';
        if (amount >= 1048576) return (amount / 1048576).toFixed(2) + ' MB';
        if (amount >= 1024) return (amount / 1024).toFixed(2) + ' KB';
        return amount + ' Bytes';
    } else if (type === 'VOICE') {
        return (amount / 60).toFixed(0) + ' mnt';
    } else if (type === 'TEXT') {
        return amount + ' SMS';
    }
    return amount;
};

const percentage = (remaining, total) => {
    if (!total || total === 0) return 0;
    return Math.min(100, Math.round((remaining / total) * 100));
};

const progressBarColor = (type) => {
    switch (type) {
        case 'DATA': return 'bg-green-500';
        case 'VOICE': return 'bg-blue-500';
        case 'TEXT': return 'bg-orange-500';
        default: return 'bg-purple-500';
    }
};
</script>
