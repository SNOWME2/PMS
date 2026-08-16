<script setup >
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import AppSidebar from '@/components/AppSidebar.vue'
import PageHeader from '@/components/PageHeader.vue'
import { route } from 'ziggy-js'
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
  Pencil,
  Trash2,
  Eye,
  MapPin,
  Home,
  ChevronRight,
  MoreHorizontal,
  Layers,
  Users,
  Wrench,
  CheckCircle2,
  Circle,
  Clock,
  AlertCircle,
} from 'lucide-vue-next'



const props = defineProps({
  property:{
    type: Object,
    required: true,
  }
})

// ── State ─────────────────────────────────────────────────────────────────────
const filterStatus = ref('all')
const filterFloor  = ref('all')

// ── Computed ──────────────────────────────────────────────────────────────────
const occupancyPct = computed(() =>
  props.property.total_units
    ? Math.round(props.property.occupied_units / props.property.total_units * 100)
    : 0
)

const floors = computed(() => [
  ...new Set(props.property.units.map(u => u.floor_number))
].sort((a, b) => a - b))

const filteredUnits = computed(() => {
  return props.property.units.filter(u => {
    const matchStatus = filterStatus.value === 'all' || u.status === filterStatus.value
    const matchFloor  = filterFloor.value  === 'all' || u.floor_number === Number(filterFloor.value)
    return matchStatus && matchFloor
  })
})

