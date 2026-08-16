<script setup>
import { LayoutGrid } from "lucide-vue-next"
import SignupForm from "@/components/SignupForm.vue"
</script>

<template>
  <div class="relative flex min-h-svh flex-col items-center justify-center bg-[#FAFAF9] p-6 md:p-10">

    <!-- faint graph-paper grid, evokes a floor plan / site drawing -->
    <div
      class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(15,23,42,0.035)_1px,transparent_1px),linear-gradient(to_bottom,rgba(15,23,42,0.035)_1px,transparent_1px)] bg-[size:32px_32px]" />
    <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-white to-transparent" />

    <!-- Brand mark -->
    <div class="relative z-10 mb-9 flex items-center gap-2">
      <div class="flex h-7 w-7 items-center justify-center rounded-[3px] bg-[#2454D6]">
        <LayoutGrid class="h-3.5 w-3.5 text-white" />
      </div>
      <span class="text-[13.5px] font-semibold tracking-tight text-slate-900">Estate PMS</span>
    </div>

    <div class="relative z-10 w-full max-w-lg">
      <SignupForm />
    </div>
  </div>
</template>