<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-red-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/packages/hot" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <h1 class="text-lg font-bold">Detail Paket</h1>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-4">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>

      <div v-if="package" class="bg-white rounded-2xl shadow p-5 space-y-4">
        <h2 class="font-bold text-xl text-gray-800">{{ package.package_family?.name }} - {{ package.package_detail_variant?.name }}</h2>
        <p class="text-sm text-gray-600">{{ package.package_option?.name }}</p>
        <div class="flex justify-between"><span class="text-gray-600">Harga</span><span class="font-bold text-blue-600">Rp {{ package.package_option?.price?.toLocaleString('id-ID') }}</span></div>
        <div class="flex justify-between"><span class="text-gray-600">Masa Aktif</span><span>{{ package.package_option?.validity }}</span></div>
        <div class="flex justify-between"><span class="text-gray-600">Payment For</span><span>{{ package.package_family?.payment_for || 'BUY_PACKAGE' }}</span></div>

        <div v-if="package.package_option?.benefits?.length" class="mt-3">
          <h3 class="font-semibold text-gray-700">Benefit</h3>
          <ul class="list-disc list-inside text-sm text-gray-600">
            <li v-for="b in package.package_option.benefits" :key="b.item_id">{{ b.name }} - {{ formatBenefit(b) }}</li>
          </ul>
        </div>

        <!-- Pilihan Metode Pembayaran Lengkap -->
        <div class="pt-4 border-t space-y-3">
          <h3 class="font-semibold text-gray-700">Metode Pembayaran</h3>

          <!-- 1. Balance / Pulsa -->
          <button @click="buy('BALANCE')" :disabled="buying" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
            💳 Beli dengan Pulsa (Rp {{ package.package_option?.price?.toLocaleString('id-ID') }})
          </button>

          <!-- 2. E-Wallet -->
          <div class="bg-gray-50 rounded-xl p-3 space-y-2">
            <p class="text-sm font-medium text-gray-700">💰 E-Wallet</p>
            <div class="grid grid-cols-2 gap-2">
              <button @click="buy('DANA')" :disabled="buying" class="bg-white border rounded-xl py-2 text-sm font-medium">DANA</button>
              <button @click="buy('SHOPEEPAY')" :disabled="buying" class="bg-white border rounded-xl py-2 text-sm font-medium">ShopeePay</button>
              <button @click="buy('GOPAY')" :disabled="buying" class="bg-white border rounded-xl py-2 text-sm font-medium">GoPay</button>
              <button @click="buy('OVO')" :disabled="buying" class="bg-white border rounded-xl py-2 text-sm font-medium">OVO</button>
            </div>
            <input v-if="['DANA','OVO'].includes(ewalletMethod)" v-model="walletNumber" placeholder="Nomor wallet (08xxx)" class="w-full border rounded-xl p-2 text-sm mt-1">
          </div>

          <!-- 3. QRIS -->
          <button @click="buy('QRIS')" :disabled="buying" class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
            📱 Bayar dengan QRIS
          </button>

          <!-- 4. Pulsa + Decoy -->
          <div class="bg-yellow-50 rounded-xl p-3 space-y-2">
            <div class="flex items-center space-x-2">
              <input type="checkbox" v-model="useDecoy" class="w-4 h-4">
              <span class="text-sm font-medium text-gray-700">Gunakan Decoy (Balance) +Rp {{ decoyPrice.toLocaleString('id-ID') }}</span>
            </div>
            <button @click="buyWithDecoy('BALANCE')" :disabled="buying || !useDecoy" class="w-full bg-yellow-600 text-white py-2 rounded-xl text-sm font-semibold disabled:opacity-50">
              🎯 Pulsa + Decoy (Rp {{ totalWithDecoy.toLocaleString('id-ID') }})
            </button>
            <button @click="buyWithDecoyV2('BALANCE')" :disabled="buying || !useDecoy" class="w-full bg-yellow-700 text-white py-2 rounded-xl text-sm font-semibold disabled:opacity-50">
              🎯 Pulsa + Decoy V2
            </button>
          </div>

          <!-- 5. QRIS + Decoy -->
          <div class="bg-teal-50 rounded-xl p-3 space-y-2">
            <div class="flex items-center space-x-2">
              <input type="checkbox" v-model="useQrisDecoy" class="w-4 h-4">
              <span class="text-sm font-medium text-gray-700">QRIS + Decoy</span>
            </div>
            <button @click="buyWithDecoy('QRIS')" :disabled="buying || !useQrisDecoy" class="w-full bg-teal-600 text-white py-2 rounded-xl text-sm font-semibold disabled:opacity-50">
              📱 QRIS + Decoy
            </button>
          </div>

          <!-- 8. Pulsa N Kali -->
          <div class="bg-purple-50 rounded-xl p-3 space-y-2">
            <p class="text-sm font-medium text-gray-700">🔄 Pulsa N Kali</p>
            <input v-model="nTimes" type="number" min="1" placeholder="Berapa kali?" class="w-full border rounded-xl p-2 text-sm">
            <div class="flex items-center space-x-2">
              <input type="checkbox" v-model="useDecoyN" class="w-4 h-4">
              <span class="text-xs text-gray-600">Dengan Decoy</span>
            </div>
            <button @click="buyNTimes" :disabled="buying || !nTimes" class="w-full bg-purple-600 text-white py-2 rounded-xl text-sm font-semibold disabled:opacity-50">
              Beli {{ nTimes || 'N' }} Kali
            </button>
          </div>
        </div>

        <!-- Pesan hasil -->
        <p v-if="buyMsg" class="text-sm p-3 rounded-xl" :class="buyError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">{{ buyMsg }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ package: Object, error: String });

