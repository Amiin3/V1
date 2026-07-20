<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center p-6">
    <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl p-8 max-w-md w-full text-center">
      <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl">
        📱
      </div>
      <h1 class="text-2xl font-bold text-slate-800 mb-2">Dashboard Reseller</h1>
      <p class="text-slate-500 text-sm mb-6">Login sebagai <strong>{{ user.number }}</strong></p>
      
      <Link href="/reseller/hot" class="block w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-xl mb-3 transition">
        🔥 Beli Paket HOT
      </Link>
      
      <Link href="/logout" method="post" class="block w-full border-2 border-red-500 text-red-600 hover:bg-red-50 font-semibold py-3 rounded-xl transition">
        Keluar
      </Link>
      
      <p class="text-xs text-slate-400 mt-6">Anda dapat membeli paket untuk pelanggan</p>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ user: Object });
</script>
