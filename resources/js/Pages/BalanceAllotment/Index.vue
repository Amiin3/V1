<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div>
        <h1 class="text-lg font-bold">Transfer Pulsa</h1>
        <p class="text-xs opacity-80">{{ profile.number }}</p>
      </div>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-4">
      <div class="bg-white rounded-2xl shadow p-5 text-center">
        <p class="text-sm text-gray-500">Saldo Anda</p>
        <p class="text-3xl font-bold text-blue-600">Rp {{ balance.toLocaleString('id-ID') }}</p>
        <p class="text-xs text-gray-400 mt-2">Pastikan PIN transaksi sudah disetel di MyXL</p>
      </div>

      <div class="bg-white rounded-2xl shadow p-5 space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700">Nomor Tujuan</label>
          <input v-model="form.receiver" placeholder="628xxxxxxxxxx" class="w-full border p-3 rounded-xl mt-1 text-sm">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Jumlah (min Rp 5.000)</label>
          <input v-model="form.amount" type="number" placeholder="5000" class="w-full border p-3 rounded-xl mt-1 text-sm">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">PIN (6 digit)</label>
          <input v-model="form.pin" type="password" maxlength="6" placeholder="******" class="w-full border p-3 rounded-xl mt-1 text-sm">
        </div>
        <button @click="submitTransfer" :disabled="loading" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
          {{ loading ? 'Memproses...' : 'Kirim Pulsa' }}
        </button>
        <div v-if="msg" class="p-3 rounded-xl text-sm" :class="msgError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">{{ msg }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ balance: Number, profile: Object });
const form = ref({ receiver: '', amount: '', pin: '' });
const loading = ref(false);
const msg = ref('');
const msgError = ref(false);

const submitTransfer = async () => {
  if (!form.value.receiver.startsWith('628')) { msg.value = 'Nomor tujuan harus diawali 628.'; msgError.value = true; return; }
  if (parseInt(form.value.amount) < 5000) { msg.value = 'Minimal transfer Rp 5.000.'; msgError.value = true; return; }
  if (form.value.pin.length !== 6) { msg.value = 'PIN harus 6 digit.'; msgError.value = true; return; }
  if (!confirm(`⚠️ Transfer Rp ${parseInt(form.value.amount).toLocaleString('id-ID')} ke ${form.value.receiver}?`)) return;
  if (!confirm('Konfirmasi kedua: Transaksi tidak bisa dibatalkan. Lanjutkan?')) return;

  loading.value = true;
  msg.value = '';
  try {
    const res = await axios.post('/balance-allotment/transfer', {
      receiver: form.value.receiver,
      amount: parseInt(form.value.amount),
      pin: form.value.pin,
    });
    if (res.data.status === 'SUCCESS') {
      msg.value = '✅ Transfer berhasil!';
      msgError.value = false;
      form.value = { receiver: '', amount: '', pin: '' };
      setTimeout(() => location.reload(), 2000);
    }
  } catch (e) {
    msg.value = '❌ ' + (e.response?.data?.error || e.message);
    msgError.value = true;
  }
  loading.value = false;
};
</script>
