<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
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
import { Building2, ChevronRight, Upload, X, Check } from 'lucide-vue-next'

interface Amenity { id: number; name: string; icon: string }
interface Property {
  id?: number
  name: string
  address: string
  city: string
  type: string
  description: string
  photo: string | null
  amenity_ids: number[]
}

const props = defineProps<{
  property?: Property
  amenities: Amenity[]
}>()

const isEditing = computed(() => !!props.property?.id)

const form = useForm({
  name:        props.property?.name        ?? '',
  address:     props.property?.address     ?? '',
  city:        props.property?.city        ?? '',
  type:        props.property?.type        ?? 'residential',
  description: props.property?.description ?? '',
  photo:       null as File | null,
  amenity_ids: props.property?.amenity_ids ?? [] as number[],
})

const previewUrl = ref<string | null>(
  props.property?.photo ? `/storage/${props.property.photo}` : null
)

const toggleAmenity = (id: number) => {
  const idx = form.amenity_ids.indexOf(id)
  if (idx === -1) form.amenity_ids.push(id)
  else form.amenity_ids.splice(idx, 1)
}

const handlePhoto = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  form.photo = file
  previewUrl.value = URL.createObjectURL(file)
}

const removePhoto = () => {
  form.photo = null
  previewUrl.value = null
}

const submit = () => {
  if (isEditing.value) {
    form.post(route('properties.update', props.property!.id), {
      forceFormData: true,
      method: 'put' as any,
    })
  } else {
    form.post(route('properties.store'))
  }
}
</script>

<template>
  
    <div class="flex h-screen bg-background overflow-hidden">
    <AppSidebar/>
  
     <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <PageHeader title="Properties" subtitle="Manage your properties and units" />
  <div class="flex flex-col gap-6 p-6 max-w-1xl">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-[12.5px] text-muted-foreground">
      <Link :href="route('properties.index')" class="hover:text-foreground transition-colors">
        Properties
      </Link>
      <ChevronRight :size="12" />
      <span class="text-foreground font-medium">{{ isEditing ? 'Edit property' : 'New property' }}</span>
    </div>

    <!-- Page title -->
    <div>
      <h1 class="text-xl font-semibold text-foreground">
        {{ isEditing ? 'Edit property' : 'Add property' }}
      </h1>
      <p class="text-[13px] text-muted-foreground mt-0.5">
        {{ isEditing ? 'Update property details and units.' : 'Fill in the details to add a new property.' }}
      </p>
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-6">

      <!-- ── Section: Basic info ──────────────────────────────────────── -->
      <div class="bg-card border border-border rounded-xl p-5 space-y-4">
        <h2 class="text-[13.5px] font-semibold text-foreground">Basic information</h2>

        <!-- Name -->
        <div class="space-y-1.5">
          <Label for="name" class="text-[12.5px]">Property name <span class="text-destructive">*</span></Label>
          <Input
            id="name"
            v-model="form.name"
            placeholder="e.g. Sunrise Residences"
            class="h-9 text-[13.5px]"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-[11.5px] text-destructive">{{ form.errors.name }}</p>
        </div>

        <!-- Type -->
        <div class="space-y-1.5">
          <Label class="text-[12.5px]">Property type <span class="text-destructive">*</span></Label>
          <Select v-model="form.type">
            <SelectTrigger class="h-9 text-[13.5px]" :class="{ 'border-destructive': form.errors.type }">
              <SelectValue placeholder="Select type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="residential">Residential</SelectItem>
              <SelectItem value="commercial">Commercial</SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.type" class="text-[11.5px] text-destructive">{{ form.errors.type }}</p>
        </div>

        <!-- Address row -->
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label for="address" class="text-[12.5px]">Address <span class="text-destructive">*</span></Label>
            <Input
              id="address"
              v-model="form.address"
              placeholder="Street address"
              class="h-9 text-[13.5px]"
              :class="{ 'border-destructive': form.errors.address }"
            />
            <p v-if="form.errors.address" class="text-[11.5px] text-destructive">{{ form.errors.address }}</p>
          </div>
          <div class="space-y-1.5">
            <Label for="city" class="text-[12.5px]">City <span class="text-destructive">*</span></Label>
            <Input
              id="city"
              v-model="form.city"
              placeholder="e.g. Cebu City"
              class="h-9 text-[13.5px]"
              :class="{ 'border-destructive': form.errors.city }"
            />
            <p v-if="form.errors.city" class="text-[11.5px] text-destructive">{{ form.errors.city }}</p>
          </div>
        </div>

        <!-- Description -->
        <div class="space-y-1.5">
          <Label for="description" class="text-[12.5px]">Description</Label>
          <Textarea
            id="description"
            v-model="form.description"
            placeholder="Brief description of the property…"
            class="text-[13.5px] resize-none"
            rows="3"
          />
        </div>
      </div>

      <!-- ── Section: Cover photo ─────────────────────────────────────── -->
      <div class="bg-card border border-border rounded-xl p-5 space-y-3">
        <h2 class="text-[13.5px] font-semibold text-foreground">Cover photo</h2>

        <div v-if="previewUrl" class="relative w-full h-44 rounded-lg overflow-hidden border border-border">
          <img :src="previewUrl" alt="Preview" class="w-full h-full object-cover" />
          <button
            type="button"
            class="absolute top-2 right-2 w-7 h-7 rounded-full bg-background/90 border border-border flex items-center justify-center hover:bg-background transition-colors"
            @click="removePhoto"
          >
            <X :size="13" />
          </button>
        </div>

        <label
          v-else
          class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-border rounded-lg cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition-colors"
        >
          <Upload :size="18" class="text-muted-foreground mb-2" />
          <p class="text-[12.5px] font-medium text-muted-foreground">Click to upload photo</p>
          <p class="text-[11px] text-muted-foreground/70">JPG, PNG, WEBP · Max 5MB</p>
          <input type="file" accept="image/*" class="hidden" @change="handlePhoto" />
        </label>
        <p v-if="form.errors.photo" class="text-[11.5px] text-destructive">{{ form.errors.photo }}</p>
      </div>

      <!-- ── Section: Amenities ───────────────────────────────────────── -->
      <div v-if="amenities.length" class="bg-card border border-border rounded-xl p-5 space-y-3">
        <h2 class="text-[13.5px] font-semibold text-foreground">Amenities</h2>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="amenity in amenities"
            :key="amenity.id"
            type="button"
            :class="[
              'flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-[12.5px] transition-all',
              form.amenity_ids.includes(amenity.id)
                ? 'bg-primary text-primary-foreground border-primary font-medium'
                : 'bg-background text-muted-foreground border-border hover:border-border/60',
            ]"
            @click="toggleAmenity(amenity.id)"
          >
            <Check v-if="form.amenity_ids.includes(amenity.id)" :size="11" />
            {{ amenity.name }}
          </button>
        </div>
      </div>

      <!-- ── Actions ──────────────────────────────────────────────────── -->
      <div class="flex items-center justify-between pt-2">
        <Link :href="route('properties.index')">
          <Button variant="outline" type="button" class="h-9 text-[13px]">Cancel</Button>
        </Link>
        <Button type="submit" class="h-9 text-[13px] px-6" :disabled="form.processing">
          {{ form.processing ? 'Saving…' : isEditing ? 'Update property' : 'Create property' }}
        </Button>
      </div>

    </form>
  </div>

</div>
</div>
</template> 