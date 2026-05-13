<script setup lang="ts">
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {route} from 'ziggy-js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { ChevronRight, Check } from 'lucide-vue-next'

interface Amenity  { id: number; name: string }
interface Property { id: number; name: string }
interface Unit {
  id?: number
  property_id: number
  unit_number: string
  type: string
  floor_number: number | null
  size_sqm: number | null
  rent_price: number | null
  deposit_amount: number | null
  status: string
  description: string
  amenity_ids: number[]
}

const props = defineProps<{
  unit?: Unit
  properties: Property[]
  amenities: Amenity[]
  defaultPropertyId?: number
}>()

const isEditing = computed(() => !!props.unit?.id)

const form = useForm({
  property_id:    props.unit?.property_id    ?? props.defaultPropertyId ?? '',
  unit_number:    props.unit?.unit_number    ?? '',
  type:           props.unit?.type           ?? 'studio',
  floor_number:   props.unit?.floor_number   ?? null as number | null,
  size_sqm:       props.unit?.size_sqm       ?? null as number | null,
  rent_price:     props.unit?.rent_price     ?? null as number | null,
  deposit_amount: props.unit?.deposit_amount ?? null as number | null,
  status:         props.unit?.status         ?? 'vacant',
  description:    props.unit?.description    ?? '',
  amenity_ids:    props.unit?.amenity_ids    ?? [] as number[],
})

const toggleAmenity = (id: number) => {
  const idx = form.amenity_ids.indexOf(id)
  if (idx === -1) form.amenity_ids.push(id)
  else form.amenity_ids.splice(idx, 1)
}

const submit = () => {
  if (isEditing.value) {
    form.put(route('units.update', props.unit!.id))
  } else {
    form.post(route('units.store'))
  }
}

const backHref = computed(() =>
  props.unit?.property_id || props.defaultPropertyId
    ? route('properties.show', props.unit?.property_id ?? props.defaultPropertyId)
    : route('properties.index')
)

const currentProperty = computed(() =>
  props.properties.find(p => p.id === Number(form.property_id))
)
</script>

