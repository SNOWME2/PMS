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
</script>
