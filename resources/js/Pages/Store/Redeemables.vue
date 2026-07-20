<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div class="flex-1">
        <h1 class="text-lg font-bold">Redeemables</h1>
      </div>
      <label class="flex items-center space-x-2 text-sm bg-white/20 px-3 py-1.5 rounded-full">
        <span>Enterprise</span>
        <input type="checkbox" v-model="enterprise" @change="toggleEnterprise" class="w-4 h-4">
      </label>
    </div>

    <div class="max-w-2xl mx-auto p-4 space-y-6">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>
      <div v-if="categories.length === 0 && !error" class="text-center bg-white rounded-2xl shadow p-6 text-gray-500">Tidak ada redeemables.</div>

      <!-- Kategori -->
      <div v-for="(cat, catIdx) in categories" :key="cat.category_code" class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-4 py-3 text-white font-semibold">
          {{ getCategoryLabel(catIdx) }}. {{ cat.category_name }}
          <span class="text-xs opacity-70 ml-2">({{ cat.category_code }})</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-if="cat.redeemables && cat.redeemables.length === 0" class="p-4 text-sm text-gray-500">
            Tidak ada item di kategori ini.
          </div>
          <div v-for="(item, itemIdx) in cat.redeemables" :key="itemIdx" class="p-4 hover:bg-gray-50 transition cursor-pointer" @click="openDetail(catIdx, itemIdx)">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <p class="font-semibold text-gray-800">{{ item.name }}</p>
                <p class="text-sm text-gray-600">Berlaku hingga: {{ formatDate(item.valid_until) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ getItemCode(catIdx, itemIdx) }}</p>
              </div>
              <div class="text-right ml-3">
                <span class="text-xs px-2 py-1 rounded-full" :class="actionBadge(item.action_type)">{{ item.action_type }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Aksi (sementara alert) -->
    <!-- Nanti bisa diisi dengan integrasi ke halaman package/family -->
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  categories: Array,
  error: String,
  isEnterprise: Boolean,
});

const enterprise = ref(props.isEnterprise);

const toggleEnterprise = () => {
  router.get('/store/redeemables', { enterprise: enterprise.value ? '1' : '0' }, { preserveState: true, replace: true });
};

const getCategoryLabel = (idx) => String.fromCharCode(65 + idx); // A, B, C...
const getItemCode = (catIdx, itemIdx) => `${getCategoryLabel(catIdx).toLowerCase()}${itemIdx + 1}`;

const formatDate = (ts) => {
  if (!ts) return '-';
  return new Date(ts * 1000).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const actionBadge = (type) => {
  switch (type) {
    case 'PLP': return 'bg-blue-100 text-blue-700';
    case 'PDP': return 'bg-green-100 text-green-700';
    default: return 'bg-gray-100 text-gray-700';
  }
};

const openDetail = (catIdx, itemIdx) => {
  const category = props.categories[catIdx];
  const item = category.redeemables[itemIdx];
  const code = getItemCode(catIdx, itemIdx);
  // Untuk sekarang alert, nanti bisa diintegrasikan ke halaman detail paket / family
  alert(`Kode: ${code}\nNama: ${item.name}\nAction: ${item.action_type}\nParam: ${item.action_param}`);
};
</script>
