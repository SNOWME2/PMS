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
   <div class="flex h-screen bg-background overflow-hidden">
    <AppSidebar/>
  
     <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <PageHeader title="Properties" subtitle="Manage your properties and units" />
 
  <div class="flex flex-col gap-6 p-6">

    <!-- ── Breadcrumb ────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-1.5 text-[12.5px] text-muted-foreground">
      <Link :href="route('properties.index')" class="hover:text-foreground transition-colors">
        Properties
      </Link>
      <ChevronRight :size="12" />
      <span class="text-foreground font-medium">All Units</span>
    </div>

    <!-- ── Page header ──────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-foreground">All Units</h1>
        <p class="text-[12.5px] text-muted-foreground mt-0.5">
          Browse and manage units across all properties
        </p>
      </div>
      <Button class="gap-2 h-9" @click="router.visit(route('units.create'))">
        <Plus :size="14" />
        Add unit
      </Button>
    </div>

    <!-- ── Stat strip ──────────────────────────────────────────────────── -->
    <div class="grid grid-cols-5 gap-2">
      <div class="bg-card border border-border rounded-xl p-3">
        <p class="text-[11px] text-muted-foreground">Total</p>
        <p class="text-2xl font-bold text-foreground mt-0.5">{{ unitStats.total }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-3">
        <p class="text-[11px] text-muted-foreground">Vacant</p>
        <p class="text-2xl font-bold text-emerald-600 mt-0.5">{{ unitStats.vacant }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-3">
        <p class="text-[11px] text-muted-foreground">Occupied</p>
        <p class="text-2xl font-bold text-blue-600 mt-0.5">{{ unitStats.occupied }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-3">
        <p class="text-[11px] text-muted-foreground">Maintenance</p>
        <p class="text-2xl font-bold text-amber-600 mt-0.5">{{ unitStats.maintenance }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-3">
        <p class="text-[11px] text-muted-foreground">Reserved</p>
        <p class="text-2xl font-bold text-violet-600 mt-0.5">{{ unitStats.reserved }}</p>
      </div>
    </div>

    <!-- ── Filters ──────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3 flex-wrap">
      <div class="relative flex-1 min-w-[200px] max-w-xs">
        <Search :size="13" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="search"
          placeholder="Search units…"
          class="pl-8 h-9 text-[13px]"
          @keydown.enter="applyFilters"
        />
      </div>

      <Select v-model="propertyId" @update:model-value="applyFilters">
        <SelectTrigger class="w-[180px] h-9 text-[13px]">
          <SelectValue placeholder="Property" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All properties</SelectItem>
          <SelectItem v-for="p in properties" :key="p.id" :value="p.id">
            {{ p.name }}
          </SelectItem>
        </SelectContent>
      </Select>

      <Select v-model="floor" @update:model-value="applyFilters">
        <SelectTrigger class="w-[130px] h-9 text-[13px]">
          <SelectValue placeholder="Floor" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All floors</SelectItem>
          <SelectItem v-for="f in floors" :key="f" :value="f">Floor {{ f }}</SelectItem>
        </SelectContent>
      </Select>

      <Select v-model="status" @update:model-value="applyFilters">
        <SelectTrigger class="w-[140px] h-9 text-[13px]">
          <SelectValue placeholder="Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem value="vacant">Vacant</SelectItem>
          <SelectItem value="occupied">Occupied</SelectItem>
          <SelectItem value="maintenance">Maintenance</SelectItem>
          <SelectItem value="reserved">Reserved</SelectItem>
        </SelectContent>
      </Select>

      <Button variant="outline" size="sm" class="h-9 ml-auto" @click="applyFilters">
        Apply
      </Button>
    </div>

    <!-- ── Empty state ──────────────────────────────────────────────────── -->
    <div
      v-if="filteredUnits.length === 0"
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <div class="w-14 h-14 rounded-2xl bg-muted flex items-center justify-center mb-4">
        <Home :size="24" class="text-muted-foreground" />
      </div>
      <p class="text-[15px] font-medium text-foreground">No units found</p>
      <p class="text-[13px] text-muted-foreground mt-1">Add your first unit or adjust filters.</p>
      <Button class="mt-4 gap-2" @click="router.visit(route('units.create'))">
        <Plus :size="14" /> Add unit
      </Button>
    </div>

    <!-- ── Units table (responsive) ─────────────────────────────────────── -->
    <div v-else class="space-y-3">
      <!-- Desktop view -->
      <div class="hidden lg:block bg-card border border-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-7 gap-4 px-4 py-3 bg-muted/50 border-b border-border text-[12.5px] font-semibold text-muted-foreground">
          <div>Unit</div>
          <div>Property</div>
          <div>Type</div>
          <div>Status</div>
          <div>Tenant</div>
          <div>Rent</div>
          <div></div>
        </div>
        <div class="divide-y divide-border">
          <div
            v-for="unit in filteredUnits"
            :key="unit.id"
            class="grid grid-cols-7 gap-4 px-4 py-3 hover:bg-muted/30 transition-colors items-center text-[13px]"
          >
            <!-- Unit -->
            <div>
              <p class="font-semibold text-foreground">{{ unit.unit_number }}</p>
              <p class="text-[11.5px] text-muted-foreground">Fl {{ unit.floor_number }} · {{ unit.type }}</p>
            </div>

            <!-- Property -->
            <div>
              <Link :href="route('properties.show', unit.property.id)" class="font-medium text-primary hover:underline">
                {{ unit.property.name }}
              </Link>
              <p class="text-[11.5px] text-muted-foreground">{{ unit.property.city }}</p>
            </div>

            <!-- Type/Size -->
            <div>
              <p class="text-foreground">{{ unit.type }}</p>
              <p v-if="unit.size_sqm" class="text-[11.5px] text-muted-foreground">{{ unit.size_sqm }}m²</p>
            </div>

            <!-- Status -->
            <div>
              <span :class="['text-[11px] font-medium px-2 py-1 rounded-full border', statusConfig[unit.status].pill]">
                {{ statusConfig[unit.status].label }}
              </span>
            </div>

            <!-- Tenant -->
            <div>
              <template v-if="unit.active_lease">
                <p class="font-medium text-foreground">{{ unit.active_lease.tenant.name }}</p>
                <p :class="['text-[11.5px]', daysUntilExpiry(unit.active_lease.end_date) <= 30 ? 'text-red-600 font-semibold' : 'text-muted-foreground']">
                  Expires {{ new Date(unit.active_lease.end_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) }}
                </p>
              </template>
              <template v-else>
                <p class="text-muted-foreground">—</p>
              </template>
            </div>

            <!-- Rent -->
            <div>
              <p class="font-semibold text-foreground">{{ fmt(unit.rent_price) }}</p>
              <p v-if="unit.maintenance_requests.length" class="text-[11.5px] text-amber-600 font-medium">
                {{ unit.maintenance_requests.length }} MR
              </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-1">
              <Button
                variant="ghost"
                size="sm"
                class="h-8 px-2 text-[12px]"
                @click="router.visit(route('units.show', unit.id))"
              >
                <Eye :size="13" />
              </Button>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon" class="w-8 h-8">
                    <MoreHorizontal :size="14" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                  <DropdownMenuItem @click="router.visit(route('units.edit', unit.id))">
                    <Pencil :size="13" class="mr-2" /> Edit
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    class="text-destructive focus:text-destructive"
                    @click="deleteUnit(unit.id)"
                  >
                    <Trash2 :size="13" class="mr-2" /> Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile/tablet card view -->
      <div class="lg:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div
          v-for="unit in filteredUnits"
          :key="unit.id"
          class="bg-card border border-border rounded-xl p-4"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-2">
            <div>
              <p class="text-[15px] font-bold text-foreground">Unit {{ unit.unit_number }}</p>
              <p class="text-[12px] text-muted-foreground">
                {{ unit.type }} · Floor {{ unit.floor_number }}
                <span v-if="unit.size_sqm"> · {{ unit.size_sqm }}m²</span>
              </p>
            </div>
            <span :class="['text-[10.5px] font-medium px-1.5 py-0.5 rounded-full border', statusConfig[unit.status].pill]">
              {{ statusConfig[unit.status].label }}
            </span>
          </div>

          <!-- Property -->
          <Link :href="route('properties.show', unit.property.id)" class="text-[12px] font-medium text-primary hover:underline block mb-2">
            {{ unit.property.name }}
          </Link>

          <!-- Tenant info -->
          <div v-if="unit.active_lease" class="mb-2 p-2 rounded bg-blue-50 dark:bg-blue-950/30 text-[11px]">
            <p class="font-medium text-foreground">{{ unit.active_lease.tenant.name }}</p>
            <p :class="daysUntilExpiry(unit.active_lease.end_date) <= 30 ? 'text-red-600 font-semibold' : 'text-muted-foreground'">
              Expires {{ new Date(unit.active_lease.end_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) }}
            </p>
          </div>

          <!-- Maintenance badge -->
          <div v-if="unit.maintenance_requests.length" class="mb-2 inline-block text-[11px] text-amber-700 bg-amber-50 dark:bg-amber-950/30 px-2 py-1 rounded border border-amber-200 dark:border-amber-900">
            {{ unit.maintenance_requests.length }} maintenance request(s)
          </div>

          <!-- Rent -->
          <div class="pt-2 border-t border-border">
            <p class="text-[11px] text-muted-foreground">Rent</p>
            <p class="text-[14px] font-bold text-foreground">{{ fmt(unit.rent_price) }}</p>
          </div>

          <!-- Actions -->
          <Button
            variant="outline"
            size="sm"
            class="w-full mt-3 h-8 text-[12px] gap-1"
            @click="router.visit(route('units.show', unit.id))"
          >
            View <ChevronRight :size="12" />
          </Button>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
</template>