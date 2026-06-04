<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import AppSidebar from '@/components/AppSidebar.vue'
import PageHeader from '@/components/PageHeader.vue'
import AppPagination from '@/components/AppPagination.vue'
import { route } from 'ziggy-js'
import { debounce } from 'lodash-es'
import { watch } from 'vue'
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
  BedSingle,
  BarChart2,
  BadgeCheck,
  X,
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
  vacant_units: number
  photo: string | null
  units: Unit[]
}



const props = defineProps<{
  properties: {
    data: Property[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
    path: string
    query?: Record<string, any>
  }
  filters: { search?: string; type?: string; status?: string }
}>()


// ── Local state ───────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
const type = ref(props.filters.type ?? 'all')
const status = ref(props.filters.status ?? 'all')
const viewMode = ref<'grid' | 'list'>('grid')

// ── Helpers ───────────────────────────────────────────────────────────────────
const occupancyPct = (p: Property) =>
  p.total_units ? Math.round((p.occupied_units / p.total_units) * 100) : 0

const vacantCount = (p: Property) =>
  p.units?.filter(u => u.status === 'vacant').length ?? (p.total_units - p.occupied_units)

const applyFilters = () => {
  router.get(route('properties.index'), {
    search: search.value || undefined,
    type: type.value !== 'all' ? type.value : undefined,
    status: status.value !== 'all' ? status.value : undefined,
  }, { preserveState: true, replace: true })
}

const debouncedSearch = debounce(() => {
  applyFilters()
}, 100)

watch(search, () => {
  debouncedSearch()
})

const deleteProperty = (id: number) => {
  if (confirm('Delete this property? This cannot be undone.'))
    router.delete(route('properties.destroy', id))
}

const typeLabel: Record<string, string> = {
  residential: 'Residential',
  commercial: 'Commercial',
}

const statusColor: Record<string, string> = {
  vacant: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
  occupied: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900',
  maintenance: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900',
  reserved: 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:border-violet-900',
}

// Summary stats
const totalUnits = computed(() => props.properties.data.reduce((s, p) => s + p.total_units, 0))
const totalOccupied = computed(() => props.properties.data.reduce((s, p) => s + p.occupied_units, 0))
const overallPct = computed(() => totalUnits.value ? Math.round(totalOccupied.value / totalUnits.value * 100) : 0)
const totalVacant = computed(() => totalUnits.value - totalOccupied.value)

</script>

<template>
  <div class="flex h-screen bg-slate-50 dark:bg-background overflow-hidden">
    <AppSidebar />

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <PageHeader title="Properties" subtitle="Manage your properties and units" />

      <div class="flex flex-col gap-5 p-6 overflow-auto flex-1">

        <!-- ── Stat strip ── -->
        <div class="grid grid-cols-5 gap-3">

          <!-- Total Properties -->
          <div
            class="relative bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 shadow-sm overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50/80 to-transparent dark:from-muted/10" />
            <div class="relative flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-muted flex items-center justify-center flex-shrink-0">
                <Building2 :size="18" class="text-slate-500" />
              </div>
              <div>
                <p class="text-[23px] font-bold text-foreground leading-none">{{ properties.data.length }}</p>
                <p class="text-[10.5px] font-medium text-muted-foreground mt-1">Total Properties</p>
              </div>
            </div>
          </div>

          
          <!-- Total Units -->
          <div
            class="relative bg-white dark:bg-card border border-indigo-100 dark:border-indigo-900/40 rounded-2xl p-4 shadow-sm overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/60 to-transparent dark:from-indigo-950/20" />
            <div class="relative flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/50 flex items-center justify-center flex-shrink-0">
                <BedSingle :size="18" class="text-indigo-600" />
              </div>
              <div>
                <p class="text-[23px] font-bold text-indigo-600 leading-none">{{ totalUnits }}</p>
                <p class="text-[10.5px] font-medium text-muted-foreground mt-1">Total Units</p>
              </div>
            </div>
          </div>
<!-- Vacant -->
          <div
            class="relative bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 shadow-sm overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50/80 to-transparent dark:from-muted/10" />
            <div class="relative flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-muted flex items-center justify-center flex-shrink-0">
                <Building2 :size="18" class="text-slate-500" />
              </div>
              <div>
                <p class="text-[23px] font-bold text-foreground leading-none">{{ totalVacant }}</p>
                <p class="text-[10.5px] font-medium text-muted-foreground mt-1">Vacant Units</p>
              </div>
            </div>
          </div>
          <!-- Occupied -->
          <div
            class="relative bg-white dark:bg-card border border-blue-100 dark:border-blue-900/40 rounded-2xl p-4 shadow-sm overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/60 to-transparent dark:from-blue-950/20" />
            <div class="relative flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center flex-shrink-0">
                <BadgeCheck :size="18" class="text-blue-600" />
              </div>
              <div>
                <p class="text-[23px] font-bold text-blue-600 leading-none">{{ totalOccupied }}</p>
                <p class="text-[10.5px] font-medium text-muted-foreground mt-1">Occupied</p>
              </div>
            </div>
          </div>

          <!-- Occupancy Rate -->
          <div
            class="relative bg-white dark:bg-card border border-emerald-100 dark:border-emerald-900/40 rounded-2xl p-4 shadow-sm overflow-hidden">
            <div
              class="absolute inset-0 bg-linear-to-br from-emerald-50/60 to-transparent dark:from-emerald-950/20" />
            <div class="relative flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center shrink-0">
                <BarChart2 :size="18" class="text-emerald-600" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[23px] font-bold text-emerald-600 leading-none">{{ overallPct }}<span
                    class="text-[14px] font-semibold">%</span></p>
                <p class="text-[10.5px] font-medium text-muted-foreground mt-1">Occupancy Rate</p>
              </div>
            </div>
            <!-- Mini progress bar -->
            <div class="relative mt-3 h-1 bg-emerald-100 dark:bg-emerald-950/50 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full transition-all duration-700"
                :style="{ width: `${overallPct}%` }" />
            </div>
          </div>
        </div>

        <!-- ── Filters ── -->
        <div
          class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl px-4 py-3 shadow-sm flex items-center gap-3 flex-wrap">
          <div class="relative flex-1 min-w-[180px] max-w-sm">
            <Search :size="12" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Search properties…"
              class="pl-8 h-9 text-[12.5px] bg-slate-50 dark:bg-muted border-slate-200 dark:border-border rounded-lg"
              @keydown.enter="applyFilters" />
            <button v-if="search" type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              @click="search = ''; applyFilters()">
              <X :size="12" />
            </button>
          </div>

          <div class="h-5 w-px bg-slate-200 dark:bg-border hidden sm:block" />

          <Select v-model="type" @update:model-value="applyFilters">
            <SelectTrigger
              class="w-[140px] h-9 text-[12.5px] bg-slate-50 dark:bg-muted border-slate-200 dark:border-border rounded-lg">
              <SelectValue placeholder="All types" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All types</SelectItem>
              <SelectItem value="residential">Residential</SelectItem>
              <SelectItem value="commercial">Commercial</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="status" @update:model-value="applyFilters">
            <SelectTrigger
              class="w-[155px] h-9 text-[12.5px] bg-slate-50 dark:bg-muted border-slate-200 dark:border-border rounded-lg">
              <SelectValue placeholder="All statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All statuses</SelectItem>
              <SelectItem value="has_vacancy">Has vacancy</SelectItem>
              <SelectItem value="full">Fully occupied</SelectItem>
            </SelectContent>
          </Select>

          <button @click="applyFilters"
            class="ml-auto h-9 px-5 text-[12.5px] font-semibold bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-lg transition-all">
            Apply
          </button>
        </div>

        <!-- ── Empty state ── -->
        <div v-if="properties.data.length === 0"
          class="flex flex-col items-center justify-center py-28 text-center bg-white dark:bg-card border border-dashed border-slate-200 dark:border-border rounded-2xl">
          <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-muted flex items-center justify-center mb-4">
            <Building2 :size="28" class="text-slate-400" />
          </div>
          <p class="text-[15px] font-bold text-foreground">No properties found</p>
          <p class="text-[12.5px] text-muted-foreground mt-1.5 mb-6 max-w-[220px] leading-relaxed">
            Add your first property to start managing your portfolio.
          </p>
          <div v-if="status === 'all' && type === 'all'">
            <button @click="router.visit(route('properties.create'))"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[13px] font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-md shadow-indigo-200">
              <Plus :size="14" /> Add Property
            </button>
          </div>
        </div>

        <!-- ── Property grid ── -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <div v-for="property in properties.data" :key="property.id"
            class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group">
            <!-- Cover photo -->
            <div class="relative h-40 bg-slate-100 dark:bg-muted overflow-hidden">
              <img v-if="property.photo" :src="`/storage/${property.photo}`" :alt="property.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2">
                <Building2 :size="32" class="text-slate-300 dark:text-muted-foreground/30" />
                <p class="text-[11px] text-slate-400 dark:text-muted-foreground/50 font-medium">No photo</p>
              </div>

              <!-- Gradient overlay -->
              <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent" />

              <!-- Type badge -->
              <div class="absolute top-3 left-3">
                <span
                  class="text-[10.5px] font-semibold px-2.5 py-1 rounded-full bg-white/90 dark:bg-card/90 text-foreground border border-white/50 backdrop-blur-sm shadow-sm">
                  {{ typeLabel[property.type] ?? property.type }}
                </span>
              </div>

              <!-- Dropdown -->
              <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <button
                      class="w-7 h-7 flex items-center justify-center bg-white/90 dark:bg-card/90 hover:bg-white dark:hover:bg-card backdrop-blur-sm rounded-lg shadow-sm border border-white/50 transition-colors">
                      <MoreHorizontal :size="13" />
                    </button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" class="w-44 rounded-xl shadow-lg">
                    <DropdownMenuItem class="text-[13px] rounded-lg"
                      @click="router.visit(route('properties.show', property.id))">
                      <Eye :size="13" class="mr-2 text-muted-foreground" /> View
                    </DropdownMenuItem>
                    <DropdownMenuItem class="text-[13px] rounded-lg"
                      @click="router.visit(route('properties.edit', property.id))">
                      <Pencil :size="13" class="mr-2 text-muted-foreground" /> Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem class="text-[13px] rounded-lg text-destructive focus:text-destructive"
                      @click="deleteProperty(property.id)">
                      <Trash2 :size="13" class="mr-2" /> Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>

              <!-- Bottom-left: unit count pill over image -->
              <div class="absolute bottom-3 left-3">
                <span
                  class="inline-flex items-center gap-1 text-[10.5px] font-semibold px-2 py-0.5 rounded-full bg-black/40 text-white backdrop-blur-sm">
                  <Home :size="9" /> {{ property.total_units }} units
                </span>
              </div>
            </div>

            <!-- Card body -->
            <div class="p-4">
              <h3 class="text-[14.5px] font-bold text-foreground truncate">{{ property.name }}</h3>
              <div class="flex items-center gap-1 mt-0.5">
                <MapPin :size="10" class="text-muted-foreground flex-shrink-0" />
                <p class="text-[11.5px] text-muted-foreground truncate">{{ property.address }}, {{ property.city }}</p>
              </div>

              <!-- Occupancy bar -->
              <div class="mt-3.5 space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-medium text-muted-foreground">Occupancy</span>
                  <span :class="[
                    'text-[11px] font-bold',
                    occupancyPct(property) >= 80 ? 'text-emerald-600' :
                      occupancyPct(property) >= 50 ? 'text-amber-600' : 'text-rose-500'
                  ]">{{ occupancyPct(property) }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 dark:bg-muted rounded-full overflow-hidden">
                  <div :class="[
                    'h-full rounded-full transition-all duration-700',
                    occupancyPct(property) >= 80 ? 'bg-emerald-500' :
                      occupancyPct(property) >= 50 ? 'bg-amber-400' : 'bg-rose-400'
                  ]" :style="{ width: `${occupancyPct(property)}%` }" />
                </div>
              </div>

              <!-- Unit badges -->
              <div class="flex items-center justify-between mt-3.5 pt-3.5 border-t border-slate-100 dark:border-border">
                <div class="flex gap-1.5">
                  <span
                    class="inline-flex items-center gap-1 text-[10.5px] font-semibold px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 border border-emerald-200 dark:border-emerald-900">
                    <Circle :size="6" class="fill-emerald-500 text-emerald-500" />
                    {{ vacantCount(property) }} vacant
                  </span>
                  <span
                    class="inline-flex items-center gap-1 text-[10.5px] font-semibold px-2 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-950/30 text-blue-700 border border-blue-200 dark:border-blue-900">
                    <Circle :size="6" class="fill-blue-500 text-blue-500" />
                    {{ property.occupied_units }} occupied
                  </span>
                </div>
              </div>

              <!-- CTA -->
              <button @click="router.visit(route('properties.show', property.id))"
                class="w-full mt-3 h-9 text-[12.5px] font-semibold text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-950/30 dark:hover:bg-indigo-600 border border-indigo-200 dark:border-indigo-900 hover:border-indigo-600 rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                View Property
                <ChevronRight :size="13" />
              </button>
            </div>
          </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="properties.last_page > 1" class="flex justify-center mt-2">
          <div class="w-full max-w-4xl flex items-center justify-center">
            <AppPagination :pagination="properties" />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>