const buying = ref(false);
const buyMsg = ref('');
const buyError = ref(false);
const useDecoy = ref(false);
const useQrisDecoy = ref(false);
const useDecoyN = ref(false);
const decoyPrice = ref(0);
const ewalletMethod = ref('');
const walletNumber = ref('');
const nTimes = ref(1);

const totalWithDecoy = computed(() => {
  return (props.package?.package_option?.price || 0) + decoyPrice.value;
});

onMounted(async () => {
  try {
    const res = await axios.get('/packages/decoy/price?type=balance');
    decoyPrice.value = res.data.price || 0;
  } catch (e) {}
});

const formatBenefit = (b) => {
  if (b.data_type === 'VOICE' && b.total > 0) return (b.total/60).toFixed(2) + ' menit';
  if (b.data_type === 'TEXT' && b.total > 0) return b.total + ' SMS';
  if (b.data_type === 'DATA' && b.total > 0) {
    const bytes = b.total;
    if (bytes >= 1073741824) return (bytes/1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes/1048576).toFixed(2) + ' MB';
    return (bytes/1024).toFixed(2) + ' KB';
  }
  return b.total + ' ' + (b.data_type || '');
};

const doBuy = async (payload) => {
  buying.value = true;
  buyMsg.value = '';
  try {
    const res = await axios.post('/packages/buy', payload);
    const data = res.data;
    if (data.status === 'SUCCESS') {
      if (data.deeplink) {
        buyMsg.value = '✅ Silakan selesaikan: <a href="' + data.deeplink + '" target="_blank" class="underline">Klik di sini</a>';
      } else {
        buyMsg.value = '✅ Pembelian berhasil!';
      }
      buyError.value = false;
    } else {
      buyMsg.value = '❌ ' + (data.error || data.message || 'Gagal');
      buyError.value = true;
    }
  } catch (e) {
    buyMsg.value = '❌ ' + (e.response?.data?.error || e.message);
    buyError.value = true;
  }
  buying.value = false;
};

const buy = (method) => {
  ewalletMethod.value = method;
  const payload = {
    option_code: props.package.option_code || props.package.package_option?.package_option_code,
    payment_method: method,
    price: props.package.package_option?.price,
    token_confirmation: props.package.token_confirmation,
    payment_for: props.package.package_family?.payment_for || 'BUY_PACKAGE',
    item_name: props.package.package_option?.name,
  };
  if (['DANA','OVO'].includes(method)) {
    if (!walletNumber.value) { alert('Nomor wallet harus diisi.'); return; }
    payload.wallet_number = walletNumber.value;
  }
  doBuy(payload);
};

const buyWithDecoy = (method) => {
  doBuy({
    option_code: props.package.option_code || props.package.package_option?.package_option_code,
    payment_method: method,
    price: props.package.package_option?.price,
    token_confirmation: props.package.token_confirmation,
    payment_for: props.package.package_family?.payment_for || 'BUY_PACKAGE',
    item_name: props.package.package_option?.name,
    decoy_type: 'balance',
  });
};

const buyWithDecoyV2 = (method) => {
  doBuy({
    option_code: props.package.option_code || props.package.package_option?.package_option_code,
    payment_method: method,
    price: props.package.package_option?.price,
    token_confirmation: props.package.token_confirmation,
    payment_for: '🤫',
    item_name: props.package.package_option?.name,
    decoy_type: 'balance',
    token_confirmation_idx: 1,
  });
};

const buyNTimes = async () => {
  if (!nTimes.value || nTimes.value < 1) return;
  buying.value = true;
  buyMsg.value = '';
  let success = 0;
  for (let i = 0; i < nTimes.value; i++) {
    try {
      const res = await axios.post('/packages/buy', {
        option_code: props.package.option_code || props.package.package_option?.package_option_code,
        payment_method: 'BALANCE',
        price: props.package.package_option?.price,
        token_confirmation: props.package.token_confirmation,
        payment_for: '🤫',
        item_name: props.package.package_option?.name,
        decoy_type: useDecoyN.value ? 'balance' : null,
        token_confirmation_idx: useDecoyN.value ? 1 : 0,
      });
      if (res.data.status === 'SUCCESS') success++;
    } catch (e) {}
    if (i < nTimes.value - 1) await new Promise(r => setTimeout(r, 1000));
  }
  buyMsg.value = `✅ Berhasil ${success} dari ${nTimes.value} kali pembelian.`;
  buyError.value = success === 0;
  buying.value = false;
};
</script>
