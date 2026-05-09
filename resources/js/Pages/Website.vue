<template>
  <div class="min-h-screen bg-background text-foreground">
    <!-- Navbar -->
    <header class="border-b border-border sticky top-0 bg-background/80 backdrop-blur z-50">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">StaySync PMS</h1>
        </div>

        <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
          <a href="#features" class="hover:text-primary transition">Features</a>
          <a href="#about" class="hover:text-primary transition">About</a>
          <a href="#pricing" class="hover:text-primary transition">Pricing</a>
          <a href="#contact" class="hover:text-primary transition">Contact</a>
        </nav>

        <div class="flex items-center gap-3">
          <button class="px-4 py-2 rounded-xl border border-border hover:bg-muted transition">
            Login
          </button>

          <button class="px-4 py-2 rounded-xl bg-primary text-primary-foreground hover:opacity-90 transition shadow">
            Get Started
          </button>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-transparent to-primary/5"></div>

      <div class="max-w-7xl mx-auto px-6 py-24 relative z-10 grid lg:grid-cols-2 gap-14 items-center">
        <div>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-muted text-sm mb-6 border border-border">
            Smart Property Management System
          </div>

          <h2 class="text-5xl md:text-6xl font-black tracking-tight leading-tight mb-6">
            Manage Properties,
            Units, and Tenants
            in One Platform
          </h2>

          <p class="text-lg text-muted-foreground leading-relaxed mb-8 max-w-2xl">
            Simplify reservations, tenant management, billing, and property operations with a modern PMS designed for apartments, dormitories, and rental businesses.
          </p>

          <div class="flex flex-wrap gap-4">
            <button class="px-6 py-3 rounded-2xl bg-primary text-primary-foreground font-semibold shadow-lg hover:opacity-90 transition">
              Start Free Trial
            </button>

            <button class="px-6 py-3 rounded-2xl border border-border hover:bg-muted transition font-semibold">
              View Demo
            </button>
          </div>

          <div class="mt-10 flex items-center gap-8 text-sm text-muted-foreground">
            <div>
              <p class="text-2xl font-bold text-foreground">120+</p>
              <span>Properties</span>
            </div>

            <div>
              <p class="text-2xl font-bold text-foreground">8k+</p>
              <span>Units Managed</span>
            </div>

            <div>
              <p class="text-2xl font-bold text-foreground">99%</p>
              <span>Uptime</span>
            </div>
          </div>
        </div>

        <!-- Dashboard Preview -->
        <div class="relative">
          <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/20 blur-3xl rounded-full"></div>

          <div class="rounded-3xl border border-border bg-card shadow-2xl overflow-hidden">
            <div class="border-b border-border p-4 flex items-center gap-2 bg-muted/30">
              <div class="w-3 h-3 rounded-full bg-red-400"></div>
              <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
              <div class="w-3 h-3 rounded-full bg-green-400"></div>
            </div>

            <div class="p-6 space-y-6">
              <div class="grid grid-cols-3 gap-4">
                <div class="rounded-2xl bg-muted p-4 border border-border">
                  <p class="text-sm text-muted-foreground">Occupied</p>
                  <h3 class="text-3xl font-bold mt-2">84</h3>
                </div>

                <div class="rounded-2xl bg-muted p-4 border border-border">
                  <p class="text-sm text-muted-foreground">Vacant</p>
                  <h3 class="text-3xl font-bold mt-2">16</h3>
                </div>

                <div class="rounded-2xl bg-muted p-4 border border-border">
                  <p class="text-sm text-muted-foreground">Revenue</p>
                  <h3 class="text-3xl font-bold mt-2">₱245k</h3>
                </div>
              </div>

              <div class="rounded-2xl border border-border p-5">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="font-semibold">Recent Bookings</h4>
                  <span class="text-sm text-primary">View All</span>
                </div>

                <div class="space-y-4">
                  <div class="flex items-center justify-between p-3 rounded-xl bg-muted/50">
                    <div>
                      <p class="font-medium">Unit A-101</p>
                      <span class="text-sm text-muted-foreground">John Doe</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs bg-primary/10 text-primary">
                      Active
                    </span>
                  </div>

                  <div class="flex items-center justify-between p-3 rounded-xl bg-muted/50">
                    <div>
                      <p class="font-medium">Unit B-204</p>
                      <span class="text-sm text-muted-foreground">Maria Santos</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs bg-yellow-500/10 text-yellow-600">
                      Pending
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-24 border-t border-border">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-black mb-4">
            Everything You Need
          </h2>

          <p class="text-muted-foreground max-w-2xl mx-auto text-lg">
            Built for modern property managers who want simplicity, speed, and automation.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="rounded-3xl border border-border p-8 bg-card hover:-translate-y-1 hover:shadow-xl transition"
          >
            <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-2xl mb-5">
              {{ feature.icon }}
            </div>

            <h3 class="text-xl font-bold mb-3">
              {{ feature.title }}
            </h3>

            <p class="text-muted-foreground leading-relaxed">
              {{ feature.description }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-24">
      <div class="max-w-5xl mx-auto px-6">
        <div class="rounded-[2rem] border border-border bg-gradient-to-br from-primary/10 to-transparent p-12 text-center shadow-xl">
          <h2 class="text-4xl font-black mb-5">
            Ready to Modernize Your Property Management?
          </h2>

          <p class="text-muted-foreground text-lg max-w-2xl mx-auto mb-8">
            Start managing units, tenants, reservations, and payments with a clean and scalable PMS platform.
          </p>

          <div class="flex flex-wrap justify-center gap-4">
            <button class="px-6 py-3 rounded-2xl bg-primary text-primary-foreground font-semibold shadow-lg hover:opacity-90 transition">
              Create Account
            </button>

            <button class="px-6 py-3 rounded-2xl border border-border hover:bg-muted transition font-semibold">
              Contact Sales
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
  <!-- Tenant Management Preview -->
    <section class="py-24 border-t border-border bg-muted/20">
      <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between mb-10">
          <div>
            <h2 class="text-4xl font-black mb-3">
              Tenant Management
            </h2>

            <p class="text-muted-foreground text-lg max-w-2xl">
              Keep track of tenant records, contracts, occupancy status, and payment activity.
            </p>
          </div>

          <button class="hidden md:block px-5 py-3 rounded-2xl bg-primary text-primary-foreground font-semibold shadow hover:opacity-90 transition">
            Add Tenant
          </button>
        </div>

        <div class="rounded-3xl border border-border bg-card overflow-hidden shadow-xl">
          <div class="p-6 border-b border-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h3 class="text-xl font-bold">Tenant Directory</h3>
              <p class="text-sm text-muted-foreground mt-1">
                Manage active tenants and monitor occupancy.
              </p>
            </div>

            <div class="flex items-center gap-3">
              <input
                type="text"
                placeholder="Search tenant..."
                class="px-4 py-2 rounded-xl border border-border bg-background outline-none focus:ring-2 focus:ring-primary/30"
              />

              <button class="px-4 py-2 rounded-xl border border-border hover:bg-muted transition">
                Filter
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-muted/50 border-b border-border">
                <tr>
                  <th class="text-left px-6 py-4 font-semibold">Tenant</th>
                  <th class="text-left px-6 py-4 font-semibold">Unit</th>
                  <th class="text-left px-6 py-4 font-semibold">Status</th>
                  <th class="text-left px-6 py-4 font-semibold">Balance</th>
                  <th class="text-left px-6 py-4 font-semibold">Move In</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="tenant in tenants"
                  :key="tenant.name"
                  class="border-b border-border hover:bg-muted/30 transition"
                >
                  <td class="px-6 py-5">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary">
                        {{ tenant.name.charAt(0) }}
                      </div>

                      <div>
                        <p class="font-medium">{{ tenant.name }}</p>
                        <span class="text-muted-foreground text-xs">
                          {{ tenant.email }}
                        </span>
                      </div>
                    </div>
                  </td>

                  <td class="px-6 py-5 font-medium">
                    {{ tenant.unit }}
                  </td>

                  <td class="px-6 py-5">
                    <span
                      class="px-3 py-1 rounded-full text-xs font-medium"
                      :class="tenant.status === 'Active'
                        ? 'bg-primary/10 text-primary'
                        : 'bg-yellow-500/10 text-yellow-600'"
                    >
                      {{ tenant.status }}
                    </span>
                  </td>

                  <td class="px-6 py-5 font-medium">
                    {{ tenant.balance }}
                  </td>

                  <td class="px-6 py-5 text-muted-foreground">
                    {{ tenant.move_in }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>


</template>

<script setup>
const features = [
  {
    icon: '🏢',
    title: 'Property Management',
    description:
      'Manage multiple properties, buildings, and units from a centralized dashboard.',
  },
  {
    icon: '🛏️',
    title: 'Unit Monitoring',
    description:
      'Track vacant, occupied, reserved, and maintenance units in real time.',
  },
  {
    icon: '💳',
    title: 'Payment Tracking',
    description:
      'Monitor rent payments, deposits, balances, and transaction history easily.',
  },
  {
    icon: '👥',
    title: 'Tenant Management',
    description:
      'Store tenant records, contracts, profiles, and communication logs securely.',
  },
  {
    icon: '📊',
    title: 'Reports & Analytics',
    description:
      'Generate occupancy reports, revenue summaries, and operational insights.',
  },
  {
    icon: '🔔',
    title: 'Notifications',
    description:
      'Send alerts for due payments, reservations, and maintenance updates.',
  },
]


const tenants = [
  {
    name: 'John Doe',
    email: 'john@example.com',
    unit: 'A-101',
    status: 'Active',
    balance: '₱0',
    move_in: 'May 01, 2026',
  },
  {
    name: 'Maria Santos',
    email: 'maria@example.com',
    unit: 'B-204',
    status: 'Pending',
    balance: '₱4,500',
    move_in: 'May 06, 2026',
  },
  {
    name: 'James Cruz',
    email: 'james@example.com',
    unit: 'C-302',
    status: 'Active',
    balance: '₱1,200',
    move_in: 'April 28, 2026',
  },
]
</script>
