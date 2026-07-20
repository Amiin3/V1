<template>
  <Link class="block w-full ps-3 pe-4 py-2 text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 focus:outline-none focus:text-gray-800 focus:bg-gray-50 transition duration-150 ease-in-out">
    <slot />
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
