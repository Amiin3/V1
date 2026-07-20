<template>
  <Head title="Login Dashboard" />
  <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl flex flex-col md:flex-row overflow-hidden border-t-4 border-blue-600">
      
      <!-- Sisi Kiri: Login Manual OTP -->
      <div class="w-full md:w-1/2 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
          <span class="mr-2">🔐</span> Login Manual
        </h2>
        
        <div v-if="$page.props.flash?.error" class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm font-bold">
          {{ $page.props.flash.error }}
        </div>
        <div v-if="$page.props.flash?.success" class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm font-bold">
          {{ $page.props.flash.success }}
        </div>

        <form @submit.prevent="submitOtp">
          <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-2">Nomor HP</label>
            <input v-model="form.number" type="text" class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50" placeholder="628xxxxxxxxxx" required>
          </div>
          
          <div class="mb-6 flex space-x-2">
            <div class="flex-grow">
              <label class="block text-sm font-bold text-gray-700 mb-2">Kode OTP</label>
              <input v-model="form.password" type="text" class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50" placeholder="Masukkan OTP" required>
            </div>
            <div class="flex items-end">
              <button type="button" @click="requestOtp" :disabled="!form.number || form.processing" class="h-[46px] px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-lg text-sm transition whitespace-nowrap border">
                Minta OTP
              </button>
            </div>
          </div>
          
          <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md">
            Masuk & Verifikasi
          </button>
        </form>
      </div>

      <!-- Sisi Kanan: 1-Click Login dari Sesi Tersimpan -->
      <div class="w-full md:w-1/2 bg-gray-50 p-8 border-t md:border-t-0 md:border-l border-gray-200">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold text-gray-800 flex items-center">
            <span class="mr-2">⚡</span> Login Otomatis
          </h2>
          <button @click="fetchSessions" class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center">
            🔄 Refresh
          </button>
        </div>
        
        <p class="text-sm text-gray-500 mb-4">Pilih nomor dari daftar sesi Reseller yang sudah aktif untuk masuk tanpa OTP.</p>
        
        <div v-if="loading" class="text-center py-8 text-gray-500 font-semibold animate-pulse">Memuat sesi aktif...</div>
        <div v-else-if="sessions.length === 0" class="text-center py-8 text-gray-500 bg-white rounded-xl border border-dashed border-gray-300 text-sm">
          Belum ada sesi reseller yang aktif.
        </div>
        <div v-else class="space-y-3 max-h-72 overflow-y-auto pr-2">
          <button 
            v-for="s in sessions" 
            :key="s.id" 
            @click="autoLogin(s.phone_number)"
            class="w-full text-left bg-white border border-gray-200 p-4 rounded-lg hover:border-blue-500 hover:shadow-md hover:bg-blue-50 transition flex justify-between items-center group"
          >
            <div>
              <div class="font-bold text-gray-900 text-lg tracking-wide">{{ s.phone_number }}</div>
              <div class="text-xs text-green-600 font-medium mt-1">Sesi Siap Digunakan</div>
            </div>
            <span class="text-blue-600 opacity-0 group-hover:opacity-100 transition transform group-hover:translate-x-1 text-2xl">➡️</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import axios from 'axios';

const form = useForm({ number: '', password: '' });
const autoForm = useForm({ number: '' });
const sessions = ref([]);
const loading = ref(true);

const fetchSessions = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/admin/reseller-sessions');
    sessions.value = res.data;
  } catch (e) {
    console.error("Gagal memuat sesi", e);
  }
  loading.value = false;
};

const requestOtp = () => form.post('/login/request-otp');
const submitOtp = () => form.post('/login');

const autoLogin = (phone) => {
  autoForm.number = phone;
  autoForm.post('/login/auto');
};

onMounted(() => fetchSessions());
</script>
