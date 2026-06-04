<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import AppSidebar from '@/components/AppSidebar.vue'
import PageHeader from '@/components/PageHeader.vue'
import {route} from 'ziggy-js'
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
  Home,
  Plus,
  Search,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
  ChevronRight,
  MapPin,
  Layers,
  CreditCard,
  Users,
  CheckCircle2,
  Circle,
  Wrench,
  Clock,
} from 'lucide-vue-next'

// ── Props from Inertia ────────────────────────────────────────────────────────
interface ActiveLease {
  id: number
  end_date: string
  tenant: { id: number; name: string }
}

interface Property {
  id: number
  name: string
  address: string
  city: string
}

interface Unit {
  id: number
  unit_number: string
  type: string
  floor_number: number
  size_sqm: number | null
  rent_price: number
  status: 'vacant' | 'occupied' | 'maintenance' | 'reserved'
  property: Property
  active_lease: ActiveLease | null
  maintenance_requests: { id: number; status: string }[]
}

const props = defineProps<{
  units: Unit[]
  properties: Property[]
  filters: { search?: string; property_id?: string; floor?: string; status?: string }
}>()

// ── Local state ───────────────────────────────────────────────────────────────
const search     = ref(props.filters.search       ?? '')
const propertyId = ref(props.filters.property_id  ?? 'all')
const floor      = ref(props.filters.floor       ?? 'all')
const status     = ref(props.filters.status      ?? 'all')

// ── Computed ───────────────────────────────────────────────────────────────────
const floors = computed(() => [
  ...new Set(props.units.map(u => u.floor_number))
].sort((a, b) => a - b))

const filteredUnits = computed(() => {
  return props.units.filter(u => {
    const matchProperty = propertyId.value === 'all' || u.property.id === Number(propertyId.value)
    const matchFloor = floor.value === 'all' || u.floor_number === Number(floor.value)
    const matchStatus = status.value === 'all' || u.status === status.value
    const matchSearch = ! search.value || 
      u.unit_number.toLowerCase().includes(search.value.toLowerCase()) ||
      u.type.toLowerCase().includes(search.value.toLowerCase()) ||
      u.property.name.toLowerCase().includes(search.value.toLowerCase())
    return matchProperty && matchFloor && matchStatus && matchSearch
  })
})

const unitStats = computed(() => ({
  total: props.units.length,
  vacant: props.units.filter(u => u.status === 'vacant').length,
  occupied: props.units.filter(u => u.status === 'occupied').length,
  maintenance: props.units.filter(u => u.status === 'maintenance').length,
  reserved: props.units.filter(u => u.status === 'reserved').length,
}))

// ── Helpers ───────────────────────────────────────────────────────────────────
const applyFilters = () => {
  router.get(route('units.index'), {
    search:       search.value || undefined,
    property_id:  propertyId.value !== 'all' ? propertyId.value : undefined,
    floor:        floor.value !== 'all' ? floor.value : undefined,
    status:       status.value !== 'all' ? status.value : undefined,
  }, { preserveState: true, replace: true })
}

const deleteUnit = (id: number) => {
  if (confirm('Delete this unit?'))
    router.delete(route('units.destroy', id))
}

