<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  ChevronRight,
  Pencil,
  Plus,
  Home,
  Layers,
  Maximize2,
  CreditCard,
  Users,
  FileText,
  Wrench,
  CheckCircle2,
  Clock,
  AlertCircle,
  Circle,
  MoreHorizontal,
  Phone,
  Mail,
  CalendarDays,
} from 'lucide-vue-next'
import { route } from 'ziggy-js'

// ── Types ─────────────────────────────────────────────────────────────────────
interface Amenity  { id: number; name: string }
interface Tenant   { id: number; name: string; email: string; phone: string }
interface Lease {
  id: number
  start_date: string
  end_date: string
  rent_price: number
  deposit_amount: number
  status: string
  tenant: Tenant
}
interface MaintenanceRequest {
  id: number
  title: string
  status: 'open' | 'in-progress' | 'resolved' | 'urgent'
  priority: string
  created_at: string
}
interface Unit {
  id: number
  unit_number: string
  type: string
  floor_number: number
  size_sqm: number
  rent_price: number
  deposit_amount: number
  status: 'vacant' | 'occupied' | 'maintenance' | 'reserved'
  description: string | null
  amenities: Amenity[]
  active_lease: Lease | null
  lease_history: Lease[]
  maintenance_requests: MaintenanceRequest[]
  property: { id: number; name: string }
}

const props = defineProps<{ unit: Unit }>()

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusPill: Record<string, string> = {
  vacant:      'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
  occupied:    'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900',
  maintenance: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900',
  reserved:    'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:border-violet-900',
}

