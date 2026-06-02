<script setup lang="ts">
import { computed, ref, type Component } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'

import {
  LayoutDashboard,
  Building2,
  Users,
  FileText,
  Wrench,
  CreditCard,
  BarChart3,
  Bell,
  Settings,
  ChevronRight,
  PanelLeftClose,
  PanelLeftOpen,
  Search,
  Building,
  LogOut,
  ClipboardList,
  MessageSquare,
  UserStar,
} from 'lucide-vue-next'

import { usePage } from '@inertiajs/vue3'

interface PageProps {
  auth: {
    user: {
      name: string
      email: string
      role: string
    }
  }
  [key: string]: any
}

interface NavChild {
  label: string
  href: string
  component: string
}

interface NavItem {
  label: string
  icon: Component
  badge: string | null
  href: string
  roles: string[]
  component?: string
  children?: NavChild[]
}

const logout = () => {
  router.post('/logout')
}

const page = usePage<PageProps>()

const isActive = (componentName: string) => {
  return page.component === componentName
}
const isParentActive = (item: NavItem) => {
  return item.children?.some(child =>
    page.component === child.component
  )
}

const isOpen = (item: NavItem) => {
  return isParentActive(item)
}
const user = page.props.auth.user
const role = page.props.auth.user.role
const getDashboardRoute = (role: string) => {
  switch (role) {
    case 'admin':
      return '/admin/dashboard'
    case 'staff':
      return '/staff/dashboard'
    case 'tenant':
    default:
      return '/tenant/dashboard'
  }
}
const collapsed = ref(false)
const activeItem = ref('Dashboard')
const openGroup = ref<string | null>(null)

const navItems = [
  {
    label: 'Dashboard',
    icon: LayoutDashboard,
    badge: null,
    href: getDashboardRoute(role),
    roles: ['admin', 'staff', 'tenant'],
    component: 'Dashboard',
  },
  {
    label: 'Properties',
    icon: Building2,
    badge: null,
    href: '#',
    roles: ['admin', 'staff'],
    children: [
      { label: 'All Properties', href: '/properties', component: "Properties/Index", },
      { label: 'Units', href: '/units', component: "Units/Index", },
      { label: 'Amenities', href: '/amenities', component: "Amenities/Index", },
    ],
  },
  {
    label: 'Tenants',
    icon: Users,
    badge: null,
    href: '/tenants',
    roles: ['admin', 'staff'],
    component: 'Tenants/Index',
  },
  {
    label: 'Leases',
    icon: FileText,
    badge: null,
    href: '#',
    roles: ['admin', 'staff', 'tenant'],
    children: [
      { label: 'Active Leases', href: '#', component: 'Leases/Active', },
      { label: 'Expiring Soon', href: '#', component: 'Leases/Expiring', },
      { label: 'Renewals', href: '#', component: 'Leases/Renewals', },
    ],
  },
  {
    label: 'Maintenance',
    icon: Wrench,
    badge: '5',
    href: '#',
    roles: ['admin', 'staff', 'tenant'],
    children: [
      { label: 'Requests', href: '#', component: 'Maintenance/Requests', },
      { label: 'In Progress', href: '#', component: 'Maintenance/InProgress', },
      { label: 'Completed', href: '#', component: 'Maintenance/Completed', },
    ],
  },
  {
    label: 'Payments',
    icon: CreditCard,
    badge: '3',
    href: '#',
    roles: ['admin', 'staff', 'tenant'],
    component: 'Payments/Index',
  },
  {
    label: 'Reports',
    icon: BarChart3,
    badge: null,
    href: '#',
    roles: ['admin'],
    component: 'Reports/Index',
  },
  {
    label: 'Messages',
    icon: MessageSquare,
    badge: '2',
    href: '#',
    roles: ['admin', 'staff', 'tenant'],
    component: 'Messages/Index',
  },

  {
    label: 'Staffs',
    icon: UserStar,
    badge: null,
    href: '/staffs',
    roles: ['admin'],
    component: 'Staffs/Index',
  },
]

const visibleNavItems = computed(() =>
  navItems.filter(item => item.roles.includes(role))
)

const bottomItems = [
  { label: 'Notifications', icon: Bell },
  { label: 'Settings', icon: Settings },
]

