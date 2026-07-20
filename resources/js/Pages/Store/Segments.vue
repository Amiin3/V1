<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div class="flex-1">
        <h1 class="text-lg font-bold">Store Segments</h1>
      </div>
      <label class="flex items-center space-x-2 text-sm bg-white/20 px-3 py-1.5 rounded-full">
        <span>Enterprise</span>
        <input type="checkbox" v-model="enterprise" @change="toggleEnterprise" class="w-4 h-4">
      </label>
    </div>

    <div class="max-w-3xl mx-auto p-4 space-y-6">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>

      <!-- Segments -->
      <div v-for="(segment, segIdx) in segments" :key="segIdx" class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3 text-white font-semibold">
          {{ getSegmentLabel(segIdx) }}. {{ segment.title }}
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="(banner, banIdx) in segment.banners" :key="banIdx" class="p-4 hover:bg-gray-50 transition cursor-pointer" @click="openDetail(banner)">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <p class="font-semibold text-gray-800">{{ banner.family_name }}</p>
                <p class="text-sm text-gray-600">{{ banner.title }}</p>
                <p class="text-xs text-gray-400 mt-1">Berlaku: {{ banner.validity }}</p>
              </div>
              <div class="text-right ml-3">
                <p class="font-bold text-blue-600">Rp {{ banner.discounted_price?.toLocaleString('id-ID') }}</p>
                <span class="text-xs text-gray-400">{{ getBannerCode(segIdx, banIdx) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="segments.length === 0 && !error" class="text-center bg-white rounded-2xl shadow p-6 text-gray-500">
        Tidak ada segmen tersedia.
      </div>
    </div>

    <!-- Modal Detail Paket -->
    <div v-if="selectedBanner" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="selectedBanner = null">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <h3 class="font-bold text-lg mb-3">{{ selectedBanner.family_name }} - {{ selectedBanner.title }}</h3>
        <div class="space-y-2 text-sm text-gray-700">
          <p><span class="font-medium">Harga:</span> Rp {{ selectedBanner.discounted_price?.toLocaleString('id-ID') }}</p>
          <p><span class="font-medium">Berlaku:</span> {{ selectedBanner.validity }}</p>
          <p><span class="font-medium">Action Type:</span> {{ selectedBanner.action_type }}</p>
          <p><span class="font-medium">Action Param:</span> {{ selectedBanner.action_param }}</p>
        </div>
        <div class="mt-4 flex space-x-2">
          <button v-if="selectedBanner.action_type === 'PDP'" @click="viewPackageDetail(selectedBanner.action_param)" class="bg-blue-600 text-white px-4 py-2 rounded-xl flex-1 font-semibold">
            Lihat Detail Paket
          </button>
          <button @click="selectedBanner = null" class="bg-gray-200 px-4 py-2 rounded-xl">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  segments: Array,
  error: String,
  isEnterprise: Boolean,
});

const enterprise = ref(props.isEnterprise);
const selectedBanner = ref(null);

const toggleEnterprise = () => {
  router.get('/store/segments', { enterprise: enterprise.value ? '1' : '0' }, { preserveState: true, replace: true });
};

const getSegmentLabel = (idx) => String.fromCharCode(65 + idx); // A, B, C...
const getBannerCode = (segIdx, banIdx) => `${getSegmentLabel(segIdx).toLowerCase()}${banIdx + 1}`;

const openDetail = (banner) => {
  selectedBanner.value = banner;
};

const viewPackageDetail = (optionCode) => {
  // Untuk sementara redirect ke halaman package detail (bisa disesuaikan)
  alert('Fitur detail paket akan segera hadir. Kode: ' + optionCode);
};
</script>
