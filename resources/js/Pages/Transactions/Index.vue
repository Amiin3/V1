<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white pb-10">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg px-4 py-6 text-white">
      <div class="max-w-3xl mx-auto flex items-center justify-between">
        <Link href="/" class="p-2 bg-white/20 hover:bg-white/30 rounded-full transition flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
        <h1 class="text-xl font-bold">📋 Riwayat Transaksi</h1>
        <div class="w-10"></div>
      </div>
    </div>

    <div class="max-w-3xl mx-auto mt-6 px-4">
      <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow">
        <p class="font-bold">Error</p>
        <p>{{ error }}</p>
      </div>

      <div v-if="!error && transactions.length === 0" class="text-center bg-white rounded-xl shadow border p-8">
        <p class="text-gray-500">Belum ada transaksi.</p>
      </div>

      <div v-else-if="!error" class="space-y-3">
        <div v-for="(trx, idx) in transactions" :key="idx" class="bg-white rounded-xl shadow border p-4 flex items-center justify-between">
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">{{ trx.title }}</h3>
            <p class="text-xs text-gray-500">{{ trx.formated_date }}</p>
            <p class="text-xs text-gray-400">Status: {{ trx.payment_status || trx.status }}</p>
            <p class="text-xs text-gray-400">Metode: {{ trx.payment_method_label || trx.payment_method }}</p>
          </div>
          <div class="text-right">
            <p class="font-bold text-gray-800">{{ trx.price }}</p>
            <span :class="statusColor(trx.payment_status || trx.status)" class="text-xs px-2 py-1 rounded-full">
              {{ trx.payment_status || trx.status }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
const props = defineProps({
    transactions: Array,
    error: String
});

const statusColor = (status) => {
    switch (status?.toUpperCase()) {
        case 'SUCCESS': return 'bg-green-100 text-green-800';
        case 'FAILED': return 'bg-red-100 text-red-800';
        case 'REFUND-SUCCESS': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>
