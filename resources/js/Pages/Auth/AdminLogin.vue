<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 to-gray-800 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
      <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">🔐 Admin Login</h1>
      
      <div v-if="$page.props.errors?.error" class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded text-sm">
        {{ $page.props.errors.error }}
      </div>

      <form @submit.prevent="login">
        <div class="mb-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
          <input v-model="form.username" placeholder="Username" class="w-full border rounded-xl p-3 text-sm" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
          <input v-model="form.password" type="password" placeholder="Password" class="w-full border rounded-xl p-3 text-sm" required>
        </div>
        <button type="submit" :disabled="form.processing" class="w-full bg-gray-800 text-white py-3 rounded-xl font-semibold disabled:opacity-50">
          {{ form.processing ? 'Memproses...' : 'Masuk Admin' }}
        </button>
      </form>
      
      <div class="mt-4 text-center">
        <Link href="/login" class="text-sm text-blue-600 underline">Login Reseller (OTP)</Link>
      </div>
    </div>
  </div>
</template>
<script setup>
import { useForm, Link } from '@inertiajs/vue3';
const form = useForm({ username: '', password: '' });
const login = () => form.post('/admin/login');
</script>
