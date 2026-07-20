<template>
  <div class="min-h-screen bg-gray-100 p-6 pb-20">
    <div class="max-w-6xl mx-auto">
      
      <!-- Header -->
      <div class="flex items-center space-x-4 mb-6">
        <Link href="/" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-bold shadow-sm">⬅ Kembali</Link>
        <h1 class="text-2xl font-bold text-gray-800">🔥 Beli Paket HOT (All in One)</h1>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.errors?.error" class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow">
        <p class="font-bold">Transaksi Gagal</p>
        <p>{{ $page.props.errors.error }}</p>
      </div>
      
      <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow">
        <p class="font-bold">Transaksi Berhasil!</p>
        <p>{{ $page.props.flash.success }}</p>
      </div>

      <!-- Daftar Paket Gabungan -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="(pkg, index) in hotPackages" :key="index" 
          class="bg-white rounded-xl shadow-lg border-t-4 overflow-hidden flex flex-col transition hover:-translate-y-1 hover:shadow-xl"
          :class="pkg.badge === 'HOT 2' ? 'border-red-500' : 'border-blue-500'"
        >
          <div class="p-5 flex-grow relative">
            <!-- Label Badge -->
            <span 
              class="absolute top-4 right-4 text-xs font-bold px-2 py-1 rounded"
              :class="pkg.badge === 'HOT 2' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
            >
              {{ pkg.badge }}
            </span>
            
            <h2 class="text-lg font-bold text-gray-900 mb-2 pr-14 leading-tight">{{ pkg.name }}</h2>
            
            <p class="text-2xl font-extrabold mb-4" :class="pkg.badge === 'HOT 2' ? 'text-red-600' : 'text-blue-600'">
              {{ pkg.price > 0 ? 'Rp ' + pkg.price.toLocaleString('id-ID') : 'Cek Harga Otomatis' }}
            </p>
            
            <p class="text-xs text-gray-600 bg-gray-50 p-3 rounded border" v-html="pkg.detail.replace(/\n/g, '<br>')"></p>
          </div>
          
          <div class="p-5 bg-gray-50 border-t">
            <button 
              @click="openModal(pkg)" 
              class="w-full text-white font-bold py-3 px-4 rounded-lg transition shadow-md"
              :class="pkg.badge === 'HOT 2' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
            >
              Beli Paket Ini
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Pembayaran -->
      <div v-if="selectedPackage" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-1 text-gray-900">Checkout</h3>
          <p class="text-sm text-gray-500 mb-6">{{ selectedPackage.name }}</p>
          
          <form @submit.prevent="submitPurchase">
            <div class="space-y-4">
              <!-- Pilihan Metode Pembayaran -->
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Metode Pembayaran</label>
                <select v-model="form.payment_method" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500" required>
                  <option value="BALANCE">Pulsa (Balance)</option>
                  <option value="QRIS">QRIS</option>
                  <option value="DANA">DANA</option>
                  <option value="OVO">OVO</option>
                  <option value="GOPAY">GoPay</option>
                  <option value="SHOPEEPAY">ShopeePay</option>
                </select>
              </div>

              <!-- Input Nomor E-Wallet -->
              <div v-if="['DANA','OVO'].includes(form.payment_method)">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nomor {{ form.payment_method }} (08xxx)</label>
                <input v-model="form.wallet_number" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="08123456789" required>
              </div>

              <!-- Checkbox Gunakan Decoy -->
              <div class="flex items-center bg-yellow-50 p-3 rounded-lg border border-yellow-200" v-if="['BALANCE','QRIS'].includes(form.payment_method)">
                <input v-model="useDecoy" type="checkbox" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                <label class="ml-2 block text-sm font-bold text-gray-800">
                  Gunakan Decoy 🤫
                </label>
              </div>
            </div>

            <div class="mt-8 flex space-x-3">
              <button type="button" @click="closeModal" class="w-1/2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition" :disabled="form.processing">
                Batal
              </button>
              <button type="submit" class="w-1/2 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition flex justify-center items-center" :disabled="form.processing">
                <span v-if="form.processing">Memproses...</span>
                <span v-else>Bayar</span>
              </button>
            </div>
          </form>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
  hotPackages: Array
});

const selectedPackage = ref(null);
const useDecoy = ref(false);

const form = useForm({
  packages: [],
  payment_method: 'BALANCE',
  wallet_number: '',
  decoy_type: '',
  overwrite_amount: -1,
  amount_idx: -1,
});

watch([useDecoy, () => form.payment_method], ([decoyActive, method]) => {
  if (decoyActive && ['BALANCE', 'QRIS'].includes(method)) {
    form.decoy_type = method.toLowerCase();
  } else {
    form.decoy_type = '';
  }
});

const openModal = (pkg) => {
  selectedPackage.value = pkg;
  form.packages = pkg.packages || [];
  form.overwrite_amount = pkg.overwrite_amount ?? -1;
  form.amount_idx = pkg.amount_idx ?? -1;
  form.payment_method = 'BALANCE';
  form.wallet_number = '';
  useDecoy.value = false;
};

const closeModal = () => {
  selectedPackage.value = null;
  form.reset();
};

const submitPurchase = () => {
  form.post('/packages/hot2/buy', {
    preserveScroll: true,
    onSuccess: (page) => {
      if (page.props.flash?.deeplink) {
        window.open(page.props.flash.deeplink, '_blank');
        alert("Silakan selesaikan pembayaran di aplikasi / link tab baru.");
      }
      closeModal();
    }
  });
};
</script>
