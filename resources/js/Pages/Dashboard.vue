<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  Building2,
  Users,
  CreditCard,
  Wrench,
  TrendingUp,
  TrendingDown,
  Bell,
  Plus,
  ArrowRight,
  CheckCircle2,
  Clock,
  AlertCircle,
  Circle,
  ChevronRight,
  Home,
  Calendar,
} from 'lucide-vue-next'

// ── Stat cards ────────────────────────────────────────────────────────────────
const stats = [
  {
    label: 'Total Units',
    value: '128',
    sub: '112 occupied',
    icon: Building2,
    trend: '+4',
    trendUp: true,
    color: 'text-blue-600',
    bg: 'bg-blue-50 dark:bg-blue-950/40',
  },
  {
    label: 'Active Tenants',
    value: '108',
    sub: '6 move-outs this month',
    icon: Users,
    trend: '-2',
    trendUp: false,
    color: 'text-violet-600',
    bg: 'bg-violet-50 dark:bg-violet-950/40',
  },
  {
    label: 'Rent Collected',
    value: '₱2.4M',
    sub: '94% collection rate',
    icon: CreditCard,
    trend: '+₱180k',
    trendUp: true,
    color: 'text-emerald-600',
    bg: 'bg-emerald-50 dark:bg-emerald-950/40',
  },
  {
    label: 'Open Requests',
    value: '17',
    sub: '5 urgent, 12 normal',
    icon: Wrench,
    trend: '+3',
    trendUp: false,
    color: 'text-amber-600',
    bg: 'bg-amber-50 dark:bg-amber-950/40',
  },
]

// ── Occupancy bars ────────────────────────────────────────────────────────────
const properties = [
  { name: 'Sunrise Residences', units: 48, occupied: 46, color: 'bg-primary' },
  { name: 'The Cebu Flats',     units: 32, occupied: 28, color: 'bg-primary' },
  { name: 'Mabolo Garden View', units: 24, occupied: 22, color: 'bg-primary' },
  { name: 'IT Park Suites',     units: 24, occupied: 16, color: 'bg-primary' },
]

// ── Maintenance requests ───────────────────────────────────────────────────────
const requests = [
  { id: 'REQ-089', unit: '3B',  tenant: 'Ana Reyes',    issue: 'Leaking faucet in bathroom',   status: 'urgent',      date: 'Today, 9:14 AM' },
  { id: 'REQ-088', unit: '12A', tenant: 'Marco Santos', issue: 'AC unit not cooling properly', status: 'in-progress', date: 'Today, 8:02 AM' },
  { id: 'REQ-087', unit: '7C',  tenant: 'Liza Uy',      issue: 'Broken door lock',             status: 'in-progress', date: 'Yesterday' },
  { id: 'REQ-086', unit: '2D',  tenant: 'Rey Bautista', issue: 'Ceiling light replacement',    status: 'open',        date: 'Yesterday' },
  { id: 'REQ-085', unit: '9A',  tenant: 'Joy Tan',      issue: 'Clogged kitchen drain',        status: 'resolved',    date: 'Jun 12' },
]

// ── Recent payments ───────────────────────────────────────────────────────────
const payments = [
  { tenant: 'Ana Reyes',    unit: '3B',  amount: '₱18,500', date: 'Today',     status: 'paid' },
  { tenant: 'Marco Santos', unit: '12A', amount: '₱22,000', date: 'Today',     status: 'paid' },
  { tenant: 'Liza Uy',      unit: '7C',  amount: '₱15,000', date: 'Yesterday', status: 'paid' },
  { tenant: 'Rey Bautista', unit: '2D',  amount: '₱19,500', date: 'Jun 12',    status: 'overdue' },
  { tenant: 'Joy Tan',      unit: '9A',  amount: '₱16,800', date: 'Jun 11',    status: 'paid' },
]

// ── Lease renewals ────────────────────────────────────────────────────────────
const renewals = [
  { tenant: 'Maria Cruz',   unit: '5A',  expires: 'Jun 30', days: 13 },
  { tenant: 'Ben Lim',      unit: '11B', expires: 'Jul 15', days: 28 },
  { tenant: 'Grace Delos',  unit: '4C',  expires: 'Jul 22', days: 35 },
]