const vacantCount      = computed(() => props.property.units.filter(u => u.status === 'vacant').length)
const maintenanceCount = computed(() => props.property.units.filter(u => u.status === 'maintenance').length)

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusConfig = {
  vacant:      { label: 'Vacant',      icon: Circle,       pill: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-100' },
  occupied:    { label: 'Occupied',    icon: CheckCircle2, pill: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900' },
  maintenance: { label: 'Maintenance', icon: Wrench,       pill: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900' },
  reserved:    { label: 'Reserved',    icon: Clock,        pill: 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:border-violet-900' },
}

const deleteUnit = (id) => {
  if (confirm('Delete this unit?')) router.delete(route('units.destroy', id))
}

const deleteProperty = () => {
  if (confirm('Delete this property and all its units?'))
    router.delete(route('properties.destroy', props.property.id))
}

const formatCurrency = (n) =>
  '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 0 })
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
      <span class="text-foreground font-medium">{{ property.name }}</span>
    </div>

    <!-- ── Header ───────────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-center gap-4">
        <!-- Cover thumbnail -->
        <div class="w-14 h-14 rounded-xl bg-muted overflow-hidden shrink-0 border border-border">
          <img
            v-if="property.photo"
            :src="`/storage/${property.photo}`"
            :alt="property.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <Building2 :size="22" class="text-muted-foreground/40" />
          </div>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-semibold text-foreground">{{ property.name }}</h1>
            <span class="text-[11px] capitalize font-medium px-2 py-0.5 rounded-full border bg-muted text-muted-foreground">
              {{ property.type }}
            </span>
          </div>
          <div class="flex items-center gap-1 mt-0.5">
            <MapPin :size="11" class="text-muted-foreground" />
            <p class="text-[12.5px] text-muted-foreground">{{ property.address }}, {{ property.city }}</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <Button
          variant="outline"
          size="sm"
          class="h-9 gap-2 text-[13px]"
          @click="router.visit(route('properties.edit', property.id))"
        >
          <Pencil :size="13" /> Edit
        </Button>
        <Button
          size="sm"
          class="h-9 gap-2 text-[13px]"
          @click="router.visit(route('units.create', { property_id: property.id }))"
        >
          <Plus :size="13" /> Add unit
        </Button>
      </div>
    </div>

    <!-- ── Stat strip ────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-3">
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Total units</p>
        <p class="text-2xl font-bold text-foreground mt-1">{{ property.total_units }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Occupied</p>
        <p class="text-2xl font-bold text-foreground mt-1">{{ property.occupied_units }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Vacant</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ vacantCount }}</p>
      </div>
      <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[11.5px] text-muted-foreground">Occupancy</p>
        <div class="flex items-end gap-2 mt-1">
          <p class="text-2xl font-bold text-foreground">{{ occupancyPct }}%</p>
        </div>
        <div class="h-1 bg-muted rounded-full mt-2 overflow-hidden">
          <div class="h-full bg-primary rounded-full" :style="{ width: `${occupancyPct}%` }" />
        </div>
      </div>
    </div>

    <!-- ── Description + Amenities ──────────────────────────────────────── -->
    <div v-if="property.description || property.amenities.length" class="bg-card border border-border rounded-xl p-5">
      <p v-if="property.description" class="text-[13.5px] text-muted-foreground leading-relaxed">
        {{ property.description }}
      </p>
      <div v-if="property.amenities.length" :class="property.description ? 'mt-4 pt-4 border-t border-border' : ''">
        <p class="text-[12px] font-medium text-muted-foreground mb-2">Amenities</p>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="amenity in property.amenities"
            :key="amenity.id"
            class="text-[12px] px-2.5 py-1 rounded-lg bg-muted text-muted-foreground border border-border"
          >
            {{ amenity.name }}
          </span>
        </div>
      </div>
    </div>

    <!-- ── Units section ─────────────────────────────────────────────────── -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-[15px] font-semibold text-foreground">Units</h2>
        <div class="flex items-center gap-2">
          <!-- Floor filter -->
          <select
            v-model="filterFloor"
            class="h-8 text-[12.5px] rounded-lg border border-border bg-background text-foreground px-2 pr-7 focus:outline-none focus:ring-1 focus:ring-ring"
          >
            <option value="all">All floors</option>
            <option v-for="f in floors" :key="f" :value="f">Floor {{ f }}</option>
          </select>
          <!-- Status filter -->
          <select
            v-model="filterStatus"
            class="h-8 text-[12.5px] rounded-lg border border-border bg-background text-foreground px-2 pr-7 focus:outline-none focus:ring-1 focus:ring-ring"
          >
            <option value="all">All statuses</option>
            <option value="vacant">Vacant</option>
            <option value="occupied">Occupied</option>
            <option value="maintenance">Maintenance</option>
            <option value="reserved">Reserved</option>
          </select>
        </div>
      </div>

      <!-- Units grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div
          v-for="unit in filteredUnits"
          :key="unit.id"
          class="bg-card border border-border rounded-xl p-4 hover:border-border/60 hover:shadow-sm transition-all"
        >
          <!-- Unit header -->
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-[15px] font-bold text-foreground">Unit {{ unit.unit_number }}</span>
                <span :class="['text-[10.5px] font-medium px-1.5 py-0.5 rounded-full border', statusConfig[unit.status].pill]">
                  {{ statusConfig[unit.status].label }}
                </span>
              </div>
              <p class="text-[12px] text-muted-foreground mt-0.5">
                {{ unit.type }} · Floor {{ unit.floor_number }} · {{ unit.size_sqm }}m²
              </p>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="w-7 h-7 -mr-1 -mt-1">
                  <MoreHorizontal :size="14" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-40">
                <DropdownMenuItem @click="router.visit(route('units.show', unit.id))">
                  <Eye :size="13" class="mr-2" /> View
                </DropdownMenuItem>
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

          <!-- Tenant info (if occupied) -->
          <div v-if="unit.active_lease" class="mt-3 p-2.5 rounded-lg bg-muted/50 border border-border">
            <p class="text-[11.5px] font-medium text-foreground">{{ unit.active_lease.tenant.name }}</p>
            <p class="text-[11px] text-muted-foreground">
              Lease until {{ new Date(unit.active_lease.end_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) }}
            </p>
          </div>

          <!-- Pricing -->
          <div class="flex items-center justify-between mt-3 pt-3 border-t border-border">
            <div>
              <p class="text-[11px] text-muted-foreground">Rent / mo</p>
              <p class="text-[14px] font-bold text-foreground">{{ formatCurrency(unit.rent_price) }}</p>
            </div>
            <div class="text-right">
              <p class="text-[11px] text-muted-foreground">Deposit</p>
              <p class="text-[13px] font-semibold text-foreground">{{ formatCurrency(unit.deposit_amount) }}</p>
            </div>
          </div>
        </div>

        <!-- Add unit CTA card -->
        <button
          class="border-2 border-dashed border-border rounded-xl p-4 flex flex-col items-center justify-center gap-2 text-muted-foreground hover:border-primary/40 hover:text-primary hover:bg-primary/5 transition-all min-h-35"
          @click="router.visit(route('units.create', { property_id: property.id }))"
        >
          <Plus :size="20" />
          <span class="text-[13px] font-medium">Add unit</span>
        </button>
      </div>
    </div>
  </div>
</div>
</div>
</template>