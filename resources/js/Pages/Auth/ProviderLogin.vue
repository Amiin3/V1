<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
      <h1 class="text-2xl font-bold text-center text-blue-600 mb-6">Login OTP</h1>
      <div v-if="$page.props.errors?.password" class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded text-sm">
        {{ $page.props.errors.password }}
      </div>
      <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-3 rounded text-sm">
        {{ $page.props.flash.success }}
      </div>
      <form @submit.prevent="requestOtp">
        <div class="mb-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">Nomor HP (628xxx)</label>
          <input v-model="form.number" placeholder="628xxxxxxxxxx" class="w-full border rounded-xl p-3 text-sm" required>
        </div>
        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
          {{ form.processing ? 'Mengirim...' : 'Kirim OTP' }}
        </button>
      </form>
      <form @submit.prevent="login" class="mt-6 border-t pt-6">
        <div class="mb-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">Kode OTP</label>
          <input v-model="form.password" placeholder="123456" maxlength="6" class="w-full border rounded-xl p-3 text-sm" required>
        </div>
        <button type="submit" :disabled="form.processing" class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
          {{ form.processing ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>
    </div>
  </div>
</template>
<script setup>
import { useForm } from '@inertiajs/vue3';
const form = useForm({ number: '', password: '' });
const requestOtp = () => form.post('/login/request-otp', { preserveScroll: true, onSuccess: () => alert('OTP dikirim!') });
const login = () => form.post('/login');
</script>
