<template>
    <div class="max-w-4xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Riwayat Transaksi - CY STORE</h1>
        
        <div class="mb-4 text-right">
            <button @click="refreshData" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                Refresh Data
            </button>
        </div>

        <div v-if="history.length === 0" class="text-center text-gray-500 py-10 bg-white rounded shadow">
            Tidak ada riwayat transaksi.
        </div>

        <div v-else class="space-y-4">
            <div v-for="(trx, index) in history" :key="index" class="bg-white p-4 rounded shadow border-l-4" :class="trx.status === 'SUCCESS' ? 'border-green-500' : 'border-red-500'">
                <div class="flex justify-between border-b pb-2 mb-2">
                    <h3 class="font-bold">{{ index + 1 }}. {{ trx.title }}</h3>
                    <span class="font-semibold text-blue-600">{{ trx.price }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm text-gray-700">
                    <p><strong>Tanggal:</strong> {{ trx.formatted_time }}</p>
                    <p><strong>Metode:</strong> {{ trx.payment_method_label }}</p>
                    <p><strong>Status Transaksi:</strong> <span :class="trx.status === 'SUCCESS' ? 'text-green-600' : 'text-red-600'">{{ trx.status }}</span></p>
                    <p><strong>Status Pembayaran:</strong> {{ trx.payment_status }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    history: Array
});

const refreshData = () => {
    router.reload({ only: ['history'] });
};
</script>
