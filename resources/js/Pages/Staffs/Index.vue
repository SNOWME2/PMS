<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { debounce } from "lodash-es";
import AppSidebar from "@/components/AppSidebar.vue";
import PageHeader from "@/components/PageHeader.vue";
import { Eye, FilePenLine, Trash2 } from "lucide-vue-next";
import StaffModal from "./Form.vue";

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
    status: string| null;
    employment_status: string | null;
    last_login: string | null;
    gender: string | null;
    birthday: string | null;
    date_of_birth: string | null;
    address: string | null;
    city: string | null;
    province: string | null;
    photo: string | null;
}

type PaginatedStaff = {
    data: Staff[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    staff: PaginatedStaff;
    filters: { search?: string };
}>();

// ── Search ────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? "");

const performSearch = debounce(() => {
    router.get(
        route("staffs.index"),
        { search: search.value || undefined },
        { preserveState: true, replace: true }
    );
}, 300);

watch(search, () => performSearch());

// ── Computed ──────────────────────────────────────────────────────
const staffList = computed(() =>
    props.staff.data.map((s) => ({
        ...s,
        role: s.role ?? s.job_title ?? null,
        employment_status : s.employment_status ?? "Active",
        status: s.status ?? "Active",
        
    }))
);

const totalCount = computed(() => props.staff.total);
const activeCount = computed(() => staffList.value.filter((s) => s.employment_status === "Active").length);
const inactiveCount = computed(() => staffList.value.filter((s) => s.employment_status === "Inactive").length);
const onlineCount = computed(() => staffList.value.filter((s) => s.status === "Active").length);
// ── Avatar ────────────────────────────────────────────────────────
function getAvatarColor(name?: string | null) {
    const colors = [
        "bg-violet-500", "bg-blue-500", "bg-emerald-500", "bg-amber-500",
        "bg-rose-500", "bg-cyan-500", "bg-indigo-500", "bg-fuchsia-500",
    ];
    if (!name) return "bg-slate-400";
    return colors[name.charCodeAt(0) % colors.length];
}

// ── Modal state ───────────────────────────────────────────────────
const modalOpen = ref(false);
const modalMode = ref<"add" | "edit" | "view">("add");
const selectedStaff = ref<Staff | null>(null);

function openAdd() {
    selectedStaff.value = null;
    modalMode.value = "add";
    modalOpen.value = true;
}

function openEdit(staff: Staff) {
    selectedStaff.value = staff;
    modalMode.value = "edit";
    modalOpen.value = true;
}

function openView(staff: Staff) {
    selectedStaff.value = staff;
    modalMode.value = "view";
    modalOpen.value = true;
}

// ── Delete ────────────────────────────────────────────────────────
const deleteDialogOpen = ref(false);
const staffToDelete = ref<number | null>(null);

function openDeleteDialog(id: number) {
    staffToDelete.value = id;
    deleteDialogOpen.value = true;
}

function confirmDelete() {
    if (staffToDelete.value !== null) {
        router.delete(route("staffs.destroy", staffToDelete.value), {
            preserveScroll: true,
            onSuccess: () => {
                staffToDelete.value = null;
                deleteDialogOpen.value = false;
            },
        });
    }
}

// ── Pagination ────────────────────────────────────────────────────
function goToPage(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true });
}
</script>

