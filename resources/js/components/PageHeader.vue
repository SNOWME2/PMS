<script setup>
import { computed } from 'vue'
import { Calendar, Plus, Bell } from 'lucide-vue-next'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'

defineProps({
  title: String,
  subtitle: String
})

const page = usePage()
const showAddProperty = computed(() =>
  page.component?.startsWith('Properties/')
)
</script>

<template>
  <header class="h-[54px] flex items-center justify-between px-6 border-b border-border shrink-0 bg-background">

    <!-- LEFT SIDE -->
    <div>
      <h1 class="text-[15px] font-semibold text-foreground">
        {{ title }}
      </h1>

      <p v-if="subtitle" class="text-[11.5px] text-muted-foreground">
        {{ subtitle }}
      </p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="flex items-center gap-2">

      <Button variant="outline" size="sm" class="h-8 text-[12.5px] gap-1.5">
        <Calendar :size="13" />
        June 2025
      </Button>

   
      <Button
        v-if="showAddProperty"
        size="sm"
        class="h-8 text-[12.5px] gap-1.5"
        @click="router.visit(route('properties.create'))"
      >
        <Plus :size="13" />
        Add property
        
      </Button>
         <Button v-else size="sm" class="h-8 text-[12.5px] gap-1.5">
        <Plus :size="13" />
        New Request
      </Button>


      <button class="relative w-8 h-8 flex items-center justify-center rounded-lg hover:bg-accent transition-colors">
        <Bell :size="15" class="text-muted-foreground" />
        <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-primary" />
      </button>

  
    </div>
  </header>
</template>