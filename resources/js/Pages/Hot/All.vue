<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-red-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <h1 class="text-lg font-bold">🔥 Semua Paket HOT</h1>
    </div>

    <div class="max-w-2xl mx-auto p-4 space-y-6">
      <div v-if="error" class="bg-red-50 p-4 rounded-xl text-red-700">{{ error }}</div>

      <!-- HOT-1 -->
      <div v-if="hot1.length">
        <h2 class="text-lg font-semibold text-gray-800 mb-3 px-1">🔥 HOT-1</h2>
        <div class="space-y-3">
          <div v-for="(item, idx) in hot1" :key="'h1-'+idx" class="bg-white rounded-2xl shadow p-4">
            <p class="font-bold text-gray-800">{{ item.family_name }} - {{ item.variant_name }}</p>
            <p class="text-sm text-gray-600">{{ item.option_name }}</p>
            <Link :href="`/packages/detail/${item.family_code}/${item.variant_name}/${item.order}`" class="mt-2 inline-block bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">Lihat Detail & Beli</Link>
          </div>
        </div>
      </div>

      <!-- HOT-2 -->
      <div v-if="hot2.length">
        <h2 class="text-lg font-semibold text-gray-800 mb-3 px-1">🔥 HOT-2</h2>
        <div class="space-y-4">
          <div v-for="(item, idx) in hot2" :key="'h2-'+idx" class="bg-white rounded-2xl shadow p-4">
            <p class="font-bold text-gray-800 text-lg">{{ item.name }}</p>
            <p class="text-sm text-gray-600">Harga: {{ formatPrice(item.price) }}</p>
            <p class="text-sm text-gray-600">{{ item.detail }}</p>
            <div v-if="item.packages" class="mt-2 text-xs text-gray-500 space-y-1">
              <div v-for="(pkg, pIdx) in item.packages" :key="pIdx" class="flex items-center space-x-2">
                <span>📦</span>
                <span>{{ pkg.family_code?.substring(0,8) }}... / {{ pkg.variant_code?.substring(0,8) }}... / order:{{ pkg.order }}</span>
              </div>
            </div>

            <!-- Decoy Option -->
            <div class="mt-3 flex items-center space-x-2">
              <input type="checkbox" v-model="useDecoy[idx]" class="w-4 h-4 text-red-600">
              <span class="text-sm text-gray-700">Gunakan Decoy (Balance)</span>
              <span v-if="useDecoy[idx] && decoyPrice[idx]" class="text-xs text-gray-500">+Rp {{ decoyPrice[idx]?.toLocaleString('id-ID') }}</span>
            </div>

            <!-- Pilihan Metode Pembayaran -->
            <div class="mt-2 space-y-2">
              <label class="text-sm font-medium text-gray-700">Metode Pembayaran:</label>
              <select v-model="paymentMethods[idx]" class="w-full border rounded-xl p-2 text-sm">
                <option value="BALANCE">💳 Pulsa (Balance)</option>
                <option value="QRIS">📱 QRIS</option>
                <option value="DANA">💰 DANA</option>
                <option value="SHOPEEPAY">🛒 ShopeePay</option>
                <option value="GOPAY">🟢 GoPay</option>
                <option value="OVO">🟣 OVO</option>
              </select>
              <input v-if="['DANA','OVO'].includes(paymentMethods[idx])" v-model="walletNumbers[idx]" placeholder="Nomor wallet (08xxx)" class="w-full border rounded-xl p-2 text-sm mt-1">
            </div>

            <button @click="buyHot2(item, idx)" :disabled="buyingHot2 === idx" class="mt-3 w-full bg-red-600 text-white py-2 rounded-xl text-sm font-semibold disabled:opacity-50">
              {{ buyingHot2 === idx ? 'Memproses...' : 'Beli Sekarang' }}
            </button>
            <p v-if="msgHot2[idx]" class="mt-1 text-xs p-2 rounded-lg" :class="msgHot2Error[idx] ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">{{ msgHot2[idx] }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  hot1: { type: Array, default: () => [] },
  hot2: { type: Array, default: () => [] },
  error: String
});

const buyingHot2 = ref(-1);
const msgHot2 = ref([]);
const msgHot2Error = ref([]);
const paymentMethods = ref([]);
const walletNumbers = ref([]);
const useDecoy = ref([]);
const decoyPrice = ref([]);

onMounted(async () => {
  for (let i = 0; i < props.hot2.length; i++) {
    try {
      const res = await axios.get('/packages/decoy/price?type=balance');
      decoyPrice.value[i] = res.data.price || 0;
    } catch (e) {
      decoyPrice.value[i] = 0;
    }
  }
});

const formatPrice = (price) => {
  if (!price && price !== 0) return 'Rp0';
  if (typeof price === 'string' && price.toLowerCase().includes('rp')) return price;
  const num = Number(price);
  if (isNaN(num)) return price;
  return 'Rp ' + num.toLocaleString('id-ID');
};

const buyHot2 = async (item, idx) => {
  const method = paymentMethods.value[idx] || 'BALANCE';
  const wallet = walletNumbers.value[idx] || '';
  if (['DANA','OVO'].includes(method) && !wallet) {
    alert('Nomor wallet harus diisi.');
    return;
  }
  if (!confirm(`Beli "${item.name}" dengan metode ${method}?`)) return;
  buyingHot2.value = idx;
  msgHot2.value[idx] = '';
  try {
    const res = await axios.post('/packages/hot2/buy', {
      packages: item.packages || [],
      payment_method: method,
      wallet_number: wallet,
      decoy_type: useDecoy.value[idx] ? 'balance' : null,
      decoy_prefix: 'default',
      overwrite_amount: item.overwrite_amount ?? -1,
      amount_idx: item.amount_idx ?? -1,
    });
    const data = res.data;
    if (data.status === 'SUCCESS') {
      if (data.deeplink) {
        msgHot2.value[idx] = '✅ Silakan selesaikan pembayaran: <a href="' + data.deeplink + '" target="_blank" class="underline">Klik di sini</a>';
      } else {
        msgHot2.value[idx] = '✅ Pembelian berhasil!';
      }
      msgHot2Error.value[idx] = false;
    } else {
      msgHot2.value[idx] = '❌ ' + (data.error || data.message || 'Gagal');
      msgHot2Error.value[idx] = true;
    }
  } catch (e) {
    msgHot2.value[idx] = '❌ ' + (e.response?.data?.error || e.message);
    msgHot2Error.value[idx] = true;
  }
  buyingHot2.value = -1;
};
</script>
