<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationEllipsis,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'

const props = defineProps<{
  pagination: {
    data: any[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
    path: string
    query?: Record<string, any>
  }
}>()

const getQueryString = (page: number) => {
  const params = new URLSearchParams(props.pagination.query || {})
  params.set('page', page.toString())
  return `${props.pagination.path}?${params.toString()}`
}

const visit = (url: string | null) => {
  if (!url) return

  router.visit(url, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
}
</script>

<template>
  <Pagination>
    <PaginationContent class="flex items-center gap-1">

      <!-- Previous -->
      <PaginationPrevious
        :class="{ 'opacity-50 pointer-events-none': pagination.current_page === 1 }"
        @click="visit(getQueryString(pagination.current_page - 1))"
      />

      <!-- Pages -->
      <template v-for="n in pagination.last_page" :key="n">

        <PaginationItem v-if="n === pagination.current_page">
          <button class="px-3 py-1 rounded bg-black text-white">
            {{ n }}
          </button>
        </PaginationItem>

        <PaginationItem v-else>
          <button
            class="px-3 py-1 rounded hover:bg-gray-200"
            @click="visit(getQueryString(n))"
          >
            {{ n }}
          </button>
        </PaginationItem>

      </template>

      <!-- Next -->
      <PaginationNext
        :class="{ 'opacity-50 pointer-events-none': pagination.current_page === pagination.last_page }"
        @click="visit(getQueryString(pagination.current_page + 1))"
      />

    </PaginationContent>
  </Pagination>
</template>