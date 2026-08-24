<x-app-layout>
    <div x-data="{ 
        addFieldModalOpen: false,
        editFieldModalOpen: false,
        addPriceModalOpen: false,
        editPriceModalOpen: false,
        
        // Field states
        editField: { id: '', field_name: '', field_code: '', base_price: '', isActive: true },
        
        // Price states
        editPrice: { id: '', service_fields_id: '', price_target: 'role', user_type: 'personal', user_email: '', price: '', commission: '', margin_percent: '' }
    }" class="space-y-8 max-w-7xl mx-auto">
        
        <!-- Breadcrumb & Back -->
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Services
            </a>
            <span class="text-xs text-slate-400 font-semibold font-display">Services Management / {{ $service->name }}</span>
        </div>

        <!-- Success/Error Alerts -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex flex-col gap-1.5 shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 flex-shrink-0 animate-pulse"></i>
                    <span class="text-sm font-extrabold uppercase tracking-wider">Validation Errors Found:</span>
                </div>
                <ul class="list-disc list-inside text-xs text-rose-600 mt-1 pl-8 font-semibold space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Service Profile Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col md:flex-row items-center md:items-start justify-between gap-6">
            <div class="flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
                @if($service->image)
                    <div class="w-16 h-16 rounded-2xl border border-slate-200 overflow-hidden bg-slate-50 flex items-center justify-center p-2">
                        <img src="{{ $service->image }}" class="w-full h-full object-contain" alt="{{ $service->name }}">
                    </div>
                @else
                    <div class="w-16 h-16 rounded-2xl bg-[#0056D2]/5 text-[#0056D2] border border-slate-100 flex items-center justify-center font-bold text-lg uppercase">
                        {{ strtoupper(substr($service->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-extrabold text-slate-800 tracking-tight font-display">{{ $service->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1 max-w-xl font-medium">{{ $service->description ?? 'No description provided' }}</p>
                    <div class="mt-3 flex items-center gap-2 justify-center md:justify-start">
                        @if($service->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-100 rounded-full uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                Inactive
                            </span>
                        @endif
                        <span class="text-xs text-slate-400 font-semibold">• Configured on {{ $service->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Fields Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-md font-bold text-slate-800 font-display">Service Variants (Fields)</h3>
                    <p class="text-xs text-slate-400 mt-1">Configure pricing plans and field codes for this service.</p>
                </div>
                <x-primary-button @click="addFieldModalOpen = true" class="!px-3 !py-2 !text-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Add Variant
                </x-primary-button>
            </div>

            <!-- Fields Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">S/N</th>
                            <th class="py-4 px-6">Variant Name</th>
                            <th class="py-4 px-6">Field Code</th>
                            <th class="py-4 px-6">Base Price</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse($fields as $field)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6 text-slate-400 text-xs">
                                    {{ $fields->firstItem() + $loop->index }}
                                </td>
                                <td class="py-4 px-6 font-bold text-slate-800">
                                    {{ $field->field_name }}
                                </td>
                                <td class="py-4 px-6">
                                    <code class="px-2 py-1 bg-slate-50 border border-slate-100 rounded text-xs font-bold text-[#0056D2]">{{ $field->field_code }}</code>
                                </td>
                                <td class="py-4 px-6 text-[#0056D2] font-bold font-display">
                                    ₦{{ number_format($field->base_price, 2) }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($field->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Variant -->
                                        <button @click="
                                            editField = { 
                                                id: '{{ $field->id }}', 
                                                field_name: '{{ addslashes($field->field_name) }}', 
                                                field_code: '{{ addslashes($field->field_code) }}', 
                                                base_price: '{{ $field->base_price }}', 
                                                isActive: {{ $field->is_active ? 'true' : 'false' }} 
                                            }; 
                                            editFieldModalOpen = true;
                                        " class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            Edit
                                        </button>

                                        <!-- Delete Variant -->
                                        <button type="button" 
                                                @click="confirmDeleteField('{{ $field->id }}', '{{ addslashes($field->field_name) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100/50 rounded-xl transition-all duration-150">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                        
                                        <form id="delete-field-form-{{ $field->id }}" action="{{ route('admin.services.fields.destroy', $field) }}" method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-sm">
                                    No variants defined for this service yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Fields Pagination -->
            {{ $fields->withQueryString()->links('vendor.pagination.custom') }}
        </div>

        <!-- Custom Pricing Configuration Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-md font-bold text-slate-800 font-display">Target / Group Custom Pricing</h3>
                    <p class="text-xs text-slate-400 mt-1">Set customized pricing rates for specific user roles or individual user accounts.</p>
                </div>
                <x-primary-button @click="addPriceModalOpen = true" class="!px-3 !py-2 !text-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Add Custom Price
                </x-primary-button>
            </div>

            <!-- Prices Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">S/N</th>
                            <th class="py-4 px-6">Target</th>
                            <th class="py-4 px-6">Linked Variant</th>
                            <th class="py-4 px-6">Custom Price</th>
                            <th class="py-4 px-6">Commission</th>
                            <th class="py-4 px-6">Platform Margin</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse($prices as $price)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6 text-slate-400 text-xs">
                                    {{ $prices->firstItem() + $loop->index }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($price->user_id)
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800">{{ $price->user->name ?? 'User Account' }}</span>
                                            <span class="text-xs text-slate-400 font-semibold mt-0.5">{{ $price->user->email ?? 'No email' }}</span>
                                        </div>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 uppercase tracking-wider">
                                            Role: {{ $price->user_type }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    @if($price->serviceField)
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800">{{ $price->serviceField->field_name }}</span>
                                            <span class="text-xs text-slate-400 font-semibold mt-0.5">Code: {{ $price->serviceField->field_code }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">All Variants</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-[#0056D2] font-bold font-display">
                                    {{ $service->name === 'airtime' ? number_format($price->price, 2) . '%' : '₦' . number_format($price->price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-emerald-600 font-bold font-display">
                                    {{ $service->name === 'airtime' ? number_format($price->commission, 2) . '%' : '₦' . number_format($price->commission, 2) }}
                                </td>
                                <td class="py-4 px-6 text-violet-600 font-bold font-display">
                                    {{ number_format($price->margin_percent, 2) }}%
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Pricing -->
                                        <button @click="
                                            editPrice = { 
                                                id: '{{ $price->id }}', 
                                                service_fields_id: '{{ $price->service_fields_id ?? '' }}', 
                                                price_target: '{{ $price->user_id ? 'user' : 'role' }}', 
                                                user_type: '{{ $price->user_type ?? 'personal' }}', 
                                                user_email: '{{ $price->user->email ?? '' }}', 
                                                price: '{{ $price->price }}',
                                                commission: '{{ $price->commission }}',
                                                margin_percent: '{{ $price->margin_percent }}'
                                            };
                                            editPriceModalOpen = true;
                                        " class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            Edit
                                        </button>

                                        <!-- Delete Pricing -->
                                        <button type="button" 
                                                @click="confirmDeletePrice('{{ $price->id }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100/50 rounded-xl transition-all duration-150">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                        
                                        <form id="delete-price-form-{{ $price->id }}" action="{{ route('admin.services.prices.destroy', $price) }}" method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-sm">
                                    No custom pricing rules defined for this service.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Prices Pagination -->
            {{ $prices->withQueryString()->links('vendor.pagination.custom') }}
        </div>

        <!-- Add Field Modal -->
        <div x-show="addFieldModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="addFieldModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-900 font-display">Add New Variant</h3>
                        <button @click="addFieldModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.services.fields.store', $service) }}" method="POST" id="addFieldForm">
                        @csrf
                        <div class="space-y-5">
                            <!-- Name -->
                            <div>
                                <x-input-label value="Variant Name" />
                                <x-text-input type="text" name="field_name" required placeholder="e.g., MTN VTU 1GB"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Code -->
                            <div>
                                <x-input-label value="Field Code" />
                                <x-text-input type="text" name="field_code" required placeholder="e.g., mtn_vtu_1gb"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Base Price -->
                            <div>
                                <x-input-label value="Base Price (₦)" />
                                <x-text-input type="number" step="0.01" name="base_price" required placeholder="e.500.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Status -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div>
                                    <span class="block text-sm font-bold text-slate-800">Active Status</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">Toggle to enable or disable service field.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0056D2]"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" @click="addFieldModalOpen = false" class="!text-xs">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">
                                Create Variant
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Field Modal -->
        <div x-show="editFieldModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="editFieldModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-900 font-display">Edit Variant</h3>
                        <button @click="editFieldModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form :action="`/admin/services/fields/${editField.id}`" method="POST" id="editFieldForm">
                        @csrf
                        @method('PUT')
                        <div class="space-y-5">
                            <!-- Name -->
                            <div>
                                <x-input-label value="Variant Name" />
                                <x-text-input type="text" name="field_name" x-bind:value="editField.field_name" required
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Code -->
                            <div>
                                <x-input-label value="Field Code" />
                                <x-text-input type="text" name="field_code" x-bind:value="editField.field_code" required
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Base Price -->
                            <div>
                                <x-input-label value="Base Price (₦)" />
                                <x-text-input type="number" step="0.01" name="base_price" x-bind:value="editField.base_price" required
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Status -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div>
                                    <span class="block text-sm font-bold text-slate-800">Active Status</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">Toggle to enable or disable variant.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" :checked="editField.isActive" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0056D2]"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" @click="editFieldModalOpen = false" class="!text-xs">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">
                                Update Variant
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Custom Price Modal -->
        <div x-show="addPriceModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="addPriceModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-900 font-display">Add Custom Price</h3>
                        <button @click="addPriceModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.services.prices.store', $service) }}" method="POST" id="addPriceForm" x-data="{ addTarget: 'role' }">
                        @csrf
                        <div class="space-y-5">
                            
                            <!-- Variant select -->
                            <div>
                                <x-input-label value="Target Variant" />
                                <x-select-input name="service_fields_id" class="rounded-xl bg-slate-50/50">
                                    <option value="">All Variants (Service Base Price)</option>
                                    @foreach($service->fields as $f)
                                        <option value="{{ $f->id }}">{{ $f->field_name }} (Base: ₦{{ number_format($f->base_price, 2) }})</option>
                                    @endforeach
                                </x-select-input>
                            </div>

                            <!-- Target Type Tabs/Buttons -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Target Selection</label>
                                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                                    <button type="button" @click="addTarget = 'role'" :class="addTarget === 'role' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                                        Role Group
                                    </button>
                                    <button type="button" @click="addTarget = 'user'" :class="addTarget === 'user' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                                        Specific User
                                    </button>
                                </div>
                                <input type="hidden" name="price_target" :value="addTarget">
                            </div>

                            <!-- Target Role Selection (x-show) -->
                            <div x-show="addTarget === 'role'">
                                <x-input-label value="User Role Group" />
                                <x-select-input name="user_type" class="rounded-xl bg-slate-50/50">
                                    <option value="personal">Personal</option>
                                    <option value="agent">Agent</option>
                                    <option value="partner">Partner</option>
                                    <option value="business">Business</option>
                                    <option value="coordinator">Coordinator</option>
                                    <option value="regional_manager">Regional Manager</option>
                                    <option value="staff">Staff</option>
                                    <option value="checker">Checker</option>
                                    <option value="super_admin">Super Admin</option>
                                </x-select-input>
                            </div>

                            <!-- Target Specific User Selection (x-show) -->
                            <div x-show="addTarget === 'user'" style="display: none;">
                                <x-input-label value="User Email Address" />
                                <x-text-input type="email" name="user_email" placeholder="e.g., user@example.com"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Custom Price -->
                            <div>
                                <x-input-label>
                                    Custom Price <span x-text="'{{ $service->name }}' === 'airtime' ? '(%)' : '(₦)'"></span>
                                </x-input-label>
                                <x-text-input type="number" step="0.01" name="price" required placeholder="e.g., 480.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Commission -->
                            <div>
                                <x-input-label>
                                    Commission <span x-text="'{{ $service->name }}' === 'airtime' ? '(%)' : '(₦)'"></span>
                                </x-input-label>
                                <x-text-input type="number" step="0.01" name="commission" required value="0.00" placeholder="e.g., 50.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Platform Margin -->
                            <div>
                                <x-input-label>
                                    Platform Margin (%) <span class="text-slate-400 font-normal normal-case">— credited to the Utilities Revenue Wallet</span>
                                </x-input-label>
                                <x-text-input type="number" step="0.01" name="margin_percent" value="0.00" placeholder="e.g., 2.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" @click="addPriceModalOpen = false" class="!text-xs">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">
                                Save Price
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Custom Price Modal -->
        <div x-show="editPriceModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="editPriceModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-900 font-display">Edit Custom Price</h3>
                        <button @click="editPriceModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form :action="`/admin/services/prices/${editPrice.id}`" method="POST" id="editPriceForm">
                        @csrf
                        @method('PUT')
                        <div class="space-y-5">
                            
                            <!-- Display Linked Variant (Readonly context) -->
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Linked Variant (Inherited)</span>
                                <span class="block text-sm font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5">
                                    <span x-text="editPrice.service_fields_id ? 'Variant Level Pricing' : 'Service Base Level Pricing'"></span>
                                </span>
                            </div>

                            <!-- Target Type Tabs/Buttons -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Target Selection</label>
                                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                                    <button type="button" @click="editPrice.price_target = 'role'" :class="editPrice.price_target === 'role' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                                        Role Group
                                    </button>
                                    <button type="button" @click="editPrice.price_target = 'user'" :class="editPrice.price_target === 'user' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                                        Specific User
                                    </button>
                                </div>
                                <input type="hidden" name="price_target" :value="editPrice.price_target">
                            </div>

                            <!-- Target Role Selection (x-show) -->
                            <div x-show="editPrice.price_target === 'role'">
                                <x-input-label value="User Role Group" />
                                <x-select-input name="user_type" x-bind:value="editPrice.user_type" @change="editPrice.user_type = $event.target.value" class="rounded-xl bg-slate-50/50">
                                    <option value="personal">Personal</option>
                                    <option value="agent">Agent</option>
                                    <option value="partner">Partner</option>
                                    <option value="business">Business</option>
                                    <option value="coordinator">Coordinator</option>
                                    <option value="regional_manager">Regional Manager</option>
                                    <option value="staff">Staff</option>
                                    <option value="checker">Checker</option>
                                    <option value="super_admin">Super Admin</option>
                                </x-select-input>
                            </div>

                            <!-- Target Specific User Selection (x-show) -->
                            <div x-show="editPrice.price_target === 'user'" style="display: none;">
                                <x-input-label value="User Email Address" />
                                <x-text-input type="email" name="user_email" x-bind:value="editPrice.user_email" @input="editPrice.user_email = $event.target.value" placeholder="e.g., user@example.com"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Custom Price -->
                            <div>
                                <x-input-label>
                                    Custom Price <span x-text="'{{ $service->name }}' === 'airtime' ? '(%)' : '(₦)'"></span>
                                </x-input-label>
                                <x-text-input type="number" step="0.01" name="price" x-bind:value="editPrice.price" required placeholder="e.g., 480.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Commission -->
                            <div>
                                <x-input-label>
                                    Commission <span x-text="'{{ $service->name }}' === 'airtime' ? '(%)' : '(₦)'"></span>
                                </x-input-label>
                                <x-text-input type="number" step="0.01" name="commission" x-bind:value="editPrice.commission" required placeholder="e.g., 50.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                            <!-- Platform Margin -->
                            <div>
                                <x-input-label>
                                    Platform Margin (%) <span class="text-slate-400 font-normal normal-case">— credited to the Utilities Revenue Wallet</span>
                                </x-input-label>
                                <x-text-input type="number" step="0.01" name="margin_percent" x-bind:value="editPrice.margin_percent" placeholder="e.g., 2.00"
                                       class="rounded-xl bg-slate-50/50" />
                            </div>

                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" @click="editPriceModalOpen = false" class="!text-xs">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">
                                Update Price
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- SweetAlert CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmAction(formId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to perform this action?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0056D2',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Yes, continue!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Bind confirmation dialogs on form submissions
            const addField = document.getElementById('addFieldForm');
            if (addField) {
                addField.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('addFieldForm');
                });
            }

            const editField = document.getElementById('editFieldForm');
            if (editField) {
                editField.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('editFieldForm');
                });
            }

            const addPrice = document.getElementById('addPriceForm');
            if (addPrice) {
                addPrice.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('addPriceForm');
                });
            }

            const editPrice = document.getElementById('editPriceForm');
            if (editPrice) {
                editPrice.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('editPriceForm');
                });
            }
        });

        function confirmDeleteField(id, name) {
            Swal.fire({
                title: 'Delete Variant?',
                text: `Are you sure you want to permanently delete the variant "${name}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-field-form-' + id).submit();
                }
            });
        }

        function confirmDeletePrice(id) {
            Swal.fire({
                title: 'Delete Pricing Config?',
                text: "Are you sure you want to delete this custom pricing configuration?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-price-form-' + id).submit();
                }
            });
        }
    </script>
</x-app-layout>
