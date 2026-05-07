<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { useForm } from "@inertiajs/vue3"


const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})
const submit = () => {
    form.post('/register')
}

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('flex flex-col gap-6', props.class)">
    <Card>
      <CardHeader class="text-center">
        <CardTitle class="text-xl">
          Create your account
        </CardTitle>
        <CardDescription>
          Enter your email below to create your account
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form
        @submit.prevent="submit">
          <FieldGroup>
            <Field>
              <FieldLabel for="name">
                Full Name
              </FieldLabel>
              <Input    v-model="form.name" id="name" type="text" placeholder="John Doe" required />
            </Field>
            <p v-if="form.errors.name">
  {{ form.errors.name }}
</p>
            <Field>
              <FieldLabel for="email">
                Email
              </FieldLabel>
              <Input
              v-model="form.email"
                id="email"
                type="email"
                placeholder="m@example.com"
                required
              />
            </Field>
            <Field>
              <Field class="grid grid-cols-2 gap-4">
                <Field>
                  <FieldLabel for="password">
                    Password
                  </FieldLabel>
                  <Input    v-model="form.password" id="password" type="password" required />
                </Field>
                <Field>
                  <FieldLabel for="confirm-password">
                    Confirm Password
                  </FieldLabel>
                  <Input    v-model="form.password_confirmation" id="confirm-password" type="password" required />
                </Field>
              </Field>
              <FieldDescription>
                Must be at least 8 characters long.
              </FieldDescription>
            </Field>
            <Field>
              <Button type="submit">
                Create Account
              </Button>
              <FieldDescription class="text-center">
                Already have an account? <a href="/login">Sign in</a>
              </FieldDescription>
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
    </Card>
    <FieldDescription class="px-6 text-center">
      By clicking continue, you agree to our <a href="#">Terms of Service</a>
      and <a href="#">Privacy Policy</a>.
    </FieldDescription>
  </div>
</template>