const statusConfig: Record<string, { label: string; icon: any; class: string; bg: string }> = {
  urgent:      { label: 'Urgent',      icon: AlertCircle,   class: 'text-red-600 bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-900', bg: 'bg-red-50 dark:bg-red-950/40' },
  'in-progress':{ label: 'In Progress', icon: Clock,         class: 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-900', bg: 'bg-amber-50 dark:bg-amber-950/40' },
  open:        { label: 'Open',        icon: Circle,        class: 'text-blue-600 bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-900', bg: 'bg-blue-50 dark:bg-blue-950/40' },
  resolved:    { label: 'Resolved',    icon: CheckCircle2,  class: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900', bg: 'bg-emerald-50 dark:bg-emerald-950/40' },
}
</script>

<template>
  <div class="flex h-screen bg-background overflow-hidden">
    <AppSidebar />

    <!-- ── Main content ──────────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

      <!-- Top bar -->
      <header class="h-[54px] flex items-center justify-between px-6 border-b border-border shrink-0 bg-background">
        <div>
          <h1 class="text-[15px] font-semibold text-foreground">Dashboard</h1>
          <p class="text-[11.5px] text-muted-foreground">Thursday, June 13 · Good morning 👋</p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" class="h-8 text-[12.5px] gap-1.5">
            <Calendar :size="13" />
            June 2025
          </Button>
          <Button size="sm" class="h-8 text-[12.5px] gap-1.5">
            <Plus :size="13" />
            New Request
          </Button>
          <button class="relative w-8 h-8 flex items-center justify-center rounded-lg hover:bg-accent transition-colors">
            <Bell :size="15" class="text-muted-foreground" />
            <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-primary" />
          </button>
        </div>
      </header>

      <!-- Scrollable body -->
      <main class="flex-1 overflow-y-auto p-6 space-y-6">

        <!-- ── Stat cards ─────────────────────────────────────────────── -->
        <div class="grid grid-cols-4 gap-4">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="bg-card border border-border rounded-xl p-4 flex flex-col gap-3"
          >
            <div class="flex items-start justify-between">
              <div :class="['w-9 h-9 rounded-lg flex items-center justify-center', stat.bg]">
                <component :is="stat.icon" :size="16" :class="stat.color" />
              </div>
              <span
                :class="[
                  'flex items-center gap-0.5 text-[11px] font-medium px-1.5 py-0.5 rounded-md',
                  stat.trendUp
                    ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40'
                    : 'text-red-600 bg-red-50 dark:bg-red-950/40',
                ]"
              >
                <component :is="stat.trendUp ? TrendingUp : TrendingDown" :size="10" />
                {{ stat.trend }}
              </span>
            </div>
            <div>
              <p class="text-[26px] font-bold text-foreground leading-none tracking-tight">{{ stat.value }}</p>
              <p class="text-[11.5px] text-muted-foreground mt-1">{{ stat.sub }}</p>
            </div>
            <p class="text-[12px] font-medium text-muted-foreground">{{ stat.label }}</p>
          </div>
        </div>

        <!-- ── Middle row ─────────────────────────────────────────────── -->
        <div class="grid grid-cols-3 gap-4">

          <!-- Occupancy by property -->
          <div class="col-span-2 bg-card border border-border rounded-xl p-5">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h2 class="text-[13.5px] font-semibold text-foreground">Occupancy by Property</h2>
                <p class="text-[11.5px] text-muted-foreground">Overall: 87.5% occupied</p>
              </div>
              <Button variant="ghost" size="sm" class="h-7 text-[12px] text-muted-foreground gap-1">
                View all <ArrowRight :size="12" />
              </Button>
            </div>
            <div class="space-y-4">
              <div v-for="prop in properties" :key="prop.name" class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <Home :size="12" class="text-muted-foreground" />
                    <span class="text-[12.5px] font-medium text-foreground">{{ prop.name }}</span>
                  </div>
                  <span class="text-[12px] text-muted-foreground">
                    {{ prop.occupied }}/{{ prop.units }} units ·
                    <span class="font-semibold text-foreground">{{ Math.round(prop.occupied / prop.units * 100) }}%</span>
                  </span>
                </div>
                <div class="h-1.5 bg-muted rounded-full overflow-hidden">
                  <div
                    :class="['h-full rounded-full transition-all duration-500', prop.color]"
                    :style="{ width: `${prop.occupied / prop.units * 100}%` }"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Lease renewals -->
          <div class="bg-card border border-border rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-[13.5px] font-semibold text-foreground">Upcoming Renewals</h2>
              <span class="text-[11px] bg-amber-50 dark:bg-amber-950/40 text-amber-700 px-2 py-0.5 rounded-full font-medium border border-amber-200 dark:border-amber-900">
                {{ renewals.length }} expiring
              </span>
            </div>
            <div class="space-y-3">
              <div
                v-for="r in renewals"
                :key="r.tenant"
                class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-accent transition-colors cursor-pointer"
              >
                <Avatar class="w-8 h-8 shrink-0">
                  <AvatarFallback class="bg-primary/10 text-primary text-[11px] font-semibold">
                    {{ r.tenant.split(' ').map(n => n[0]).join('') }}
                  </AvatarFallback>
                </Avatar>
                <div class="flex-1 min-w-0">
                  <p class="text-[12.5px] font-medium text-foreground truncate">{{ r.tenant }}</p>
                  <p class="text-[11px] text-muted-foreground">Unit {{ r.unit }} · Expires {{ r.expires }}</p>
                </div>
                <span
                  :class="[
                    'text-[10.5px] font-semibold px-1.5 py-0.5 rounded-md shrink-0',
                    r.days <= 14
                      ? 'bg-red-50 text-red-600 dark:bg-red-950/40'
                      : 'bg-amber-50 text-amber-600 dark:bg-amber-950/40',
                  ]"
                >
                  {{ r.days }}d
                </span>
              </div>
            </div>
            <Button variant="outline" size="sm" class="w-full mt-4 h-8 text-[12px] gap-1">
              Manage Renewals <ChevronRight :size="12" />
            </Button>
          </div>
        </div>

        <!-- ── Bottom row ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-3 gap-4">

          <!-- Maintenance requests -->
          <div class="col-span-2 bg-card border border-border rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-[13.5px] font-semibold text-foreground">Maintenance Requests</h2>
                <p class="text-[11.5px] text-muted-foreground">17 open · 5 urgent</p>
              </div>
              <Button variant="ghost" size="sm" class="h-7 text-[12px] text-muted-foreground gap-1">
                View all <ArrowRight :size="12" />
              </Button>
            </div>
            <div class="space-y-2">
              <div
                v-for="req in requests"
                :key="req.id"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-accent transition-colors cursor-pointer border border-transparent hover:border-border"
              >
                <div :class="['w-7 h-7 rounded-lg flex items-center justify-center shrink-0', statusConfig[req.status].bg ?? 'bg-muted']">
                  <component :is="statusConfig[req.status].icon" :size="13" :class="statusConfig[req.status].class.split(' ')[0]" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-[12.5px] font-medium text-foreground truncate">{{ req.issue }}</span>
                  </div>
                  <p class="text-[11px] text-muted-foreground">{{ req.id }} · Unit {{ req.unit }} · {{ req.tenant }}</p>
                </div>
                <div class="text-right shrink-0">
                  <span :class="['text-[10.5px] font-medium px-2 py-0.5 rounded-full border', statusConfig[req.status].class]">
                    {{ statusConfig[req.status].label }}
                  </span>
                  <p class="text-[10.5px] text-muted-foreground mt-0.5">{{ req.date }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent payments -->
          <div class="bg-card border border-border rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-[13.5px] font-semibold text-foreground">Recent Payments</h2>
              <Button variant="ghost" size="sm" class="h-7 text-[12px] text-muted-foreground gap-1">
                All <ArrowRight :size="12" />
              </Button>
            </div>
            <div class="space-y-2.5">
              <div
                v-for="pay in payments"
                :key="pay.tenant + pay.date"
                class="flex items-center gap-2.5"
              >
                <Avatar class="w-7 h-7 shrink-0">
                  <AvatarFallback class="bg-primary/10 text-primary text-[10px] font-semibold">
                    {{ pay.tenant.split(' ').map(n => n[0]).join('') }}
                  </AvatarFallback>
                </Avatar>
                <div class="flex-1 min-w-0">
                  <p class="text-[12px] font-medium text-foreground truncate">{{ pay.tenant }}</p>
                  <p class="text-[10.5px] text-muted-foreground">Unit {{ pay.unit }} · {{ pay.date }}</p>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-[12.5px] font-semibold text-foreground">{{ pay.amount }}</p>
                  <span
                    :class="[
                      'text-[10px] font-medium',
                      pay.status === 'paid' ? 'text-emerald-600' : 'text-red-500',
                    ]"
                  >
                    {{ pay.status === 'paid' ? '✓ Paid' : '⚠ Overdue' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>
</template>