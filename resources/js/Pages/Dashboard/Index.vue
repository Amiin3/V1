<template>
  <Head title="Dashboard" />
  <div class="min-h-screen bg-gray-50">
    <!-- Header MyXL Style -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-500 shadow-xl px-4 pt-6 pb-8 text-white rounded-b-[2rem]">
      <div class="max-w-lg mx-auto">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h1 class="text-xl font-bold">{{ profile.number }}</h1>
            <p class="text-xs opacity-70">{{ profile.subscription_type }} • Aktif s/d {{ profile.balance_expired_at }}</p>
          </div>
          <Link href="/logout" method="post" class="bg-white/20 p-2 rounded-full hover:bg-white/30 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          </Link>
        </div>

        <!-- Kartu Balance -->
        <div class="bg-white/15 backdrop-blur rounded-2xl p-4 mt-2">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-xs opacity-70 mb-1">Pulsa</p>
              <p class="text-3xl font-bold tracking-tight">Rp {{ profile.balance.toLocaleString('id-ID') }}</p>
            </div>
            <div v-if="profile.subscription_type === 'PREPAID'" class="text-right">
              <p class="text-xs opacity-70 mb-1">Poin</p>
              <p class="text-2xl font-bold">{{ profile.points }}</p>
              <p class="text-xs opacity-70">Tier {{ profile.tier }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Menu Grid -->
    <div class="max-w-lg mx-auto -mt-4 px-4 pb-10">
      <div class="grid grid-cols-3 gap-3">
        <Link href="/packages/my" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">📦</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Paket<br>Saya</span>
        </Link>
        <Link href="/packages/hot" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🔥</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Beli<br>HOT</span>
        </Link>
        <Link href="/transaction-history" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">📋</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Riwayat</span>
        </Link>
        <Link href="/family" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">👨‍👩‍👧‍👦</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Family<br>Plan</span>
        </Link>
        <Link href="/circle" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">⭕</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Circle</span>
        </Link>
        <Link href="/notifications" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🔔</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Notif</span>
        </Link>
        <Link href="/store/segments" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🏪</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Store<br>Segments</span>
        </Link>
        <Link href="/store/families" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">📂</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Family<br>List</span>
        </Link>
        <Link href="/store/packages" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🎁</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Store<br>Packages</span>
        </Link>
        <Link href="/store/redeemables" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🎟️</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Redeem</span>
        </Link>
        <Link href="/balance-allotment" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">💸</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Transfer<br>Pulsa</span>
        </Link>
        <Link href="/register" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">📝</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Register</span>
        </Link>
        <Link href="/validate" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">✅</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Validate<br>MSISDN</span>
        </Link>
        <Link href="/bookmarks" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">⭐</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Bookmark</span>
        </Link>
        <Link href="/packages/option" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🎯</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Option<br>Code</span>
        </Link>
        <Link href="/packages/family" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">👪</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Family<br>Code</span>
        </Link>
        <Link href="/packages/loop" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center hover:shadow-md transition active:scale-95">
          <span class="text-2xl mb-1">🔄</span>
          <span class="text-xs font-medium text-gray-600 text-center leading-tight">Loop<br>Purchase</span>
        </Link>
      </div>
    </div>
  </div>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ profile: Object });
</script>
