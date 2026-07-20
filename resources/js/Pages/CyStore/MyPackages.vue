<template>
    <div class="max-w-4xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">My Packages - CY STORE</h1>

        <div v-if="quotas.length === 0" class="text-center text-gray-500">
            Tidak ada paket aktif.
        </div>

        <div v-else class="space-y-6">
            <div v-for="(quota, index) in quotas" :key="quota.quota_code" class="bg-white p-5 rounded-lg shadow border">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-lg font-bold">Paket {{ index + 1 }}: {{ quota.name }}</h2>
                        <p class="text-sm text-gray-500">Group: {{ quota.group_name }} | Code: {{ quota.quota_code }}</p>
                    </div>
                    <button 
                        @click="unsubscribe(quota)" 
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition"
                        :disabled="form.processing"
                    >
                        Unsubscribe
                    </button>
                </div>

                <div class="bg-gray-50 p-4 rounded-md">
                    <h3 class="font-semibold mb-2">Benefits:</h3>
                    <ul class="space-y-2">
                        <li v-for="benefit in quota.benefits" :key="benefit.id" class="border-b pb-2 text-sm">
                            <span class="font-medium">{{ benefit.name }}</span> ({{ benefit.data_type }})<br>
                            <span class="text-gray-600">
                                Sisa: {{ formatQuota(benefit.remaining, benefit.data_type) }} / 
                                Total: {{ formatQuota(benefit.total, benefit.data_type) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    quotas: Array
});

const form = useForm({
    quota_code: '',
    product_subscription_type: '',
    product_domain: ''
});

const unsubscribe = (quota) => {
    if (confirm(`Yakin mau berhenti berlangganan ${quota.name}?`)) {
        form.quota_code = quota.quota_code;
        form.product_subscription_type = quota.product_subscription_type;
        form.product_domain = quota.product_domain;
        
        form.delete(route('packages.unsubscribe'), {
            preserveScroll: true,
            onSuccess: () => alert('Berhasil Unsubscribe!')
        });
    }
};

const formatQuota = (amount, type) => {
    if (type === 'DATA') {
        if (amount >= 1073741824) return (amount / 1073741824).toFixed(2) + ' GB';
        if (amount >= 1048576) return (amount / 1048576).toFixed(2) + ' MB';
        if (amount >= 1024) return (amount / 1024).toFixed(2) + ' KB';
        return amount + ' Bytes';
    } else if (type === 'VOICE') {
        return (amount / 60).toFixed(2) + ' menit';
    } else if (type === 'TEXT') {
        return amount + ' SMS';
    }
    return amount;
};
</script>
