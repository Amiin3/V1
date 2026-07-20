<template>
  <div class="min-h-screen bg-gray-100 p-6 pb-20">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center space-x-4 mb-6">
        <Link href="/" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-bold shadow-sm">⬅ Kembali</Link>
        <h1 class="text-2xl font-bold text-gray-800">👪 Beli via Family Code</h1>
      </div>
      <div class="mb-6 flex space-x-4">
        <input v-model="familyCode" placeholder="Masukkan Family Code" class="flex-1 border p-3 rounded-xl text-sm">
        <button @click="cari" :disabled="loading" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold">
            {{ loading ? 'Mencari...' : 'Cari Paket' }}
        </button>
      </div>
      <div v-if="error" class="text-red-600 text-sm mb-4 bg-red-100 p-3 rounded">{{ error }}</div>
      <div v-if="packages.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="(pkg, idx) in packages" :key="idx" class="bg-white p-4 rounded-xl shadow border flex flex-col justify-between">
            <div>
                <h2 class="font-bold text-lg text-gray-800">{{ pkg.variant_name }}</h2>
                <p class="text-gray-600 text-sm mt-1">{{ pkg.option_name }}</p>
                <div class="mt-2 font-bold text-blue-600">{{ pkg.currency }} {{ pkg.price }}</div>
            </div>
            <!-- Tombol Beli ini memanggil route 'packages.detail' di web.php -->
            <Link :href="`/packages/detail/${pkg.family_code}/${pkg.variant_code}/${pkg.order}`" class="mt-4 text-center bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-semibold text-sm transition">
                Beli Paket Ini
            </Link>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const familyCode = ref('');
const error = ref('');
const packages = ref([]);
const loading = ref(false);

const cari = async () => {
    if (!familyCode.value) return;
    loading.value = true;
    error.value = '';
    packages.value = [];
    try {
        // Tembak langsung ke rute di web.php: Route::post('/packages/family-list')
        const res = await axios.post('/packages/family-list', { family_code: familyCode.value });
        if (res.data.error) {
            error.value = res.data.error;
        } else {
            packages.value = res.data.packages || [];
        }
    } catch (err) {
        error.value = err.response?.data?.error || 'Gagal mencari paket dari Provider.';
    } finally {
        loading.value = false;
    }
};
</script>
