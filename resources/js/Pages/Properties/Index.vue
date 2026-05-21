<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import AppSidebar from '@/components/AppSidebar.vue'
import PageHeader from '@/components/PageHeader.vue'
import { route } from 'ziggy-js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Building2,
  Plus,
  Search,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
  Home,
  MapPin,
  Users,
  TrendingUp,
} from 'lucide-vue-next'

// ── Props from Inertia ────────────────────────────────────────────────────────
interface Unit {
  id: number
  status: 'vacant' | 'occupied' | 'maintenance' | 'reserved'
}

interface Property {
  id: number
  name: string
  address: string
  city: string
  type: 'residential' | 'commercial'
  status: string
  total_units: number
  occupied_units: number
  photo: string | null
  units: Unit[]
}



const props = defineProps<{
  properties: Property[]
  filters: { search?: string; type?: string; status?: string }
}>()


// ── Local state ───────────────────────────────────────────────────────────────
const search  = ref(props.filters.search  ?? '')
const type    = ref(props.filters.type    ?? 'all')
const status  = ref(props.filters.status  ?? 'all')
const viewMode = ref<'grid' | 'list'>('grid')

// ── Helpers ───────────────────────────────────────────────────────────────────
const occupancyPct = (p: Property) =>
  p.total_units ? Math.round((p.occupied_units / p.total_units) * 100) : 0

const vacantCount = (p: Property) =>
  p.units?.filter(u => u.status === 'vacant').length ?? (p.total_units - p.occupied_units)

const applyFilters = () => {
  router.get(route('properties.index'), {
    search: search.value || undefined,
    type:   type.value   !== 'all' ? type.value   : undefined,
    status: status.value !== 'all' ? status.value : undefined,
  }, { preserveState: true, replace: true })
}

const deleteProperty = (id: number) => {
  if (confirm('Delete this property? This cannot be undone.'))
    router.delete(route('properties.destroy', id))
}

const typeLabel: Record<string, string> = {
  residential: 'Residential',
  commercial:  'Commercial',
}

const statusColor: Record<string, string> = {
  vacant:    'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
  occupied:  'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900',
  maintenance: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900',
  reserved:  'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:border-violet-900',
}

// Summary stats
const totalUnits    = computed(() => props.properties.reduce((s, p) => s + p.total_units, 0))
const totalOccupied = computed(() => props.properties.reduce((s, p) => s + p.occupied_units, 0))
const overallPct    = computed(() => totalUnits.value ? Math.round(totalOccupied.value / totalUnits.value * 100) : 0)

</script>

