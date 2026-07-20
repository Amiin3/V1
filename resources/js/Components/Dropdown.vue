<template>
  <div class="relative">
    <div @click="open = !open">
      <slot name="trigger" />
    </div>
    <div v-if="open" class="absolute right-0 z-10 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
      <slot name="content" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
const open = ref(false);
</script>
