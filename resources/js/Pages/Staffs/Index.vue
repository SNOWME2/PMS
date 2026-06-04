<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppSidebar from '@/components/AppSidebar.vue'
import PageHeader from '@/components/PageHeader.vue'
import { Eye, FilePenLine, Trash2 } from 'lucide-vue-next'
import { watch } from 'vue'

import { debounce } from 'lodash-es'
interface Staff {
    id: number
    name: string
    initials: string 
    email: string
    phone: string | null
    department: string | null
    job_title: string | null
    role: string | null
    status: 'Active' | 'Inactive' | null
    gender: string | null
    birthday: string | null
    address: string | null
    city: string | null
    province: string | null
    photo: string | null
}

type PaginatedStaff = {
    data: Staff[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const props = defineProps<{
    staff: PaginatedStaff
    filters: {
        search?: string
    }
}>()

// ── Local state ───────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
const isDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const isEditing = ref(false)
const staffToDelete = ref<number | null>(null)

// ── Server-side search with debounce ──────────────────────────────
const performSearch = () => {
    router.get(route('staff.index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = debounce(performSearch, 300)

watch(search, () => {
    debouncedSearch()
})

// ── Computed ──────────────────────────────────────────────────────
const staffList = computed(() => 
    props.staff.data.map(s => ({
        ...s,
        role: s.role ?? s.job_title ?? null,
        status: s.status ?? 'Active'
    }))
)

const filteredStaff = computed(() => staffList.value)

const totalCount = computed(() => props.staff.total)
const activeCount = computed(() => staffList.value.filter(s => s.status === 'Active').length)
const inactiveCount = computed(() => staffList.value.filter(s => s.status === 'Inactive').length)

// ── Avatar helpers ────────────────────────────────────────────────


function getAvatarColor(name?: string | null) {
    const colors = [
        'bg-violet-500',
        'bg-blue-500',
        'bg-emerald-500',
        'bg-amber-500',
        'bg-rose-500',
        'bg-cyan-500',
        'bg-indigo-500',
    ]

    if (!name) {
        return 'bg-slate-500'
    }

    return colors[name.charCodeAt(0) % colors.length]
}

// ── Form ──────────────────────────────────────────────────────────
const emptyForm = () => ({
    name: '',
    email: '',
    phone: '',
    department: '',
    role: '',
    status: 'Active' as 'Active' | 'Inactive',
    job_title: '',
    gender: '',
    birthday: '',
    address: '',
    city: '',
    province: '',
})

const form = reactive<Partial<Staff> & { id?: number }>(emptyForm())

function openAddDialog() {
    isEditing.value = false
    Object.assign(form, emptyForm())
    isDialogOpen.value = true
}

function openEditDialog(staff: Staff) {
    isEditing.value = true
    Object.assign(form, { ...staff })
    isDialogOpen.value = true
}
function openViewDialog(staff:Staff){
    isEditing.value = false
    Object.assign(form, { ...staff })
    isDialogOpen.value = true
}

function openDeleteDialog(id: number) {
    staffToDelete.value = id
    isDeleteDialogOpen.value = true
}

// ── CRUD via Inertia ──────────────────────────────────────────────
function saveStaff() {
    if (isEditing.value && form.id) {
        router.put(route('staffs.update', form.id), form, {
            preserveScroll: true,
            onSuccess: () => { isDialogOpen.value = false }
        })
    } else {
        router.post(route('staffs.store'), form, {
            preserveScroll: true,
            onSuccess: () => { isDialogOpen.value = false }
        })
    }
}

function confirmDelete() {
    if (staffToDelete.value !== null) {
        router.delete(route('staffs.destroy', staffToDelete.value), {
            preserveScroll: true,
            onSuccess: () => {
                staffToDelete.value = null
                isDeleteDialogOpen.value = false
            }
        })
    }
}

// // ── Pagination ────────────────────────────────────────────────────
// function goToPage(url: string | null) {
//     if (url) router.visit(url, { preserveScroll: true })
// }
</script>

<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden">
        <AppSidebar />

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <PageHeader title="Staff" subtitle="Manage your staff members" />

            <div class="p-6 flex-1 overflow-auto space-y-6">

                <!-- Stat Cards -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2m0 0v2m0-2h2m-2 0h-2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ totalCount }}</p>
                            <p class="text-xs text-slate-500 font-medium">Total Employees</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ activeCount }}</p>
                            <p class="text-xs text-slate-500 font-medium">Active Staff</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ inactiveCount }}</p>
                            <p class="text-xs text-slate-500 font-medium">Inactive Staff</p>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                    <!-- Table Toolbar -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Employee Directory</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ filteredStaff.length }} employees found</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Search -->
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
                                </svg>
                                <input v-model="search" type="text" placeholder="Search employees..."
                                    class="pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg w-56 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition" />
                            </div>
                            <!-- Add Button -->
                            <button @click="openAddDialog"
                                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shadow-sm shadow-indigo-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Employee
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left">
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Employee</th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Contact</th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role
                                </th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Department</th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="staff in filteredStaff" :key="staff.id"
                                class="hover:bg-slate-50/70 transition-colors group">
                                <!-- Avatar + Name -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            :class="['w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0', getAvatarColor(staff.name)]">
                                            {{ staff.initials }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ staff.name }}</p>
                                            <p class="text-xs text-slate-400">#{{ String(staff.id).padStart(4, '0') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Contact -->
                                <td class="px-6 py-4">
                                    <p class="text-slate-700">{{ staff.email }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ staff.phone }}</p>
                                </td>
                                <!-- Role -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">
                                        {{ staff.role }}
                                    </span>
                                </td>
                                <!-- Department -->
                                <td class="px-6 py-4 text-slate-600">{{ staff.department }}</td>
                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                                        staff.status === 'Active'
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-rose-50 text-rose-600'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', staff.status === 'Active' ? 'bg-emerald-500' : 'bg-rose-400']" />
                                        {{ staff.status }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openViewDialog(staff)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                            title="View Details">
                                        <Eye class="w-4 h-4" />
                                        </button>
                                        <button @click="openEditDialog(staff)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                            title="Edit">
                                            <FilePenLine class="w-4 h-4" />
                                        </button>
                                        <button @click="openDeleteDialog(staff.id)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Remove">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredStaff.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="text-slate-400 text-sm">No employees found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isDialogOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isDialogOpen = false" />
                <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="isDialogOpen"
                        class="relative z-10 bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">

                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5">
                            <h2 class="text-white font-semibold text-lg">
                                {{ isEditing ? '✏️ Edit Employee' : '👤 Add New Employee' }}
                            </h2>
                            <p class="text-indigo-200 text-xs mt-0.5">
                                {{ isEditing ? 'Update the employee information below.' : 'Fill in the details to add a new team member.' }}
                            </p>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full Name</label>
                                <input v-model="form.name" type="text" placeholder="e.g. Maria Santos"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition bg-slate-50" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email Address</label>
                                <input v-model="form.email" type="email" placeholder="email@example.com"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition bg-slate-50" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone Number</label>
                                <input v-model="form.phone" type="text" placeholder="09XX XXX XXXX"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition bg-slate-50" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Role / Position</label>
                                <input v-model="form.role" type="text" placeholder="e.g. Manager"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition bg-slate-50" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Department</label>
                                <input v-model="form.department" type="text" placeholder="e.g. Operations"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition bg-slate-50" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employment
                                    Status</label>
                                <div class="flex gap-3">
                                    <label
                                        :class="['flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all', form.status === 'Active' ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300']">
                                        <input type="radio" v-model="form.status" value="Active" class="hidden" />
                                        <span
                                            :class="['w-3 h-3 rounded-full flex-shrink-0', form.status === 'Active' ? 'bg-emerald-500' : 'bg-slate-300']" />
                                        <div>
                                            <p
                                                :class="['text-sm font-semibold', form.status === 'Active' ? 'text-emerald-700' : 'text-slate-600']">
                                                Active</p>
                                            <p class="text-xs text-slate-400">Currently employed</p>
                                        </div>
                                    </label>
                                    <label
                                        :class="['flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all', form.status === 'Inactive' ? 'border-rose-400 bg-rose-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300']">
                                        <input type="radio" v-model="form.status" value="Inactive" class="hidden" />
                                        <span
                                            :class="['w-3 h-3 rounded-full flex-shrink-0', form.status === 'Inactive' ? 'bg-rose-500' : 'bg-slate-300']" />
                                        <div>
                                            <p
                                                :class="['text-sm font-semibold', form.status === 'Inactive' ? 'text-rose-700' : 'text-slate-600']">
                                                Inactive</p>
                                            <p class="text-xs text-slate-400">No longer active</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 pb-5 flex justify-end gap-3">
                            <button @click="isDialogOpen = false"
                                class="px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button @click="saveStaff"
                                class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm shadow-indigo-200">
                                {{ isEditing ? 'Save Changes' : 'Add Employee' }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100">
            <div v-if="isDeleteDialogOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isDeleteDialogOpen = false" />
                <div class="relative z-10 bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="w-14 h-14 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-slate-800 font-semibold text-lg mb-1">Remove Employee?</h3>
                        <p class="text-slate-500 text-sm">This action is permanent and cannot be undone. The employee
                            record will be deleted.</p>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="isDeleteDialogOpen = false"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDelete"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-rose-500 hover:bg-rose-600 rounded-lg transition-colors">
                            Yes, Remove
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>