const toggleCollapse = () => (collapsed.value = !collapsed.value)
const setActive = (label: string) => (activeItem.value = label)
const toggleGroup = (label: string) =>
  (openGroup.value = openGroup.value === label ? null : label)
</script>

<template>
  <TooltipProvider :delay-duration="0">
    <aside :class="[
      'flex flex-col h-screen bg-sidebar border-r border-sidebar-border overflow-hidden transition-[width] duration-200 ease-in-out',
      collapsed ? 'w-15' : 'w-60',
    ]">
      <!-- Header -->
      <div class="flex items-center justify-between min-h-13.5 px-3 py-3 gap-2">
        <div v-if="!collapsed" class="flex items-center gap-2 overflow-hidden">
          <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-sidebar-primary shrink-0">
            <Building :size="14" class="text-sidebar-primary-foreground" />
          </div>
          <span class="text-[15px] font-bold tracking-tight text-sidebar-foreground whitespace-nowrap">
            PropFlow
          </span>
        </div>
        <div v-else class="flex items-center justify-center w-7 h-7 rounded-lg bg-sidebar-primary mx-auto">
          <Building :size="14" class="text-sidebar-primary-foreground" />
        </div>

        <Button variant="ghost" size="icon"
          class="w-7 h-7 shrink-0 text-sidebar-foreground/50 hover:text-sidebar-foreground hover:bg-sidebar-accent"
          @click="toggleCollapse">
          <PanelLeftClose v-if="!collapsed" :size="15" />
          <PanelLeftOpen v-else :size="15" />
        </Button>
      </div>

      <!-- Search -->
      <div v-if="!collapsed" class="px-2.5 pb-2.5">
        <div
          class="flex items-center gap-2 bg-sidebar-accent border border-sidebar-border rounded-lg px-2.5 py-1.5 cursor-pointer hover:border-sidebar-ring/40 transition-colors">
          <Search :size="13" class="text-sidebar-foreground/50 shrink-0" />
          <span class="text-[12.5px] text-sidebar-foreground/50 flex-1 whitespace-nowrap">Search…</span>
          <kbd class="text-[10px] text-sidebar-foreground/40 bg-sidebar-border px-1.5 py-0.5 rounded font-mono">⌘K</kbd>
        </div>
      </div>
      <div v-else class="flex justify-center pb-2.5">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="icon"
              class="w-9 h-9 text-sidebar-foreground/50 hover:bg-sidebar-accent hover:text-sidebar-foreground">
              <Search :size="15" />
            </Button>
          </TooltipTrigger>
          <TooltipContent side="right">Search</TooltipContent>
        </Tooltip>
      </div>

      <Separator class="bg-sidebar-border" />

      <!-- Main nav -->
      <nav class="flex flex-col gap-px px-2 py-2 overflow-y-auto flex-1">
        <template v-for="item in visibleNavItems" :key="item.label">

          <!-- Collapsible group -->
          <template v-if="item.children && !collapsed">
            <Collapsible :open="openGroup === item.label || isOpen(item)" @update:open="toggleGroup(item.label)">
              <CollapsibleTrigger as-child>
                <button :class="[
                  'flex items-center gap-2.5 w-full px-2.5 py-1.75 rounded-lg text-[13.5px] font-medium transition-colors',
                  activeItem === item.label
                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground',
                ]" @click="setActive(item.label)">
                  <component :is="item.icon" :size="15" class="shrink-0" />
                  <span class="flex-1 text-left truncate">{{ item.label }}</span>
                  <Badge v-if="item.badge"
                    class="text-[10px] px-1.5 py-0 h-auto rounded-full bg-sidebar-primary/15 text-sidebar-primary border-0 font-semibold mr-1">
                    {{ item.badge }}
                  </Badge>
                  <ChevronRight :size="13"
                    :class="['shrink-0 transition-transform duration-200', openGroup === item.label || isOpen(item) ? 'rotate-90' : '']" />
                </button>
              </CollapsibleTrigger>
              <CollapsibleContent>
                <div class="flex flex-col gap-px pl-5 pt-0.5 pb-1">
                  <button v-for="child in item.children" :key="child.label" :class="[
                    'text-left px-2.5 py-1.25 rounded-md text-[12.5px] border-l transition-colors',
                    isActive(child.component as string)
                      ? 'text-sidebar-primary border-sidebar-primary font-medium'
                      : 'text-sidebar-foreground/60 border-sidebar-border hover:bg-sidebar-accent hover:text-sidebar-foreground',
                  ]" @click="router.visit(child.href)">
                    {{ child.label }}
                  </button>
                </div>
              </CollapsibleContent>
            </Collapsible>
          </template>

          <!-- Icon-only with tooltip (collapsed) -->
          <template v-else-if="collapsed">
            <Tooltip>
              <TooltipTrigger as-child>
                <button :class="[
                  'relative flex items-center justify-center w-full py-1.75 rounded-lg transition-colors',
                  isActive(item.component as string)
                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                    : 'text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-foreground',
                ]" @click="setActive(item.label)">
                  <component :is="item.icon" :size="15" />
                  <span v-if="item.badge"
                    class="absolute top-1 right-1.5 w-1.5 h-1.5 rounded-full bg-sidebar-primary" />
                </button>
              </TooltipTrigger>
              <TooltipContent side="right">{{ item.label }}</TooltipContent>
            </Tooltip>
          </template>

          <!-- Regular expanded item -->
          <template v-else>
            <button :class="[
              'flex items-center gap-2.5 w-full px-2.5 py-1.75 rounded-lg text-[13.5px] font-medium transition-colors',
              isActive(item.component as string)
                ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground',
            ]" @click="router.visit(item.href)">
              <component :is="item.icon" :size="15" class="shrink-0" />
              <span class="flex-1 text-left truncate">{{ item.label }}</span>
              <Badge v-if="item.badge"
                class="text-[10px] px-1.5 py-0 h-auto rounded-full bg-sidebar-primary/15 text-sidebar-primary border-0 font-semibold">
                {{ item.badge }}
              </Badge>
            </button>
          </template>

        </template>
      </nav>

      <Separator class="bg-sidebar-border" />

      <!-- Bottom nav -->
      <nav class="flex flex-col gap-px px-2 py-2">
        <template v-for="item in bottomItems" :key="item.label">
          <Tooltip v-if="collapsed">
            <TooltipTrigger as-child>
              <button
                class="flex items-center justify-center w-full py-1.75 rounded-lg text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-foreground transition-colors">
                <component :is="item.icon" :size="15" />
              </button>
            </TooltipTrigger>
            <TooltipContent side="right">{{ item.label }}</TooltipContent>
          </Tooltip>
          <button v-else
            class="flex items-center gap-2.5 w-full px-2.5 py-[7px] rounded-lg text-[13.5px] font-medium text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground transition-colors">
            <component :is="item.icon" :size="15" class="shrink-0" />
            <span class="truncate">{{ item.label }}</span>
          </button>
        </template>
      </nav>

      <Separator class="bg-sidebar-border" />

      <!-- User footer -->
      <div :class="['flex items-center gap-2.5 px-3 py-3 overflow-hidden', collapsed ? 'justify-center' : '']">
        <Avatar class="w-8 h-8 shrink-0">
          <AvatarImage src="" alt="" />
          <AvatarFallback class="bg-sidebar-primary text-sidebar-primary-foreground text-xs font-semibold">
            {{ user.name.slice(0, 2).toUpperCase() }}
          </AvatarFallback>
        </Avatar>

        <template v-if="!collapsed">
          <div class="flex-1 min-w-0">
            <p class="text-[12.5px] font-semibold text-sidebar-foreground truncate leading-tight">{{ user.name }}</p>
            <p class="text-[11px] text-sidebar-foreground/50 truncate leading-tight capitalize">{{ user.role }}</p>
          </div>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button variant="ghost" size="icon" @click="logout"
                class="w-7 h-7 shrink-0 text-sidebar-foreground/40 hover:text-sidebar-foreground hover:bg-sidebar-accent">
                <LogOut :size="13" />
              </Button>
            </TooltipTrigger>
            <TooltipContent side="right">Log out</TooltipContent>
          </Tooltip>
        </template>
      </div>

    </aside>
  </TooltipProvider>
</template>