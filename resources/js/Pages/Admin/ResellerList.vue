<template>
  <div style="padding:40px;background:white;min-height:100vh">
    <h1>🔍 Debug Session</h1>
    <div style="background:#f0f4ff;padding:20px;border-radius:10px;margin-bottom:20px">
      <h3>Session User:</h3>
      <pre>{{ JSON.stringify(user, null, 2) }}</pre>
    </div>
    
    <h2>📋 Daftar Reseller</h2>
    <div v-if="sessions.length === 0">
      <p>Belum ada sesi tersimpan.</p>
    </div>
    <div v-else>
      <div v-for="(s, idx) in sessions" :key="idx" style="background:#f9fafb;padding:15px;margin:10px 0;border-radius:8px;display:flex;justify-content:space-between;align-items:center">
        <div>
          <strong>{{ s.phone_number }}</strong>
          <p style="color:#6b7280;font-size:12px">Terakhir: {{ s.updated_at }}</p>
        </div>
        <form :action="route('login.auto')" method="POST" style="display:inline">
          <input type="hidden" name="_token" :value="csrfToken">
          <input type="hidden" name="number" :value="s.phone_number">
          <button type="submit" style="background:#2563eb;color:white;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:bold">➡️ Login</button>
        </form>
      </div>
    </div>
    <p style="margin-top:20px"><a href="/logout" style="color:#dc2626">Keluar</a></p>
  </div>
</template>

<script setup>
defineProps({ sessions: Array, user: Object, csrfToken: String });
</script>