<template>
  <div class="flex flex-col gap-6 p-6 max-w-2xl">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-[12.5px] text-muted-foreground flex-wrap">
      <Link :href="route('properties.index')" class="hover:text-foreground transition-colors">Properties</Link>
      <ChevronRight :size="12" />
      <Link v-if="currentProperty" :href="route('properties.show', currentProperty.id)" class="hover:text-foreground transition-colors">
        {{ currentProperty.name }}
      </Link>
      <ChevronRight v-if="currentProperty" :size="12" />
      <span class="text-foreground font-medium">{{ isEditing ? 'Edit unit' : 'New unit' }}</span>
    </div>

    <!-- Page title -->
    <div>
      <h1 class="text-xl font-semibold text-foreground">{{ isEditing ? 'Edit unit' : 'Add unit' }}</h1>
      <p class="text-[13px] text-muted-foreground mt-0.5">
        {{ isEditing ? 'Update unit information.' : 'Add a new unit to the property.' }}
      </p>
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-5">

      <!-- ── Property selection (only in create mode) ─────────────────── -->
      <div v-if="!isEditing && !defaultPropertyId" class="bg-card border border-border rounded-xl p-5 space-y-4">
        <h2 class="text-[13.5px] font-semibold text-foreground">Property</h2>
        <div class="space-y-1.5">
          <Label class="text-[12.5px]">Select property <span class="text-destructive">*</span></Label>
          <Select v-model="form.property_id">
            <SelectTrigger class="h-9 text-[13.5px]" :class="{ 'border-destructive': form.errors.property_id }">
              <SelectValue placeholder="Choose a property" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="p in properties" :key="p.id" :value="p.id">
                {{ p.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.property_id" class="text-[11.5px] text-destructive">{{ form.errors.property_id }}</p>
        </div>
      </div>

      <!-- ── Unit identity ────────────────────────────────────────────── -->
      <div class="bg-card border border-border rounded-xl p-5 space-y-4">
        <h2 class="text-[13.5px] font-semibold text-foreground">Unit identity</h2>

        <div class="grid grid-cols-2 gap-3">
          <!-- Unit number -->
          <div class="space-y-1.5">
            <Label for="unit_number" class="text-[12.5px]">Unit number <span class="text-destructive">*</span></Label>
            <Input
              id="unit_number"
              v-model="form.unit_number"
              placeholder="e.g. 3A, 101, 204B"
              class="h-9 text-[13.5px]"
              :class="{ 'border-destructive': form.errors.unit_number }"
            />
            <p v-if="form.errors.unit_number" class="text-[11.5px] text-destructive">{{ form.errors.unit_number }}</p>
          </div>

          <!-- Floor -->
          <div class="space-y-1.5">
            <Label for="floor_number" class="text-[12.5px]">Floor number <span class="text-destructive">*</span></Label>
            <Input
              id="floor_number"
              v-model.number="form.floor_number as number "
              type="number"
              min="0"
              placeholder="e.g. 3"
              class="h-9 text-[13.5px]"
              :class="{ 'border-destructive': form.errors.floor_number }"
            />
            <p v-if="form.errors.floor_number" class="text-[11.5px] text-destructive">{{ form.errors.floor_number }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <!-- Type -->
          <div class="space-y-1.5">
            <Label class="text-[12.5px]">Unit type <span class="text-destructive">*</span></Label>
            <Select v-model="form.type">
              <SelectTrigger class="h-9 text-[13.5px]" :class="{ 'border-destructive': form.errors.type }">
                <SelectValue placeholder="Select type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="studio">Studio</SelectItem>
                <SelectItem value="1BR">1 Bedroom</SelectItem>
                <SelectItem value="2BR">2 Bedrooms</SelectItem>
                <SelectItem value="3BR">3 Bedrooms</SelectItem>
                <SelectItem value="penthouse">Penthouse</SelectItem>
                <SelectItem value="commercial">Commercial</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.type" class="text-[11.5px] text-destructive">{{ form.errors.type }}</p>
          </div>

          <!-- Status -->
          <div class="space-y-1.5">
            <Label class="text-[12.5px]">Status <span class="text-destructive">*</span></Label>
            <Select v-model="form.status">
              <SelectTrigger class="h-9 text-[13.5px]" :class="{ 'border-destructive': form.errors.status }">
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="vacant">Vacant</SelectItem>
                <SelectItem value="occupied">Occupied</SelectItem>
                <SelectItem value="maintenance">Under maintenance</SelectItem>
                <SelectItem value="reserved">Reserved</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.status" class="text-[11.5px] text-destructive">{{ form.errors.status }}</p>
          </div>
        </div>

        <!-- Size -->
        <div class="space-y-1.5">
          <Label for="size_sqm" class="text-[12.5px]">Size (m²)</Label>
          <Input
            id="size_sqm"
            v-model.number="form.size_sqm as number"
            type="number"
            min="0"
            step="0.5"
            placeholder="e.g. 35"
            class="h-9 text-[13.5px]"
            :class="{ 'border-destructive': form.errors.size_sqm }"
          />
          <p v-if="form.errors.size_sqm" class="text-[11.5px] text-destructive">{{ form.errors.size_sqm }}</p>
        </div>
      </div>

      <!-- ── Pricing ───────────────────────────────────────────────────── -->
      <div class="bg-card border border-border rounded-xl p-5 space-y-4">
        <h2 class="text-[13.5px] font-semibold text-foreground">Pricing</h2>

        <div class="grid grid-cols-2 gap-3">
          <!-- Rent -->
          <div class="space-y-1.5">
            <Label for="rent_price" class="text-[12.5px]">Monthly rent (₱) <span class="text-destructive">*</span></Label>
            <Input
              id="rent_price"
              v-model.number="form.rent_price as number"
              type="number"
              min="0"
              step="500"
              placeholder="e.g. 15000"
              class="h-9 text-[13.5px]"
              :class="{ 'border-destructive': form.errors.rent_price }"
            />
            <p v-if="form.errors.rent_price" class="text-[11.5px] text-destructive">{{ form.errors.rent_price }}</p>
          </div>

          <!-- Deposit -->
          <div class="space-y-1.5">
            <Label for="deposit_amount" class="text-[12.5px]">Security deposit (₱) <span class="text-destructive">*</span></Label>
            <Input
              id="deposit_amount"
              v-model.number="form.deposit_amount as number"
              type="number"
              min="0"
              step="500"
              placeholder="e.g. 30000"
              class="h-9 text-[13.5px]"
              :class="{ 'border-destructive': form.errors.deposit_amount }"
            />
            <p v-if="form.errors.deposit_amount" class="text-[11.5px] text-destructive">{{ form.errors.deposit_amount }}</p>
          </div>
        </div>

        <!-- Helper text -->
        <p class="text-[11.5px] text-muted-foreground">Deposit is typically 1–2 months of rent.</p>
      </div>

      <!-- ── Amenities ─────────────────────────────────────────────────── -->
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
                : 'bg-background text-muted-foreground border-border hover:border-foreground/30',
            ]"
            @click="toggleAmenity(amenity.id)"
          >
            <Check v-if="form.amenity_ids.includes(amenity.id)" :size="11" />
            {{ amenity.name }}
          </button>
        </div>
      </div>

      <!-- ── Notes ─────────────────────────────────────────────────────── -->
      <div class="bg-card border border-border rounded-xl p-5 space-y-3">
        <h2 class="text-[13.5px] font-semibold text-foreground">Additional notes</h2>
        <Textarea
          v-model="form.description"
          placeholder="Any special notes about this unit…"
          class="text-[13.5px] resize-none"
          rows="3"
        />
      </div>

      <!-- ── Actions ──────────────────────────────────────────────────── -->
      <div class="flex items-center justify-between pt-2">
        <Link :href="backHref">
          <Button variant="outline" type="button" class="h-9 text-[13px]">Cancel</Button>
        </Link>
        <Button type="submit" class="h-9 text-[13px] px-6" :disabled="form.processing">
          {{ form.processing ? 'Saving…' : isEditing ? 'Update unit' : 'Create unit' }}
        </Button>
      </div>
    </form>
  </div>
</template>