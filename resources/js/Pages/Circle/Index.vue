<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header MyXL -->
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div>
        <h1 class="text-lg font-bold">Circle</h1>
        <p class="text-xs opacity-80">{{ groupData?.group_name || 'Belum ada Circle' }}</p>
      </div>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-4">
      <!-- Error / No Circle -->
      <div v-if="error && !groupData" class="bg-white rounded-2xl shadow p-6 text-center">
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button @click="showCreateModal = true" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold">Buat Circle Baru</button>
      </div>

      <!-- Circle Info -->
      <div v-if="groupData" class="space-y-4">
        <!-- Card Info -->
        <div class="bg-white rounded-2xl shadow p-5">
          <div class="flex justify-between">
            <div>
              <p class="text-sm text-gray-500">Nama Circle</p>
              <p class="font-bold text-gray-800">{{ groupData.group_name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Status</p>
              <span class="text-xs px-2 py-1 rounded-full" :class="groupData.group_status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ groupData.group_status }}</span>
            </div>
          </div>
          <div class="mt-3">
            <p class="text-sm text-gray-500">Pemilik</p>
            <p class="font-medium">{{ groupData.owner_name }} ({{ ownerMsisdn }})</p>
          </div>
          <!-- Paket & Kuota -->
          <div v-if="package" class="mt-4 bg-blue-50 rounded-xl p-3">
            <p class="text-sm font-semibold text-blue-800">{{ package.name }}</p>
            <div class="flex justify-between text-sm mt-1">
              <span>Sisa Kuota</span>
              <span class="font-bold">{{ formatBytes(package.benefit?.remaining) }} / {{ formatBytes(package.benefit?.allocation) }}</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2 mt-2 overflow-hidden">
              <div class="bg-blue-500 h-2 rounded-full" :style="{ width: packageUsagePercent + '%' }"></div>
            </div>
          </div>
          <!-- Spending -->
          <div v-if="spending" class="mt-3 bg-gray-50 rounded-xl p-3">
            <p class="text-sm text-gray-600">Pengeluaran</p>
            <div class="flex justify-between text-sm mt-1">
              <span>Rp {{ spending.spend?.toLocaleString('id-ID') }}</span>
              <span class="font-bold">Target: Rp {{ spending.target?.toLocaleString('id-ID') }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2 overflow-hidden">
              <div class="bg-orange-500 h-2 rounded-full" :style="{ width: spendingPercent + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- Anggota -->
        <h2 class="font-semibold text-gray-800 px-1">Anggota ({{ members.length }})</h2>
        <div class="space-y-3">
          <div v-for="(member, idx) in members" :key="member.member_id" class="bg-white rounded-2xl shadow p-4">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center space-x-2">
                  <span class="font-bold text-gray-800">{{ member.msisdn_raw || '<Tanpa Nomor>' }}</span>
                  <span class="text-xs px-2 py-0.5 rounded-full" :class="member.member_role === 'PARENT' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">{{ member.member_role }}</span>
                  <span v-if="member.msisdn_raw == myNumber" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">Anda</span>
                </div>
                <p class="text-sm text-gray-500">{{ member.member_name }} • {{ member.slot_type }}</p>
                <div class="mt-2 bg-gray-50 rounded-lg p-2">
                  <div class="flex justify-between text-xs text-gray-600">
                    <span>Pemakaian</span>
                    <span>{{ formatBytes(member.allocation - member.remaining) }} / {{ formatBytes(member.allocation) }}</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 overflow-hidden">
                    <div class="bg-blue-500 h-1.5 rounded-full" :style="{ width: memberUsagePercent(member) + '%' }"></div>
                  </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Status: {{ member.status }} • Sejak {{ formatDate(member.join_date) }}</p>
              </div>
              <div class="flex space-x-1 ml-2" v-if="isOwner">
                <button v-if="member.status === 'INVITED'" @click="confirmAccept(member)" class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full">Terima</button>
                <button v-if="member.member_role !== 'PARENT' && member.status !== 'INVITED'" @click="confirmRemove(member)" class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full">Hapus</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tombol Invite (hanya Owner) -->
        <button v-if="isOwner" @click="showInviteModal = true" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold">+ Undang Anggota</button>
      </div>

      <!-- Modal Buat Circle -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCreateModal = false">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
          <h3 class="font-bold text-lg mb-4">Buat Circle Baru</h3>
          <input v-model="createForm.parent_name" placeholder="Nama Anda (Parent)" class="w-full border p-3 rounded-xl mb-2">
          <input v-model="createForm.group_name" placeholder="Nama Circle" class="w-full border p-3 rounded-xl mb-2">
          <input v-model="createForm.member_msisdn" placeholder="Nomor anggota pertama (62xxx)" class="w-full border p-3 rounded-xl mb-2">
          <input v-model="createForm.member_name" placeholder="Nama anggota pertama" class="w-full border p-3 rounded-xl mb-4">
          <div class="flex space-x-2">
            <button @click="submitCreateCircle" class="bg-blue-600 text-white px-4 py-3 rounded-xl flex-1 font-semibold">Buat</button>
            <button @click="showCreateModal = false" class="bg-gray-200 px-4 py-3 rounded-xl">Batal</button>
          </div>
          <p v-if="createMsg" class="mt-2 text-xs text-red-600">{{ createMsg }}</p>
        </div>
      </div>

      <!-- Modal Invite -->
      <div v-if="showInviteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showInviteModal = false">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
          <h3 class="font-bold text-lg mb-4">Undang Anggota</h3>
          <input v-model="inviteForm.msisdn" placeholder="Nomor (62xxx)" class="w-full border p-3 rounded-xl mb-2">
          <input v-model="inviteForm.name" placeholder="Nama" class="w-full border p-3 rounded-xl mb-4">
          <div class="flex space-x-2">
            <button @click="submitInvite" class="bg-blue-600 text-white px-4 py-3 rounded-xl flex-1 font-semibold">Undang</button>
            <button @click="showInviteModal = false" class="bg-gray-200 px-4 py-3 rounded-xl">Batal</button>
          </div>
          <p v-if="inviteMsg" class="mt-2 text-xs" :class="inviteError ? 'text-red-600' : 'text-green-600'">{{ inviteMsg }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  groupData: Object,
  members: Array,
  package: Object,
  spending: Object,
  error: String,
  myNumber: String,
  isOwner: Boolean,
});

const ownerMsisdn = computed(() => {
  if (!props.members) return '';
  const parent = props.members.find(m => m.member_role === 'PARENT');
  return parent?.msisdn_raw || '-';
});

const packageUsagePercent = computed(() => {
  const alloc = props.package?.benefit?.allocation || 0;
  const remaining = props.package?.benefit?.remaining || 0;
  return alloc > 0 ? Math.min(100, ((alloc - remaining) / alloc) * 100) : 0;
});

const spendingPercent = computed(() => {
  const spend = props.spending?.spend || 0;
  const target = props.spending?.target || 1;
  return Math.min(100, (spend / target) * 100);
});

const memberUsagePercent = (member) => {
  const alloc = member.allocation || 0;
  const remaining = member.remaining || 0;
  return alloc > 0 ? Math.min(100, ((alloc - remaining) / alloc) * 100) : 0;
};

// Create Circle
const showCreateModal = ref(false);
const createForm = ref({ parent_name: '', group_name: '', member_msisdn: '', member_name: '' });
const createMsg = ref('');

const submitCreateCircle = async () => {
  if (!confirm('Buat Circle baru? Pastikan data sudah benar.')) return;
  try {
    const res = await axios.post('/circle/create', createForm.value);
    if (res.data.status === 'SUCCESS') {
      location.reload();
    } else {
      createMsg.value = 'Gagal: ' + (res.data.message || 'Unknown');
    }
  } catch (e) {
    createMsg.value = 'Error: ' + e.message;
  }
};

// Invite
const showInviteModal = ref(false);
const inviteForm = ref({ msisdn: '', name: '' });
const inviteMsg = ref('');
const inviteError = ref(false);

const submitInvite = async () => {
  if (!confirm('Undang anggota ini? Pastikan nomor benar.')) return;
  if (!confirm('Konfirmasi kedua: Undangan tidak bisa dibatalkan.')) return;
  try {
    const res = await axios.post('/circle/invite', {
      msisdn: inviteForm.value.msisdn,
      name: inviteForm.value.name,
      group_id: props.groupData.group_id,
      member_id_parent: props.members.find(m => m.member_role === 'PARENT')?.member_id,
    });
    if (res.data.status === 'SUCCESS' && res.data.data?.response_code === '200-00') {
      inviteMsg.value = 'Undangan berhasil!';
      inviteError.value = false;
      setTimeout(() => location.reload(), 1500);
    } else {
      inviteMsg.value = 'Gagal: ' + (res.data.data?.message || res.data.message || 'Unknown');
      inviteError.value = true;
    }
  } catch (e) {
    inviteMsg.value = 'Error: ' + e.message;
    inviteError.value = true;
  }
};

// Remove
const confirmRemove = (member) => {
  if (!confirm(`Hapus ${member.msisdn_raw} dari Circle?`)) return;
  if (!confirm('Konfirmasi: Penghapusan tidak bisa dibatalkan.')) return;
  axios.post('/circle/remove', {
    member_id: member.member_id,
    group_id: props.groupData.group_id,
    member_id_parent: props.members.find(m => m.member_role === 'PARENT')?.member_id,
    is_last_member: props.members.length <= 2,
  }).then(res => {
    if (res.data.status === 'SUCCESS') location.reload();
    else alert('Gagal: ' + (res.data.message || 'Unknown'));
  }).catch(e => alert('Error: ' + e.message));
};

// Accept
const confirmAccept = (member) => {
  if (!confirm(`Terima ${member.msisdn_raw} ke Circle?`)) return;
  axios.post('/circle/accept', {
    group_id: props.groupData.group_id,
    member_id: member.member_id,
  }).then(res => {
    if (res.data.status === 'SUCCESS') location.reload();
    else alert('Gagal: ' + (res.data.message || 'Unknown'));
  }).catch(e => alert('Error: ' + e.message));
};

const formatBytes = (bytes) => {
  if (!bytes || bytes === 0) return '0 MB';
  if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
  return Math.round(bytes / 1024) + ' KB';
};
const formatDate = (ts) => ts ? new Date(ts * 1000).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
</script>
