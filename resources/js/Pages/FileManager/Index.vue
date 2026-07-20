<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow px-4 py-3 flex justify-between items-center">
      <h1 class="text-xl font-bold">📁 File Manager</h1>
      <div>
        <span class="mr-4" v-if="$page.props.auth && $page.props.auth.user">
          {{ $page.props.auth.user.name }}
        </span>
        <Link href="/logout" method="post" as="button" class="text-red-600 font-semibold hover:underline">Logout</Link>
      </div>
    </nav>

    <div class="max-w-6xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Action Bar: Upload & Create Folder -->
      <div class="bg-white p-4 rounded shadow mb-4 flex flex-wrap gap-4 items-center">
        <!-- Buat Folder -->
        <form @submit.prevent="createFolder" class="flex space-x-2">
          <input v-model="folderForm.name" type="text" placeholder="Nama Folder Baru" class="border rounded p-2 text-sm focus:ring focus:ring-blue-200" required>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm disabled:opacity-50" :disabled="folderForm.processing">
            Buat Folder
          </button>
        </form>

        <div class="h-8 border-l border-gray-300"></div>

        <!-- Upload File -->
        <form @submit.prevent="uploadFile" class="flex space-x-2">
          <input type="file" @change="e => fileForm.file = e.target.files[0]" class="border rounded p-1.5 text-sm file:mr-4 file:py-1 file:px-3 file:border-0 file:bg-gray-100 file:rounded" required>
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm disabled:opacity-50" :disabled="fileForm.processing">
            Upload File
          </button>
        </form>
      </div>

      <!-- Main File List -->
      <div class="bg-white rounded shadow overflow-hidden">
        <!-- Breadcrumb / Current Path -->
        <div class="p-4 bg-gray-50 border-b flex items-center space-x-2 text-sm text-gray-600">
          <strong>Lokasi:</strong> 
          <Link href="/files" class="hover:text-blue-600">Home</Link>
          <span v-if="currentPath">/ {{ currentPath }}</span>
        </div>
        
        <!-- Tabel/List File -->
        <ul v-if="items && items.length > 0" class="divide-y divide-gray-200">
          <li v-for="item in items" :key="item.path" class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
            <div class="flex items-center space-x-3">
              <span class="text-2xl">{{ item.type === 'folder' ? '📁' : '📄' }}</span>
              
              <!-- Jika Folder, masuk ke dalamnya -->
              <Link v-if="item.type === 'folder'" :href="`/files?path=${item.path}`" class="text-blue-600 font-medium hover:underline cursor-pointer">
                {{ item.name }}
              </Link>
              
              <!-- Jika File biasa -->
              <span v-else class="text-gray-800 font-medium">{{ item.name }}</span>
            </div>
            
            <div class="space-x-4">
              <button @click="deleteItem(item.path)" class="text-red-500 text-sm font-semibold hover:underline">Hapus</button>
            </div>
          </li>
        </ul>
        
        <!-- State Kosong -->
        <div v-else class="p-12 text-center text-gray-500">
          <div class="text-4xl mb-3">📭</div>
          <p>Folder ini kosong.</p>
          <p class="text-sm">Silakan buat folder baru atau upload file.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  currentPath: String,
  items: Array,
  breadcrumbs: Array,
});

// Setup Form untuk Create Folder
const folderForm = useForm({
  name: '',
  path: props.currentPath || ''
});

const createFolder = () => {
  folderForm.path = props.currentPath || '';
  folderForm.post('/files/create-folder', {
    preserveScroll: true,
    onSuccess: () => folderForm.reset('name'),
  });
};

// Setup Form untuk Upload File
const fileForm = useForm({
  file: null,
  path: props.currentPath || ''
});

const uploadFile = () => {
  fileForm.path = props.currentPath || '';
  // Pastikan URL upload menggunakan path yg kita definisikan di web.php
  fileForm.post('/files/upload', {
    preserveScroll: true,
    onSuccess: () => fileForm.reset('file'),
  });
};

// Fungsi Delete
const deleteItem = (path) => {
  if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
    // Encode path untuk berjaga-jaga jika ada spasi pada nama file/folder
    router.delete(`/files/${encodeURIComponent(path)}`);
  }
};
</script>
