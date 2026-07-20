<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header MyXL Style -->
    <div class="bg-blue-600 text-white px-4 py-4 flex items-center space-x-3">
      <Link href="/" class="p-2 -ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </Link>
      <div>
        <h1 class="text-lg font-bold">Akrab</h1>
        <p class="text-xs opacity-80">Family Plan</p>
      </div>
    </div>
    <div class="max-w-lg mx-auto p-4 space-y-4">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">{{ error }}</div>
      <div v-if="familyData">
        <!-- Card Info Plan -->
        <div class="bg-white rounded-2xl shadow p-5">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-gray-500">Paket</p>
              <p class="font-semibold text-gray-800">{{ familyData.plan_type }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-500">Berakhir</p>
              <p class="font-semibold text-gray-800">{{ formatDate(familyData.end_date) }}</p>
            </div>
          </div>
          <div class="mt-4 bg-gray-100 rounded-xl p-3">
            <div class="flex justify-between text-sm">
              <span>Kuota Bersama</span>
              <span class="font-semibold">{{ formatBytes(familyData.remaining_quota) }} / {{ formatBytes(familyData.total_quota) }}</span>
            </div>
            <div class="w-full bg-gray-300 rounded-full h-2 mt-2 overflow-hidden">
              <div class="bg-blue-500 h-2 rounded-full" :style="{ width: usagePercent + '%' }"></div>
            </div>
          </div>
        </div>
        <!-- Anggota -->
        <h2 class="font-semibold text-gray-800 px-1">Anggota ({{ filledSlots }}/{{ familyData.members.length }})</h2>
        <div class="space-y-3">
          <div v-for="(member, idx) in familyData.members" :key="member.slot_id" class="bg-white rounded-2xl shadow p-4">
            <div class="flex justify-between items-start">              <div class="flex-1">
                <div class="flex items-center space-x-2">
                  <span class="font-bold text-gray-800">{{ member.msisdn || 'Slot Kosong' }}</span>
                  <span class="text-xs px-2 py-0.5 rounded-full" :class="member.member_type === 'PARENT' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">{{ member.member_type }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ member.alias || 'Tanpa alias' }}</p>
                <div class="mt-2 bg-gray-50 rounded-lg p-2">
                  <div class="flex justify-between text-xs text-gray-600">
                    <span>Pemakaian</span>
                    <span>{{ formatBytes(member.usage?.quota_used) }} / {{ formatBytes(member.usage?.quota_allocated) }}</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 overflow-hidden">
                    <div class="bg-orange-400 h-1.5 rounded-full" :style="{ width: memberUsagePercent(member) + '%' }"></div>
                  </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Kesempatan tambah: {{ member.add_chances }}/{{ member.total_add_chances }}</p>
              </div>
            </div>
            <div class="flex space-x-2 mt-3">
              <button v-if="member.msisdn && member.member_type !== 'PARENT'" @click="openLimitModal(member)" class="flex-1 text-sm bg-blue-50 text-blue-700 py-2 rounded-xl font-medium">Atur Kuota</button>
              <button v-if="member.msisdn && member.member_type !== 'PARENT'" @click="confirmRemove(member)" class="flex-1 text-sm bg-red-50 text-red-700 py-2 rounded-xl font-medium">Hapus</button>
              <button v-if="!member.msisdn" @click="openChangeModal(member)" class="flex-1 text-sm bg-green-50 text-green-700 py-2 rounded-xl font-medium">Isi Slot</button>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal Isi Slot -->
      <div v-if="showChangeModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showChangeModal = false">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
          <h3 class="font-bold text-lg mb-4">Isi Slot Keluarga</h3>
          <p class="text-xs text-red-600 mb-3">⚠️ Aksi ini tidak dapat dibatalkan. Pastikan nomor tujuan benar.</p>
          <input v-model="changeForm.msisdn" placeholder="Nomor tujuan (62xxx)" class="w-full border rounded-xl p-3 text-sm mb-2">
          <input v-model="changeForm.parentAlias" placeholder="Nama Anda" class="w-full border rounded-xl p-3 text-sm mb-2">
          <input v-model="changeForm.childAlias" placeholder="Nama anggota baru" class="w-full border rounded-xl p-3 text-sm mb-4">
          <div class="flex space-x-2">
            <button @click="submitChangeMember" class="bg-blue-600 text-white px-4 py-3 rounded-xl flex-1 font-semibold">Ya, Isi Slot</button>
            <button @click="showChangeModal = false" class="bg-gray-200 px-4 py-3 rounded-xl">Batal</button>
          </div>
          <p v-if="changeMsg" class="mt-2 text-xs" :class="changeError ? 'text-red-600' : 'text-green-600'">{{ changeMsg }}</p>
        </div>
      </div>
      <!-- Modal Atur Kuota -->
      <div v-if="showLimitModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showLimitModal = false">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
          <h3 class="font-bold text-lg mb-2">Atur Kuota</h3>
          <p class="text-sm text-gray-600 mb-3">Untuk {{ selectedMember?.msisdn }}</p>
          <p class="text-xs text-gray-500 mb-3">Alokasi saat ini: {{ formatBytes(selectedMember?.usage?.quota_allocated) }}</p>
          <input v-model="limitForm.newLimitMB" type="number" placeholder="Kuota baru (MB)" class="w-full border rounded-xl p-3 text-sm mb-4">
          <div class="flex space-x-2">
            <button @click="submitSetLimit" class="bg-blue-600 text-white px-4 py-3 rounded-xl flex-1 font-semibold">Simpan</button>
            <button @click="showLimitModal = false" class="bg-gray-200 px-4 py-3 rounded-xl">Batal</button>
          </div>
          <p v-if="limitMsg" class="mt-2 text-xs" :class="limitError ? 'text-red-600' : 'text-green-600'">{{ limitMsg }}</p>
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
  familyData: Object,
  error: String
});
const filledSlots = computed(() => props.familyData?.members.filter(m => m.msisdn).length || 0);
const usagePercent = computed(() => {
  if (!props.familyData) return 0;
  const total = props.familyData.total_quota || 0;
  const used = total - (props.familyData.remaining_quota || 0);
  return total > 0 ? Math.min(100, (used / total) * 100) : 0;
});
const memberUsagePercent = (member) => {
  const alloc = member.usage?.quota_allocated || 0;
  const used = member.usage?.quota_used || 0;
  return alloc > 0 ? Math.min(100, (used / alloc) * 100) : 0;
};
// Change member with double confirmation
const showChangeModal = ref(false);
const selectedSlot = ref(null);
const changeForm = ref({ msisdn: '', parentAlias: '', childAlias: '' });
const changeMsg = ref('');
const changeError = ref(false);
const openChangeModal = (member) => {
  selectedSlot.value = member;
  changeForm.value = { msisdn: '', parentAlias: '', childAlias: '' };
  changeMsg.value = '';
  showChangeModal.value = true;
};
const submitChangeMember = async () => {
  if (!confirm('⚠️ PERINGATAN: Mengisi slot tidak dapat diulang. Lanjutkan?')) return;
  if (!confirm('Benar-benar yakin? Nomor yang dimasukkan akan permanen.')) return;
  try {
    const valRes = await axios.post('/family/validate-msisdn', { msisdn: changeForm.value.msisdn });
    if (valRes.data.status?.toLowerCase() !== 'success') {      changeMsg.value = 'Nomor tidak valid.';
      changeError.value = true;
      return;
    }
    const res = await axios.post('/family/change-member',
{
      slot_id: selectedSlot.value.slot_id,
      family_member_id: selectedSlot.value.family_member_id,
      msisdn: changeForm.value.msisdn,
      parent_alias: changeForm.value.parentAlias,
      child_alias: changeForm.value.childAlias
    });
    if (res.data.status === 'SUCCESS') {
      changeMsg.value = 'Berhasil!';
      changeError.value = false;
      setTimeout(() => location.reload(), 1500);
    } else {
      changeMsg.value = 'Gagal: ' + (res.data.message || 'Unknown');
      changeError.value = true;
    }
  } catch (e) {
    changeMsg.value = 'Error: ' + e.message;
    changeError.value = true;
  }
};
// Remove member with double confirmation
const confirmRemove = (member) => {
  if (!confirm(`⚠️ Hapus ${member.msisdn} dari keluarga? Tindakan ini tidak bisa diulang.`)) return;
  if (!confirm('Benar-benar yakin? Ini kesempatan terakhir untuk membatalkan.')) return;
  axios.post('/family/remove-member', { family_member_id:
member.family_member_id })
    .then(res => {
      if (res.data.status === 'SUCCESS') location.reload();
      else alert('Gagal: ' + (res.data.message || 'Unknown'));
    }).catch(e => alert('Error: ' + e.message));
};
// Set quota limit with confirmation
const showLimitModal = ref(false);
const selectedMember = ref(null);
const limitForm = ref({ newLimitMB: 0 });
const limitMsg = ref('');
const limitError = ref(false);
const openLimitModal = (member) => {
  selectedMember.value = member;
  limitForm.value.newLimitMB = 0;
  limitMsg.value = '';
  showLimitModal.value = true;
};
const submitSetLimit = async () => {
  if (!confirm('⚠️ Ubah batas kuota anggota? Pastikan angka sudah benar.')) return;
  try {
    const res = await axios.post('/family/set-quota-limit', {
      family_member_id: selectedMember.value.family_member_id,
      new_allocation_mb: limitForm.value.newLimitMB,
      original_allocation: selectedMember.value.usage?.quota_allocated || 0
    });
    if (res.data.status === 'SUCCESS') {
      limitMsg.value = 'Kuota diupdate!';
      limitError.value = false;
      setTimeout(() => location.reload(), 1500);
    } else {
      limitMsg.value = 'Gagal: ' + (res.data.message || 'Unknown');
      limitError.value = true;
    }
  } catch (e) {
    limitMsg.value = 'Error: ' + e.message;
    limitError.value = true;
  }
};
const formatBytes = (bytes) => {
  if (!bytes || bytes === 0) return '0 MB';
  if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
  return Math.round(bytes / 1024) + ' KB';
};
const formatDate = (ts) => ts ? new Date(ts * 1000).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
</script>
