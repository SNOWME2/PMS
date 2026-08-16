<script setup>
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { useForm } from "@inertiajs/vue3"

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post('/register')
}

const props = defineProps({
  class: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div :class="cn('w-full max-w-md mx-auto', props.class)">

    <!-- Card with drafting-corner marks -->
    <div class="relative">
      <!-- corner brackets — signature motif -->
      <span class="absolute -top-2.5 -left-2.5 h-4 w-4 border-t-2 border-l-2 border-[#2454D6]" />
      <span class="absolute -top-2.5 -right-2.5 h-4 w-4 border-t-2 border-r-2 border-[#2454D6]" />
      <span class="absolute -bottom-2.5 -left-2.5 h-4 w-4 border-b-2 border-l-2 border-[#2454D6]" />
      <span class="absolute -bottom-2.5 -right-2.5 h-4 w-4 border-b-2 border-r-2 border-[#2454D6]" />

      <div class="rounded-[4px] border border-slate-200 bg-white px-8 py-10 sm:px-11 sm:py-12">

        <!-- Eyebrow -->
        <div class="mb-7 flex items-center justify-center gap-2">
          <span class="h-1.5 w-1.5 rounded-full bg-[#2454D6]" />
          <span class="text-[10.5px] font-semibold uppercase tracking-[0.16em] text-slate-400">
            Property Management System
          </span>
        </div>

        <!-- Header -->
        <div class="mb-9 text-center">
          <h1 class="text-[21px] font-semibold tracking-tight text-slate-900">
            Create your account
          </h1>
          <p class="mt-1.5 text-[13px] text-slate-500">
            Set up access to manage your properties
          </p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-5">

          <!-- Name row -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label for="first_name" class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                First name
              </label>
              <Input id="first_name" v-model="form.first_name" type="text" placeholder="John" autocomplete="given-name"
                required
                class="h-10 rounded-[3px] border-slate-200 text-[13.5px] text-slate-900 placeholder:text-slate-300 focus-visible:ring-1 focus-visible:ring-[#2454D6] focus-visible:border-[#2454D6]" />
              <p v-if="form.errors.first_name" class="text-[11px] text-rose-600">
                {{ form.errors.first_name }}
              </p>
            </div>

            <div class="space-y-1.5">
              <label for="last_name" class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                Last name
              </label>
              <Input id="last_name" v-model="form.last_name" type="text" placeholder="Doe" autocomplete="family-name"
                required
                class="h-10 rounded-[3px] border-slate-200 text-[13.5px] text-slate-900 placeholder:text-slate-300 focus-visible:ring-1 focus-visible:ring-[#2454D6] focus-visible:border-[#2454D6]" />
              <p v-if="form.errors.last_name" class="text-[11px] text-rose-600">
                {{ form.errors.last_name }}
              </p>
            </div>
          </div>

          <!-- Middle name -->
          <div class="space-y-1.5">
            <label for="middle_name" class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
              Middle name <span class="normal-case text-slate-300">(optional)</span>
            </label>
            <Input id="middle_name" v-model="form.middle_name" type="text" placeholder="Michael"
              autocomplete="additional-name"
              class="h-10 rounded-[3px] border-slate-200 text-[13.5px] text-slate-900 placeholder:text-slate-300 focus-visible:ring-1 focus-visible:ring-[#2454D6] focus-visible:border-[#2454D6]" />
            <p v-if="form.errors.middle_name" class="text-[11px] text-rose-600">
              {{ form.errors.middle_name }}
            </p>
          </div>

          <!-- Email -->
          <div class="space-y-1.5">
            <label for="email" class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
              Email
            </label>
            <Input id="email" v-model="form.email" type="email" placeholder="you@company.com" autocomplete="email"
              required
              class="h-10 rounded-[3px] border-slate-200 text-[13.5px] text-slate-900 placeholder:text-slate-300 focus-visible:ring-1 focus-visible:ring-[#2454D6] focus-visible:border-[#2454D6]" />
            <p v-if="form.errors.email" class="text-[11px] text-rose-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password row -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label for="password" class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                Password
              </label>
              <Input id="password" v-model="form.password" type="password" autocomplete="new-password" required
                class="h-10 rounded-[3px] border-slate-200 text-[13.5px] text-slate-900 focus-visible:ring-1 focus-visible:ring-[#2454D6] focus-visible:border-[#2454D6]" />
              <p v-if="form.errors.password" class="text-[11px] text-rose-600">
                {{ form.errors.password }}
              </p>
            </div>

            <div class="space-y-1.5">
              <label for="password_confirmation" class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                Confirm
              </label>
              <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                autocomplete="new-password" required
                class="h-10 rounded-[3px] border-slate-200 text-[13.5px] text-slate-900 focus-visible:ring-1 focus-visible:ring-[#2454D6] focus-visible:border-[#2454D6]" />
              <p v-if="form.errors.password_confirmation" class="text-[11px] text-rose-600">
                {{ form.errors.password_confirmation }}
              </p>
            </div>
          </div>

          <p class="text-[11px] text-slate-400">
            Must be at least 8 characters long.
          </p>

          <!-- Submit -->
          <Button type="submit" :disabled="form.processing"
            class="mt-1 h-10 w-full rounded-[3px] bg-[#2454D6] text-[13px] font-semibold text-white transition-colors hover:bg-[#1E45B8] disabled:opacity-60">
            {{ form.processing ? 'Creating account…' : 'Create account' }}
          </Button>

          <p class="text-center text-[12.5px] text-slate-500">
            Already have an account?
            <a href="/login" class="font-medium text-[#2454D6] underline underline-offset-4 hover:text-[#1E45B8]">
              Sign in
            </a>
          </p>
        </form>
      </div>
    </div>

    <!-- Terms -->
    <p class="mt-6 px-4 text-center text-[11.5px] leading-relaxed text-slate-400">
      By creating an account, you agree to our
      <a href="#" class="underline underline-offset-4 hover:text-slate-600">Terms of Service</a>
      and
      <a href="#" class="underline underline-offset-4 hover:text-slate-600">Privacy Policy</a>.
    </p>
  </div>
</template>