<template>
  

    <div class="flex h-screen bg-background overflow-hidden">
    <AppSidebar/>
  
     <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <PageHeader title="Properties" subtitle="Manage your properties and units" />
 
  <div class="flex flex-col gap-6 p-6">

    <!-- ── Page header ──────────────────────────────────────────────────── -->
   

    <!-- ── Summary stat strip ───────────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-3">
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Properties</p>
        <p class="text-2xl font-bold text-foreground mt-0.5">{{ properties.length }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Total units</p>
        <p class="text-2xl font-bold text-foreground mt-0.5">{{ totalUnits }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Occupied</p>
        <p class="text-2xl font-bold text-foreground mt-0.5">{{ totalOccupied }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Occupancy rate</p>
        <p class="text-2xl font-bold text-foreground mt-0.5">{{ overallPct }}%</p>
      </div>
    </div>

    <!-- ── Filters ──────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3 flex-wrap">
      <div class="relative flex-1 min-w-50 max-w-xs">
        <Search :size="13" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="search"
          placeholder="Search properties…"
          class="pl-8 h-9 text-[13px]"
          @keydown.enter="applyFilters"
        />
      </div>

      <Select v-model="type" @update:model-value="applyFilters">
        <SelectTrigger class="w-35 h-9 text-[13px]">
          <SelectValue placeholder="Type" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All types</SelectItem>
          <SelectItem value="residential">Residential</SelectItem>
          <SelectItem value="commercial">Commercial</SelectItem>
        </SelectContent>
      </Select>

      <Select v-model="status" @update:model-value="applyFilters">
        <SelectTrigger class="w-40 h-9 text-[13px]">
          <SelectValue placeholder="Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem value="has_vacancy">Has vacancy</SelectItem>
          <SelectItem value="full">Fully occupied</SelectItem>
        </SelectContent>
      </Select>

      <Button variant="outline" size="sm" class="h-9 ml-auto" @click="applyFilters">
        Apply
      </Button>
    </div>

    <!-- ── Empty state ──────────────────────────────────────────────────── -->
    <div
      v-if="properties.length === 0"
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <div class="w-14 h-14 rounded-2xl bg-muted flex items-center justify-center mb-4">
        <Building2 :size="24" class="text-muted-foreground" />
      </div>
      <p class="text-[15px] font-medium text-foreground">No properties found</p>
      <p class="text-[13px] text-muted-foreground mt-1">Add your first property to get started.</p>
      <Button class="mt-4 gap-2" @click="router.visit(route('properties.create'))">
        <Plus :size="14" /> Add property
      </Button>
    </div>

    <!-- ── Property grid ─────────────────────────────────────────────────── -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="property in properties"
        :key="property.id"
        class="bg-card border border-border rounded-xl overflow-hidden hover:border-border/80 hover:shadow-sm transition-all group"
      >
        <!-- Cover photo / placeholder -->
        <div class="relative h-36 bg-muted overflow-hidden">
          <img
            v-if="property.photo"
            :src="`/storage/${property.photo}`"
            :alt="property.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <Building2 :size="36" class="text-muted-foreground/30" />
          </div>

          <!-- Type badge -->
          <div class="absolute top-3 left-3">
            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-background/90 text-foreground border border-border/50 backdrop-blur-sm">
              {{ typeLabel[property.type] ?? property.type }}
            </span>
          </div>

          <!-- Dropdown -->
          <div class="absolute top-3 right-3">
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  variant="secondary"
                  size="icon"
                  class="w-7 h-7 bg-background/90 hover:bg-background backdrop-blur-sm"
                >
                  <MoreHorizontal :size="14" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-44">
                <DropdownMenuItem @click="router.visit(route('properties.show', property.id))">
                  <Eye :size="13" class="mr-2" /> View
                </DropdownMenuItem>
                <DropdownMenuItem @click="router.visit(route('properties.edit', property.id))">
                  <Pencil :size="13" class="mr-2" /> Edit
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  class="text-destructive focus:text-destructive"
                  @click="deleteProperty(property.id)"
                >
                  <Trash2 :size="13" class="mr-2" /> Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>

        <!-- Card body -->
        <div class="p-4">
          <h3 class="text-[14px] font-semibold text-foreground truncate">{{ property.name }}</h3>
          <div class="flex items-center gap-1 mt-1">
            <MapPin :size="11" class="text-muted-foreground shrink-0" />
            <p class="text-[12px] text-muted-foreground truncate">{{ property.address }}, {{ property.city }}</p>
          </div>

          <!-- Occupancy bar -->
          <div class="mt-3 space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-[11.5px] text-muted-foreground">Occupancy</span>
              <span class="text-[11.5px] font-semibold text-foreground">{{ occupancyPct(property) }}%</span>
            </div>
            <div class="h-1.5 bg-muted rounded-full overflow-hidden">
              <div
                class="h-full bg-primary rounded-full transition-all duration-500"
                :style="{ width: `${occupancyPct(property)}%` }"
              />
            </div>
          </div>

          <!-- Unit counts -->
          <div class="flex items-center justify-between mt-3 pt-3 border-t border-border">
            <div class="flex items-center gap-1.5">
              <Home :size="12" class="text-muted-foreground" />
              <span class="text-[12px] text-muted-foreground">
                {{ property.total_units }} units
              </span>
            </div>
            <div class="flex gap-1.5">
              <span class="text-[11px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 font-medium">
                {{ vacantCount(property) }} vacant
              </span>
              <span class="text-[11px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 font-medium">
                {{ property.occupied_units }} occupied
              </span>
            </div>
          </div>

          <Button
            variant="outline"
            size="sm"
            class="w-full mt-3 h-8 text-[12.5px]"
            @click="router.visit(route('properties.show', property.id))"
          >
            View property
          </Button>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
</template>