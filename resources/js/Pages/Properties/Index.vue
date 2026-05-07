<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import PageHeader from '@/components/PageHeader.vue'
defineProps({
  properties: Array,
   pageTitle: String,
  pageSubtitle: String
})
</script>

<template>
  <div class="flex h-screen bg-background overflow-hidden">
    <AppSidebar />

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <PageHeader :title="pageTitle" :subtitle="pageSubtitle" />

    <div class="flex-1 p-12">
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="property in properties" :key="property.id" class="bg-white rounded-lg shadow p-4">
          <h2 class="text-xl font-semibold mb-2">{{ property.name }}</h2>
          <p class="text-gray-600 mb-4">{{ property.address }}</p>
          <ul>
            <li v-for="unit in property.units" :key="unit.id" class="border-t py-2">
              {{ unit.name }} - {{ unit.status }}
            </li>
          </ul>
        </div>
      </div>
    </div>
</div>
  
</div>
  </template>   