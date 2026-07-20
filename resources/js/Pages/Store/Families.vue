<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div class="flex-1">
        <h1 class="text-lg font-bold">Family List</h1>
        <p class="text-xs opacity-80">{{ subscriptionType }}</p>
      </div>
      <label class="flex items-center space-x-2 text-sm bg-white/20 px-3 py-1.5 rounded-full">
        <span>Enterprise</span>
        <input type="checkbox" v-model="enterprise" @change="toggleEnterprise" class="w-4 h-4">
      </label>
    </div>

    <div class="max-w-2xl mx-auto p-4 space-y-3">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>
      <div v-if="familyList.length === 0 && !error" class="text-center bg-white rounded-2xl shadow p-6 text-gray-500">Tidak ada family tersedia.</div>

      <div v-for="family in familyList" :key="family.family_code" class="bg-white rounded-2xl shadow p-4">
        <div class="flex justify-between items-start">
          <div>
            <p class="font-bold text-gray-800">{{ family.family_name }}</p>
            <p class="text-xs text-gray-500">{{ family.family_code }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ family.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  familyList: Array,
  error: String,
  isEnterprise: Boolean,
  subscriptionType: String,
});

const enterprise = ref(props.isEnterprise);
const toggleEnterprise = () => {
  router.get('/store/families', { enterprise: enterprise.value ? '1' : '0' }, { preserveState: true, replace: true });
};
</script>
