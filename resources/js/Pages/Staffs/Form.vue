<script setup lang="ts">
import { watch, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { CircleCheck, CircleX, LoaderCircle, X } from "lucide-vue-next";
import { VueDatePicker } from '@vuepic/vue-datepicker';

interface Staff {
    id: number;
    name: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    initials: string;
    email: string;
    phone: string | null;
    department: string | null;
    job_title: string | null;
    role: string | null;

    gender: string | null;
    birthday: string | null;
    address: string | null;
    city: string | null;
    province: string | null;
    photo: string | null;
    password: string | null;
    employment_status: string | null;
    status: "Active" | "Inactive" | null;
    last_login: string | null;
}

const props = defineProps<{
    open: boolean;
    mode: "add" | "edit" | "view";
    staff: Staff | null;
}>();

const emit = defineEmits<{ close: [] }>();

const isViewing = () => props.mode === "view";
const isEditing = () => props.mode === "edit";
const isAdding = () => props.mode === "add";


const previewUrl = ref<string | null>(
    props.staff?.photo ? `/storage/${props.staff.photo}` : null,
);
const handlePhoto = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.photo = file;
    previewUrl.value = URL.createObjectURL(file);
};

const removePhoto = () => {
    form.photo = null;
    previewUrl.value = null;

};

// ── Inertia form ──────────────────────────────────────────────────
const form = useForm({
    first_name: "",
    middle_name: "",
    last_name: "",
    email: "",
    phone: "",
    role: "",
    department: "",
    job_title: "",

    gender: "",
    birthday: "",
    address: "",
    city: "",
    province: "",
    photo: null as File | null,
    password: "",
    status: "Active" as "Active" | "Inactive",
    employment_status: "",
    last_login: "",
});

// Sync form when modal opens or staff changes
watch(
    () => [props.open, props.staff],
    () => {
        if (!props.open) return;
        if (props.mode === "add") {
            form.reset();
            previewUrl.value = null;  // ← clear photo on add
            return;
        }
        if (props.staff) {
            form.first_name = props.staff.first_name ?? "";
            form.middle_name = props.staff.middle_name ?? "";
            form.last_name = props.staff.last_name ?? "";
            form.email = props.staff.email ?? "";
            form.phone = props.staff.phone ?? "";
            form.role = props.staff.role ?? "";
            form.department = props.staff.department ?? "";
            form.job_title = props.staff.job_title ?? "";
            form.status = props.staff.status ?? "Active";
            form.gender = props.staff.gender ?? "";
            form.birthday = props.staff.birthday ?? "";
            form.address = props.staff.address ?? "";
            form.city = props.staff.city ?? "";
            form.province = props.staff.province ?? "";

            form.password = props.staff.password ?? "";
            form.employment_status = props.staff.employment_status ?? "Inactive";

            previewUrl.value = props.staff.photo ? `/storage/${props.staff.photo}` : null;
        } else {
            form.reset();
        }
    },
    { immediate: true }
);

const submit = () => {

    if (isEditing() && props.staff) {
        form.transform((data) => ({
            ...data,
            _method: "PUT",
        })).post(route("staffs.update", props.staff.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();

                emit("close");
            },
            
        });
    } else {
        form.post(route("staffs.store"), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => (form.reset(),
                emit("close")),
        });

    }
}

const inputClass = (extra = "") =>
    `w-full px-3 py-2.5 text-[13px] border rounded-lg transition bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 ${extra}`;