const statusConfig: Record<string, { label: string; icon: any; pill: string }> = {
  vacant:      { label: 'Vacant',      icon: Circle,        pill: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900' },
  occupied:    { label: 'Occupied',    icon: CheckCircle2,  pill: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900' },
  maintenance: { label: 'Maintenance', icon: Wrench,        pill: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900' },
  reserved:    { label: 'Reserved',    icon: Clock,         pill: 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:border-violet-900' },
}

const fmt = (n: number) => '₱' + n.toLocaleString('en-PH')

const daysUntilExpiry = (endDate: string) => {
  const diff = new Date(endDate).getTime() - Date.now()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}
</script>

<template>
  <div class="flex h-screen bg-slate-50 dark:bg-background overflow-hidden">
    <AppSidebar />

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <PageHeader title="Units" subtitle="Manage your properties and units" />

      <div class="flex flex-col gap-6 p-6 overflow-auto flex-1">

        <!-- ── Breadcrumb ── -->
        <div class="flex items-center gap-1.5 text-[12px] text-muted-foreground">
          <Link :href="route('properties.index')"
            class="hover:text-foreground transition-colors flex items-center gap-1">
            <Home :size="12" /> Properties
          </Link>
          <ChevronRight :size="11" class="text-muted-foreground/50" />
          <span class="text-foreground font-medium">All Units</span>
        </div>

        <!-- ── Page header ── -->
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="text-xl font-bold text-foreground tracking-tight">All Units</h1>
            <p class="text-[12.5px] text-muted-foreground mt-0.5">
              Browse and manage units across all properties
            </p>
          </div>
          <button @click="router.visit(route('units.create'))"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm shadow-indigo-200">
            <Plus :size="15" />
            Add Unit
          </button>
        </div>

        <!-- ── Stat strip ── -->
        <div class="grid grid-cols-5 gap-3">
          <div
            class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 flex items-center gap-3 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-muted flex items-center justify-center flex-shrink-0">
              <Layers :size="18" class="text-slate-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-foreground leading-none">{{ unitStats.total }}</p>
              <p class="text-[11px] text-muted-foreground mt-1">Total Units</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 flex items-center gap-3 shadow-sm">
            <div
              class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center flex-shrink-0">
              <Circle :size="18" class="text-emerald-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-emerald-600 leading-none">{{ unitStats.vacant }}</p>
              <p class="text-[11px] text-muted-foreground mt-1">Vacant</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 flex items-center gap-3 shadow-sm">
            <div
              class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center flex-shrink-0">
              <CheckCircle2 :size="18" class="text-blue-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-blue-600 leading-none">{{ unitStats.occupied }}</p>
              <p class="text-[11px] text-muted-foreground mt-1">Occupied</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 flex items-center gap-3 shadow-sm">
            <div
              class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center flex-shrink-0">
              <Wrench :size="18" class="text-amber-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-amber-600 leading-none">{{ unitStats.maintenance }}</p>
              <p class="text-[11px] text-muted-foreground mt-1">Maintenance</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 flex items-center gap-3 shadow-sm">
            <div
              class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-950/40 flex items-center justify-center flex-shrink-0">
              <Clock :size="18" class="text-violet-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-violet-600 leading-none">{{ unitStats.reserved }}</p>
              <p class="text-[11px] text-muted-foreground mt-1">Reserved</p>
            </div>
          </div>
        </div>

        <!-- ── Filters ── -->
        <div
          class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl px-4 py-3 shadow-sm flex items-center gap-3 flex-wrap">
          <div class="relative flex-1 min-w-[200px] max-w-xs">
            <Search :size="13" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Search units…"
              class="pl-8 h-9 text-[13px] bg-slate-50 dark:bg-muted border-slate-200" @keydown.enter="applyFilters" />
          </div>

          <Select v-model="propertyId" @update:model-value="applyFilters">
            <SelectTrigger class="w-[180px] h-9 text-[13px] bg-slate-50 dark:bg-muted border-slate-200">
              <SelectValue placeholder="All properties" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All properties</SelectItem>
              <SelectItem v-for="p in properties" :key="p.id" :value="p.id">{{ p.name }}</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="floor" @update:model-value="applyFilters">
            <SelectTrigger class="w-[130px] h-9 text-[13px] bg-slate-50 dark:bg-muted border-slate-200">
              <SelectValue placeholder="All floors" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All floors</SelectItem>
              <SelectItem v-for="f in floors" :key="f" :value="f">Floor {{ f }}</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="status" @update:model-value="applyFilters">
            <SelectTrigger class="w-[140px] h-9 text-[13px] bg-slate-50 dark:bg-muted border-slate-200">
              <SelectValue placeholder="All statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All statuses</SelectItem>
              <SelectItem value="vacant">Vacant</SelectItem>
              <SelectItem value="occupied">Occupied</SelectItem>
              <SelectItem value="maintenance">Maintenance</SelectItem>
              <SelectItem value="reserved">Reserved</SelectItem>
            </SelectContent>
          </Select>

          <button @click="applyFilters"
            class="ml-auto h-9 px-4 text-[13px] font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
            Apply
          </button>
        </div>

        <!-- ── Empty state ── -->
        <div v-if="filteredUnits.length === 0"
          class="flex flex-col items-center justify-center py-24 text-center bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl shadow-sm">
          <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-muted flex items-center justify-center mb-4">
            <Home :size="28" class="text-slate-400" />
          </div>
          <p class="text-[15px] font-semibold text-foreground">No units found</p>
          <p class="text-[13px] text-muted-foreground mt-1 mb-5">Add your first unit or adjust your filters.</p>
          <button @click="router.visit(route('units.create'))"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
            <Plus :size="14" /> Add Unit
          </button>
        </div>

        <!-- ── Table ── -->
        <div v-else>

          <!-- Desktop -->
          <div
            class="hidden lg:block bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl overflow-hidden shadow-sm">
            <!-- Table Head -->
            <div
              class="grid grid-cols-7 gap-4 px-5 py-3 bg-slate-50 dark:bg-muted/50 border-b border-slate-100 dark:border-border">
              <div class="text-[11px] font-semibold text-slate-500 dark:text-muted-foreground uppercase tracking-wider">
                Unit</div>
              <div class="text-[11px] font-semibold text-slate-500 dark:text-muted-foreground uppercase tracking-wider">
                Property</div>
              <div class="text-[11px] font-semibold text-slate-500 dark:text-muted-foreground uppercase tracking-wider">
                Type</div>
              <div class="text-[11px] font-semibold text-slate-500 dark:text-muted-foreground uppercase tracking-wider">
                Status</div>
              <div class="text-[11px] font-semibold text-slate-500 dark:text-muted-foreground uppercase tracking-wider">
                Tenant</div>
              <div class="text-[11px] font-semibold text-slate-500 dark:text-muted-foreground uppercase tracking-wider">
                Rent</div>
              <div></div>
            </div>

            <!-- Table Rows -->
            <div class="divide-y divide-slate-50 dark:divide-border">
              <div v-for="unit in filteredUnits" :key="unit.id"
                class="grid grid-cols-7 gap-4 px-5 py-3.5 hover:bg-slate-50/80 dark:hover:bg-muted/30 transition-colors items-center group">
                <!-- Unit -->
                <div>
                  <p class="font-bold text-foreground text-[13.5px]">{{ unit.unit_number }}</p>
                  <p class="text-[11px] text-muted-foreground mt-0.5">Fl {{ unit.floor_number }} · {{ unit.type }}</p>
                </div>

                <!-- Property -->
                <div>
                  <Link :href="route('properties.show', unit.property.id)"
                    class="font-semibold text-indigo-600 hover:text-indigo-700 text-[13px] hover:underline transition-colors">
                    {{ unit.property.name }}
                  </Link>
                  <p class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                    <MapPin :size="10" /> {{ unit.property.city }}
                  </p>
                </div>

                <!-- Type/Size -->
                <div>
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-muted text-slate-600 dark:text-muted-foreground text-[11.5px] font-medium">
                    {{ unit.type }}
                  </span>
                  <p v-if="unit.size_sqm" class="text-[11px] text-muted-foreground mt-1">{{ unit.size_sqm }} m²</p>
                </div>

                <!-- Status -->
                <div>
                  <span
                    :class="['inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border', statusConfig[unit.status].pill]">
                    <component :is="statusConfig[unit.status].icon" :size="10" />
                    {{ statusConfig[unit.status].label }}
                  </span>
                </div>

                <!-- Tenant -->
                <div>
                  <template v-if="unit.active_lease">
                    <p class="font-semibold text-foreground text-[13px]">{{ unit.active_lease.tenant.name }}</p>
                    <p
                      :class="['text-[11px] mt-0.5', daysUntilExpiry(unit.active_lease.end_date) <= 30 ? 'text-red-500 font-semibold' : 'text-muted-foreground']">
                      Expires {{ new Date(unit.active_lease.end_date).toLocaleDateString('en-PH', {
                        month: 'short', day:
                      'numeric' }) }}
                    </p>
                  </template>
                  <template v-else>
                    <span class="text-muted-foreground text-[13px]">—</span>
                  </template>
                </div>

                <!-- Rent -->
                <div>
                  <p class="font-bold text-foreground text-[13.5px]">{{ fmt(unit.rent_price) }}</p>
                  <p v-if="unit.maintenance_requests.length"
                    class="text-[11px] text-amber-600 font-semibold mt-0.5 flex items-center gap-1">
                    <Wrench :size="10" /> {{ unit.maintenance_requests.length }} request{{
                      unit.maintenance_requests.length > 1 ? 's' :
                    '' }}
                  </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-1">
                  <button @click="router.visit(route('units.show', unit.id))"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors opacity-0 group-hover:opacity-100"
                    title="View">
                    <Eye :size="14" />
                  </button>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <button
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-muted transition-colors opacity-0 group-hover:opacity-100">
                        <MoreHorizontal :size="14" />
                      </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-40">
                      <DropdownMenuItem @click="router.visit(route('units.edit', unit.id))">
                        <Pencil :size="13" class="mr-2" /> Edit
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem class="text-destructive focus:text-destructive" @click="deleteUnit(unit.id)">
                        <Trash2 :size="13" class="mr-2" /> Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
            </div>

            <!-- Table Footer -->
            <div
              class="px-5 py-3 bg-slate-50 dark:bg-muted/30 border-t border-slate-100 dark:border-border flex items-center justify-between">
              <p class="text-[12px] text-muted-foreground">
                Showing <span class="font-semibold text-foreground">{{ filteredUnits.length }}</span> of
                <span class="font-semibold text-foreground">{{ units.length }}</span> units
              </p>
            </div>
          </div>

          <!-- Mobile / Tablet Cards -->
          <div class="lg:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="unit in filteredUnits" :key="unit.id"
              class="bg-white dark:bg-card border border-slate-100 dark:border-border rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow">
              <!-- Card Header -->
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="text-[15px] font-bold text-foreground">Unit {{ unit.unit_number }}</p>
                  <p class="text-[11.5px] text-muted-foreground mt-0.5">
                    {{ unit.type }} · Floor {{ unit.floor_number }}
                    <span v-if="unit.size_sqm"> · {{ unit.size_sqm }}m²</span>
                  </p>
                </div>
                <span
                  :class="['inline-flex items-center gap-1.5 text-[10.5px] font-semibold px-2 py-1 rounded-full border', statusConfig[unit.status].pill]">
                  <component :is="statusConfig[unit.status].icon" :size="9" />
                  {{ statusConfig[unit.status].label }}
                </span>
              </div>

              <!-- Property -->
              <Link :href="route('properties.show', unit.property.id)"
                class="text-[12px] font-semibold text-indigo-600 hover:underline flex items-center gap-1 mb-3">
                <MapPin :size="11" /> {{ unit.property.name }}
              </Link>

              <!-- Tenant -->
              <div v-if="unit.active_lease"
                class="mb-3 p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900">
                <p class="text-[11.5px] font-semibold text-foreground flex items-center gap-1.5">
                  <Users :size="11" class="text-blue-500" /> {{ unit.active_lease.tenant.name }}
                </p>
                <p
                  :class="['text-[11px] mt-0.5', daysUntilExpiry(unit.active_lease.end_date) <= 30 ? 'text-red-500 font-semibold' : 'text-muted-foreground']">
                  Expires {{ new Date(unit.active_lease.end_date).toLocaleDateString('en-PH', {
                    month: 'short', day:
                  'numeric' })
                  }}
                </p>
              </div>

              <!-- Maintenance -->
              <div v-if="unit.maintenance_requests.length"
                class="mb-3 inline-flex items-center gap-1.5 text-[11px] text-amber-700 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 rounded-lg border border-amber-200 dark:border-amber-900 font-medium">
                <Wrench :size="11" /> {{ unit.maintenance_requests.length }} maintenance request{{
                  unit.maintenance_requests.length > 1 ? 's' : '' }}
              </div>

              <!-- Rent + CTA -->
              <div class="pt-3 border-t border-slate-100 dark:border-border flex items-center justify-between">
                <div>
                  <p class="text-[10.5px] text-muted-foreground">Monthly Rent</p>
                  <p class="text-[15px] font-bold text-foreground">{{ fmt(unit.rent_price) }}</p>
                </div>
                <button @click="router.visit(route('units.show', unit.id))"
                  class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 px-3 py-1.5 rounded-lg transition-colors">
                  View
                  <ChevronRight :size="13" />
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>