const reqConfig: Record<string, { icon: any; pill: string }> = {
  urgent:       { icon: AlertCircle,  pill: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40' },
  'in-progress':{ icon: Clock,        pill: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40' },
  open:         { icon: Circle,       pill: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40' },
  resolved:     { icon: CheckCircle2, pill: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40' },
}

const fmt    = (n: number)  => '₱' + n.toLocaleString('en-PH')
const fmtDate = (s: string) => new Date(s).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })

const daysLeft = (endDate: string) => {
  const diff = new Date(endDate).getTime() - Date.now()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

const capitalize = (s: string) => s.charAt(0).toUpperCase() + s.slice(1)
</script>

<template>
  <div class="flex flex-col gap-6 p-6">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-[12.5px] text-muted-foreground flex-wrap">
      <Link :href="route('properties.index')" class="hover:text-foreground transition-colors">Properties</Link>
      <ChevronRight :size="12" />
      <Link :href="route('properties.show', unit.property.id)" class="hover:text-foreground transition-colors">
        {{ unit.property.name }}
      </Link>
      <ChevronRight :size="12" />
      <span class="text-foreground font-medium">Unit {{ unit.unit_number }}</span>
    </div>

    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-xl font-semibold text-foreground">Unit {{ unit.unit_number }}</h1>
          <span :class="['text-[11px] font-medium px-2 py-0.5 rounded-full border capitalize', statusPill[unit.status]]">
            {{ unit.status }}
          </span>
        </div>
        <p class="text-[12.5px] text-muted-foreground mt-0.5">
          {{ unit.type }} · Floor {{ unit.floor_number }} · {{ unit.size_sqm }}m²
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <Button
          variant="outline"
          size="sm"
          class="h-9 gap-2 text-[13px]"
          @click="router.visit(route('units.edit', unit.id))"
        >
          <Pencil :size="13" /> Edit unit
        </Button>
        <Button
          v-if="unit.status === 'vacant'"
          size="sm"
          class="h-9 gap-2 text-[13px]"
          @click="router.visit(route('leases.create', { unit_id: unit.id }))"
        >
          <Plus :size="13" /> Create lease
        </Button>
      </div>
    </div>

    <!-- ── Detail strip ───────────────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-3">
      <div class="bg-card border border-border rounded-xl p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
          <Layers :size="14" class="text-primary" />
        </div>
        <div>
          <p class="text-[11px] text-muted-foreground">Floor</p>
          <p class="text-[15px] font-bold text-foreground">{{ unit.floor_number }}</p>
        </div>
      </div>
      <div class="bg-card border border-border rounded-xl p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
          <Maximize2 :size="14" class="text-primary" />
        </div>
        <div>
          <p class="text-[11px] text-muted-foreground">Size</p>
          <p class="text-[15px] font-bold text-foreground">{{ unit.size_sqm }}m²</p>
        </div>
      </div>
      <div class="bg-card border border-border rounded-xl p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
          <CreditCard :size="14" class="text-primary" />
        </div>
        <div>
          <p class="text-[11px] text-muted-foreground">Rent / mo</p>
          <p class="text-[15px] font-bold text-foreground">{{ fmt(unit.rent_price) }}</p>
        </div>
      </div>
      <div class="bg-card border border-border rounded-xl p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
          <CreditCard :size="14" class="text-primary" />
        </div>
        <div>
          <p class="text-[11px] text-muted-foreground">Deposit</p>
          <p class="text-[15px] font-bold text-foreground">{{ fmt(unit.deposit_amount) }}</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-4">

      <!-- ── Left column ──────────────────────────────────────────────── -->
      <div class="col-span-2 flex flex-col gap-4">

        <!-- Active lease -->
        <div class="bg-card border border-border rounded-xl p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-[13.5px] font-semibold text-foreground flex items-center gap-2">
              <FileText :size="14" class="text-muted-foreground" /> Active lease
            </h2>
            <Button
              v-if="unit.active_lease"
              variant="ghost"
              size="sm"
              class="h-7 text-[12px]"
              @click="router.visit(route('leases.show', unit.active_lease.id))"
            >
              View lease
            </Button>
          </div>

          <!-- Has lease -->
          <template v-if="unit.active_lease">
            <div class="grid grid-cols-2 gap-3 text-[12.5px]">
              <div class="space-y-0.5">
                <p class="text-muted-foreground text-[11px]">Start date</p>
                <p class="font-medium text-foreground">{{ fmtDate(unit.active_lease.start_date) }}</p>
              </div>
              <div class="space-y-0.5">
                <p class="text-muted-foreground text-[11px]">End date</p>
                <p class="font-medium text-foreground">{{ fmtDate(unit.active_lease.end_date) }}</p>
              </div>
              <div class="space-y-0.5">
                <p class="text-muted-foreground text-[11px]">Monthly rent</p>
                <p class="font-bold text-foreground">{{ fmt(unit.active_lease.rent_price) }}</p>
              </div>
              <div class="space-y-0.5">
                <p class="text-muted-foreground text-[11px]">Days remaining</p>
                <p :class="['font-bold', daysLeft(unit.active_lease.end_date) <= 30 ? 'text-red-600' : 'text-foreground']">
                  {{ daysLeft(unit.active_lease.end_date) }} days
                </p>
              </div>
            </div>
          </template>

          <!-- No lease -->
          <div v-else class="flex flex-col items-center py-6 text-center">
            <FileText :size="28" class="text-muted-foreground/30 mb-2" />
            <p class="text-[13px] font-medium text-muted-foreground">No active lease</p>
            <p class="text-[12px] text-muted-foreground/70">This unit is currently {{ unit.status }}</p>
            <Button
              v-if="unit.status === 'vacant'"
              size="sm"
              class="mt-3 h-8 text-[12.5px] gap-1.5"
              @click="router.visit(route('leases.create', { unit_id: unit.id }))"
            >
              <Plus :size="13" /> Create lease
            </Button>
          </div>
        </div>

        <!-- Maintenance requests -->
        <div class="bg-card border border-border rounded-xl p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-[13.5px] font-semibold text-foreground flex items-center gap-2">
              <Wrench :size="14" class="text-muted-foreground" />
              Maintenance
              <!-- <span v-if="unit.maintenance_requests.length" class="text-[11px] bg-muted px-1.5 py-0.5 rounded-full text-muted-foreground">
                {{ unit.maintenance_requests.length }}
              </span> -->
            </h2>
            <Button
              variant="ghost"
              size="sm"
              class="h-7 text-[12px] gap-1"
              @click="router.visit(route('maintenance.create', { unit_id: unit.id }))"
            >
              <Plus :size="12" /> New
            </Button>
          </div>

          <!-- <div v-if="unit.maintenance_requests.length" class="space-y-2">
            <div
              v-for="req in unit.maintenance_requests"
              :key="req.id"
              class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-accent transition-colors cursor-pointer"
              @click="router.visit(route('maintenance.show', req.id))"
            >
              <div :class="['w-7 h-7 rounded-lg flex items-center justify-center shrink-0', reqConfig[req.status]?.pill ?? 'bg-muted']">
                <component :is="reqConfig[req.status]?.icon ?? Circle" :size="13" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12.5px] font-medium text-foreground truncate">{{ req.title }}</p>
                <p class="text-[11px] text-muted-foreground">{{ fmtDate(req.created_at) }}</p>
              </div>
              <span :class="['text-[10.5px] font-medium px-1.5 py-0.5 rounded-full border shrink-0', reqConfig[req.status]?.pill ?? 'bg-muted text-muted-foreground border-border']">
                {{ capitalize(req.status) }}
              </span>
            </div>
          </div>

          <div v-else class="flex flex-col items-center py-6 text-center">
            <Wrench :size="24" class="text-muted-foreground/30 mb-2" />
            <p class="text-[12.5px] text-muted-foreground">No maintenance requests</p>
          </div> -->
        </div>

        <!-- Lease history -->
        <!-- <div v-if="unit.lease_history.length" class="bg-card border border-border rounded-xl p-5">
          <h2 class="text-[13.5px] font-semibold text-foreground mb-4 flex items-center gap-2">
            <CalendarDays :size="14" class="text-muted-foreground" /> Lease history
          </h2>
          <div class="space-y-2">
            <div
              v-for="lease in unit.lease_history"
              :key="lease.id"
              class="flex items-center gap-3 py-2.5 border-b border-border last:border-0"
            >
              <div class="flex-1 min-w-0">
                <p class="text-[12.5px] font-medium text-foreground">{{ lease.tenant?.name ?? '—' }}</p>
                <p class="text-[11px] text-muted-foreground">
                  {{ fmtDate(lease.start_date) }} – {{ fmtDate(lease.end_date) }}
                </p>
              </div>
              <span class="text-[12px] font-semibold text-foreground shrink-0">{{ fmt(lease.rent_price) }}</span>
            </div>
          </div>
        </div> -->
      </div>

      <!-- ── Right column ─────────────────────────────────────────────── -->
      <div class="flex flex-col gap-4">

        <!-- Current tenant -->
        <div class="bg-card border border-border rounded-xl p-5">
          <h2 class="text-[13.5px] font-semibold text-foreground mb-4 flex items-center gap-2">
            <Users :size="14" class="text-muted-foreground" /> Current tenant
          </h2>

          <template v-if="unit.active_lease?.tenant">
            <div class="flex flex-col items-center text-center pb-4 border-b border-border">
              <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-2">
                <span class="text-[15px] font-bold text-primary">
                  {{ unit.active_lease.tenant.name.split(' ').map((n: string) => n[0]).join('').toUpperCase() }}
                </span>
              </div>
              <p class="text-[13.5px] font-semibold text-foreground">{{ unit.active_lease.tenant.name }}</p>
            </div>
            <div class="pt-4 space-y-2.5">
              <div class="flex items-center gap-2.5">
                <Mail :size="13" class="text-muted-foreground shrink-0" />
                <p class="text-[12px] text-foreground truncate">{{ unit.active_lease.tenant.email }}</p>
              </div>
              <div class="flex items-center gap-2.5">
                <Phone :size="13" class="text-muted-foreground shrink-0" />
                <p class="text-[12px] text-foreground">{{ unit.active_lease.tenant.phone }}</p>
              </div>
            </div>
            <Button
              variant="outline"
              size="sm"
              class="w-full mt-4 h-8 text-[12.5px]"
              @click="router.visit(route('tenants.show', unit.active_lease.tenant.id))"
            >
              View tenant profile
            </Button>
          </template>

          <div v-else class="flex flex-col items-center py-6 text-center">
            <Users :size="24" class="text-muted-foreground/30 mb-2" />
            <p class="text-[12.5px] text-muted-foreground">No current tenant</p>
          </div>
        </div>

        <!-- Unit details -->
        <div class="bg-card border border-border rounded-xl p-5 space-y-3">
          <h2 class="text-[13.5px] font-semibold text-foreground">Unit details</h2>
          <div class="space-y-2 text-[12.5px]">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Type</span>
              <span class="font-medium text-foreground capitalize">{{ unit.type }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Floor</span>
              <span class="font-medium text-foreground">{{ unit.floor_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Size</span>
              <span class="font-medium text-foreground">{{ unit.size_sqm }} m²</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Rent</span>
              <span class="font-bold text-foreground">{{ fmt(unit.rent_price) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Deposit</span>
              <span class="font-medium text-foreground">{{ fmt(unit.deposit_amount) }}</span>
            </div>
          </div>

          <div v-if="unit.description" class="pt-3 border-t border-border">
            <p class="text-[11px] text-muted-foreground mb-1">Notes</p>
            <p class="text-[12.5px] text-foreground leading-relaxed">{{ unit.description }}</p>
          </div>
        </div>

        <!-- Amenities -->
        <div v-if="unit.amenities.length" class="bg-card border border-border rounded-xl p-5">
          <h2 class="text-[13.5px] font-semibold text-foreground mb-3">Amenities</h2>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="a in unit.amenities"
              :key="a.id"
              class="text-[11.5px] px-2 py-1 rounded-md bg-muted text-muted-foreground border border-border"
            >
              {{ a.name }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>