<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden">
        <AppSidebar />

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <PageHeader title="Staff" subtitle="Manage your staff members" />

            <div class="p-6 flex-1 overflow-auto space-y-5">

                <!-- ── Stat Cards ── -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="relative bg-white rounded-2xl p-5 border border-indigo-100 shadow-sm overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/60 to-transparent" />
                        <div class="relative flex items-center gap-4">
                            <div
                                class="w-11 h-11 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[23px] font-bold text-slate-800 leading-none">{{ totalCount }}</p>
                                <p class="text-[11px] text-slate-500 font-medium mt-1">Total Employees</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm overflow-hidden">
                        <div class="absolute inset-0 bg-linear-to-br from-emerald-50/60 to-transparent" />
                        <div class="relative flex items-center gap-4">
                            <div
                                class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[23px] font-bold text-emerald-700 leading-none">{{ activeCount }}</p>
                                <p class="text-[11px] text-slate-500 font-medium mt-1">Active Staff</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm overflow-hidden">
                        <div class="absolute inset-0 bg-linear-to-br from-emerald-50/60 to-transparent" />
                        <div class="relative flex items-center gap-4">
                            <div
                                class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[23px] font-bold text-emerald-700 leading-none">{{ onlineCount }}</p>
                                <p class="text-[11px] text-slate-500 font-medium mt-1">Online</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative bg-white rounded-2xl p-5 border border-rose-100 shadow-sm overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-rose-50/60 to-transparent" />
                        <div class="relative flex items-center gap-4">
                            <div
                                class="w-11 h-11 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[23px] font-bold text-rose-600 leading-none">{{ inactiveCount }}</p>
                                <p class="text-[11px] text-slate-500 font-medium mt-1">Inactive Staff</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Table Card ── -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                    <!-- Toolbar -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-[15px] font-bold text-slate-800">Employee Directory</h2>
                            <p class="text-[11.5px] text-slate-400 mt-0.5">
                                Showing {{ staffList.length }} of {{ totalCount }} employees
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
                                </svg>
                                <input v-model="search" type="text" placeholder="Search employees..."
                                    class="pl-9 pr-4 py-2 text-[13px] bg-slate-50 border border-slate-200 rounded-lg w-56 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition" />
                            </div>
                            <button @click="openAdd"
                                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-all shadow-sm shadow-indigo-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
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
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Employee</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Contact</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Role</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Department</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Employment Status</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Activity Status</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-left">
                                    Last Active</th>
                                <th
                                    class="px-6 py-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-widest text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="staff in staffList" :key="staff.id"
                                class="hover:bg-indigo-50/20 transition-colors group">
                                <!-- Employee -->
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img v-if="staff.photo" :src="`/storage/${staff.photo}`" :alt="staff.name"
                                            class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm flex-shrink-0" />
                                        <div v-else
                                            :class="['w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 ring-2 ring-white shadow-sm', getAvatarColor(staff.name)]">
                                            {{ staff.initials }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 text-[13px]">{{ staff.name }}</p>
                                            <p class="text-[11px] text-slate-400">#{{ String(staff.id).padStart(4, "0")
                                            }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td class="px-6 py-3.5">
                                    <p class="text-[12.5px] text-slate-700">{{ staff.email }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ staff.phone ?? "—" }}</p>
                                </td>

                                <!-- Role -->
                                <td class="px-6 py-3.5">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">
                                        {{ staff.role ?? "—" }}
                                    </span>
                                </td>

                                <!-- Department -->
                                <td class="px-6 py-3.5">
                                    <span v-if="staff.department"
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-semibold border border-indigo-100">
                                        {{ staff.department }}
                                    </span>
                                    <span v-else class="text-slate-400 text-[12px]">—</span>
                                </td>

                                <!-- Employment Status -->
                                <td class="px-6 py-3.5">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold',
                                        staff.employment_status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', staff.employment_status === 'Active' ? 'bg-emerald-500' : 'bg-rose-400']" />
                                        {{ staff.employment_status }}
                                    </span>
                                </td>
                                <!-- Activity Status -->
                                <td class="px-6 py-3.5">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold',
                                        staff.status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', staff.status === 'Active' ? 'bg-emerald-500' : 'bg-rose-400']" />
                                        {{ staff.status }}
                                    </span>
                                </td>
                                <!-- Last Active -->
                                <td class="px-6 py-3.5">
                                    <span v-if="staff.last_login"
                                        class="text-[12px] text-slate-700">{{ new Date(staff.last_login).toLocaleDateString() }}</span>
                                    <span v-else class="text-slate-400 text-[12px]">—</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-3.5 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openView(staff)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                            title="View">
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <button @click="openEdit(staff)"
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

                            <!-- Empty -->
                            <tr v-if="staffList.length === 0">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="text-slate-400 text-[13px] font-medium">No employees found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-6 py-3.5 bg-slate-50/60 border-t border-slate-100 flex items-center justify-between">
                        <p class="text-[12px] text-slate-400">
                            Page <span class="font-bold text-slate-700">{{ props.staff.current_page }}</span>
                            of <span class="font-bold text-slate-700">{{ props.staff.last_page }}</span>
                            · <span class="font-bold text-slate-700">{{ props.staff.total }}</span> total
                        </p>
                        <div class="flex gap-2">
                            <button @click="goToPage(props.staff.prev_page_url)" :disabled="!props.staff.prev_page_url"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-semibold rounded-lg border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Prev
                            </button>
                            <button @click="goToPage(props.staff.next_page_url)" :disabled="!props.staff.next_page_url"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-semibold rounded-lg border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                Next
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Staff Modal -->
    <StaffModal :open="modalOpen" :mode="modalMode" :staff="selectedStaff" @close="modalOpen = false" />

    <!-- Delete Modal -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100">
            <div v-if="deleteDialogOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="deleteDialogOpen = false" />
                <div class="relative z-10 bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="w-14 h-14 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-slate-800 font-bold text-lg mb-1">Remove Employee?</h3>
                        <p class="text-slate-500 text-[13px] leading-relaxed">This action is permanent and cannot be
                            undone.</p>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="deleteDialogOpen = false"
                            class="flex-1 px-4 py-2.5 text-[13px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDelete"
                            class="flex-1 px-4 py-2.5 text-[13px] font-bold text-white bg-rose-500 hover:bg-rose-600 rounded-lg transition-colors">
                            Yes, Remove
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>