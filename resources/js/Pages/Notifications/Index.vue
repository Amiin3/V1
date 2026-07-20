<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div>
        <h1 class="text-lg font-bold">Notifikasi</h1>
        <p class="text-xs opacity-80">{{ unreadCount }} belum dibaca</p>
      </div>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-3">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>

      <!-- Tombol Tandai Semua Dibaca -->
      <div v-if="unreadCount > 0" class="text-right">
        <button @click="readAll" :disabled="reading" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ reading ? 'Memproses...' : 'Tandai Semua Dibaca' }}
        </button>
      </div>

      <!-- Daftar Notifikasi -->
      <div v-if="notifications.length === 0 && !error" class="text-center bg-white rounded-2xl shadow p-6 text-gray-500">
        Tidak ada notifikasi.
      </div>

      <div v-for="(notif, idx) in notifications" :key="idx" class="bg-white rounded-2xl shadow p-4" :class="{'border-l-4 border-blue-500': !notif.is_read}">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <p class="font-semibold text-gray-800 text-sm">{{ notif.brief_message }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ notif.full_message }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ notif.timestamp }}</p>
          </div>
          <span v-if="!notif.is_read" class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full ml-2">BARU</span>
          <span v-else class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full ml-2">DIBACA</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  notifications: Array,
  unreadCount: Number,
  error: String,
});

const reading = ref(false);
const readAll = async () => {
  if (!confirm('Tandai semua notifikasi sebagai dibaca?')) return;
  reading.value = true;
  try {
    const res = await axios.post('/notifications/read-all');
    if (res.data.status === 'SUCCESS') {
      location.reload();
    } else {
      alert('Gagal: ' + (res.data.message || 'Unknown'));
    }
  } catch (e) {
    alert('Error: ' + e.message);
  }
  reading.value = false;
};
</script>