const readonlyClass =
    "w-full px-3 py-2.5 text-[13px] border border-slate-100 rounded-lg bg-slate-50 text-slate-600 cursor-default select-none";
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="emit('close')" />

                <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="open"
                        class="relative z-10 bg-white rounded-2xl shadow-xl w-full max-w-6xl overflow-hidden">

                        <!-- Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5">
                            <h2 class="text-white font-bold text-lg">
                                {{ isEditing() ? "✏️ Edit Employee" : isViewing() ? "👁️ View Employee" : "👤 Add New  Employee" }}
                            </h2>
                            <p class="text-indigo-200 text-xs mt-0.5">
                                {{ isEditing() ? "Update the employee information below." : isViewing() ? "Reviewingemployee details." : "Fill in the details to add a new team member." }}
                            </p>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-5 space-y-4 max-h-[65vh] overflow-y-auto">


                            <div class="bg-card border border-border rounded-xl p-5 space-y-3">
                                <h2 class="text-[13.5px] font-semibold text-foreground">
                                    Cover photo
                                </h2>

                                <div v-if="previewUrl" class="relative w-full h-50 rounded-lg  border border-border">
                                    <img :src="previewUrl" alt="Preview" class="w-full h-full object-scale-down" />
                                    <button type="button"
                                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-background/90 border border-border flex items-center justify-center hover:bg-background transition-colors"
                                        @click="removePhoto">
                                        <X :size="13" />
                                    </button>
                                </div>

                                <label v-else
                                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-border rounded-lg cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition-colors">
                                    <Upload :size="18" class="text-muted-foreground mb-2" />
                                    <p class="text-[12.5px] font-medium text-muted-foreground">
                                        Click to upload photo
                                    </p>
                                    <p class="text-[11px] text-muted-foreground/70">
                                        JPG, PNG, WEBP · Max 5MB
                                    </p>
                                    <input type="file" accept="image/*" class="hidden" @change="handlePhoto" />
                                </label>
                                <p v-if="form.errors.photo" class="text-[11.5px] text-destructive">
                                    {{ form.errors.photo }}
                                </p>
                            </div>
                            <!-- Name row -->
                            <div class="grid grid-cols-1 gap-2">
                                <div v-if="!isViewing() && !isEditing()">
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">First
                                        Name</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.first_name || '—' }}</p>
                                    <div v-else>
                                        <input v-model="form.first_name" type="text" placeholder="Juan"
                                            :class="inputClass(form.errors.first_name ? 'border-rose-300' : 'border-slate-200')" />
                                        <p v-if="form.errors.first_name" class="text-[11px] text-rose-500 mt-1">{{
                                            form.errors.first_name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">First
                                        Name</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.first_name || '—' }}</p>
                                    <div v-else>
                                        <input v-model="form.first_name" type="text" placeholder="Juan"
                                            :class="inputClass(form.errors.first_name ? 'border-rose-300' : 'border-slate-200')" />
                                        <p v-if="form.errors.first_name" class="text-[11px] text-rose-500 mt-1">{{
                                            form.errors.first_name }}</p>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Middle
                                        Name</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.middle_name || '—' }}</p>
                                    <input v-else v-model="form.middle_name" type="text" placeholder="Optional"
                                        :class="inputClass('border-slate-200')" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Last
                                        Name</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.last_name || '—' }}</p>
                                    <div v-else>
                                        <input v-model="form.last_name" type="text" placeholder="dela Cruz"
                                            :class="inputClass(form.errors.last_name ? 'border-rose-300' : 'border-slate-200')" />
                                        <p v-if="form.errors.last_name" class="text-[11px] text-rose-500 mt-1">{{
                                            form.errors.last_name }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Email</label>
                                    <p v-if="!isAdding()" :class="readonlyClass"> {{ form.email || '—' }}</p>

                                    <div v-else>
                                        <input v-model="form.email" type="email" placeholder="email@example.com"
                                            :class="inputClass(form.errors.email ? 'border-rose-300' : 'border-slate-200')" />
                                        <p v-if="form.errors.email" class="text-[11px] text-rose-500 mt-1">
                                            {{ form.errors.email }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Phone</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.phone || '—' }}</p>
                                    <input v-else v-model="form.phone" type="text" placeholder="09XX XXX XXXX"
                                        :class="inputClass('border-slate-200')" />
                                </div>
                            </div>

                            <!-- Role / Department -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Role</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.role || '—' }}</p>
                                    <input v-else v-model="form.role" type="text" placeholder="e.g. Manager"
                                        :class="inputClass('border-slate-200')" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Department</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.department || '—' }}</p>
                                    <input v-else v-model="form.department" type="text" placeholder="e.g. Operations"
                                        :class="inputClass('border-slate-200')" />
                                </div>
                            </div>

                            <!-- Gender / Birthday -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Gender</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.gender || '—' }}</p>
                                    <select v-else v-model="form.gender" :class="inputClass('border-slate-200')">
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Birthday</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.birthday || '—' }}</p>
                                   <VueDatePicker v-model="form.birthday" :max-date="new Date()" :model-type="'yyyy-MM-dd'"
                                       :time-config="{ enableTimePicker: false }"
                                        :formats="{ input: 'LLLL dd, yyyy ' }"
                                        placeholder="Select birthday" 
                                        :auto-apply="true"
                                       />
                                    <p v-if="form.errors.birthday" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.birthday }}
                                    </p>
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label
                                    class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Address</label>
                                <p v-if="isViewing()" :class="readonlyClass">{{ form.address || '—' }}</p>
                                <input v-else v-model="form.address" type="text" placeholder="Street address"
                                    :class="inputClass('border-slate-200')" />
                            </div>

                            <!-- City / Province -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">City</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.city || '—' }}</p>
                                    <input v-else v-model="form.city" type="text" placeholder="e.g. Cebu City"
                                        :class="inputClass('border-slate-200')" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Province</label>
                                    <p v-if="isViewing()" :class="readonlyClass">{{ form.province || '—' }}</p>
                                    <input v-else v-model="form.province" type="text" placeholder="e.g. Cebu"
                                        :class="inputClass('border-slate-200')" />
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label
                                    class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Employment
                                    Status</label>
                                <!-- View mode: just a badge -->
                                <div v-if="isViewing()">
                                    <span
                                        :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[12px] font-semibold', form.employment_status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200']">
                                        <span
                                            :class="['w-2 h-2 rounded-full', form.employment_status === 'Active' ? 'bg-emerald-500' : 'bg-rose-400']" />
                                        {{ form.employment_status }}
                                    </span>
                                </div>
                                <!-- Edit/Add mode: radio cards -->
                                <div v-else class="flex gap-3">
                                    <label
                                        :class="['flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all', form.employment_status === 'Active' ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300']">
                                        <input type="radio" v-model="form.employment_status" value="Active"
                                            class="hidden" />
                                        <span
                                            :class="['w-3 h-3 rounded-full flex-shrink-0', form.employment_status === 'Active' ? 'bg-emerald-500' : 'bg-slate-300']" />
                                        <div>
                                            <p
                                                :class="['text-[13px] font-semibold', form.employment_status === 'Active' ? 'text-emerald-700' : 'text-slate-600']">
                                                Active</p>
                                            <p class="text-[11px] text-slate-400">Currently employed</p>
                                        </div>
                                    </label>
                                    <label
                                        :class="['flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all', form.employment_status === 'Inactive' ? 'border-rose-400 bg-rose-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300']">
                                        <input type="radio" v-model="form.employment_status" value="Inactive"
                                            class="hidden" />
                                        <span
                                            :class="['w-3 h-3 rounded-full flex-shrink-0', form.employment_status === 'Inactive' ? 'bg-rose-500' : 'bg-slate-300']" />
                                        <div>
                                            <p
                                                :class="['text-[13px] font-semibold', form.employment_status === 'Inactive' ? 'text-rose-700' : 'text-slate-600']">
                                                Inactive</p>
                                            <p class="text-[11px] text-slate-400">No longer active</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                        </div>

                        <!-- Footer -->
                        <div
                            class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                            <button @click="emit('close')"
                                class="flex items-center gap-2 px-4 py-2.5 text-[13px] font-bold text-slate-700 bg-red-100 hover:bg-red-200 hover:text-red-700 rounded-lg transition-colors">
                                <CircleX :strokeWidth="2.5" class="w-4 h-4 text-red-500" />
                                {{ isViewing() ? 'Close' : 'Cancel' }}
                            </button>

                            <button v-if="!isViewing()" @click="submit" :disabled="form.processing"
                                class="flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm shadow-green-200 disabled:opacity-60 disabled:cursor-not-allowed">
                                <LoaderCircle v-if="form.processing" class="w-4 h-4 animate-spin" />

                                <CircleCheck v-else class="w-4 h-4" />

                                {{
                                    form.processing
                                        ? (isEditing() ? "Saving..." : "Adding...")
                                        : (isEditing() ? "Save Changes" : "Add Employee")
                                }}
                            </button>

                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>