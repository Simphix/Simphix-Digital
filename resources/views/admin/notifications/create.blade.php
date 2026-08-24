<x-app-layout>
    <title>SmartSIM - New Push Notification</title>

    <div class="max-w-2xl mx-auto space-y-6" x-data="{
        targetType: '{{ old('target_type', 'general') }}',
        sendOption: '{{ old('send_option', 'now') }}',
    }">
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                    <i data-lucide="send" class="w-5 h-5"></i>
                </div>
                New Push Notification
            </h1>
            <p class="text-sm text-slate-500 mt-1">Only reaches users who have enabled push notifications on a device.</p>
        </div>

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl space-y-1">
                <ul class="list-disc pl-5 text-xs font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-5">
                @csrf

                <div class="space-y-1.5">
                    <label for="title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Title</label>
                    <x-text-input type="text" id="title" name="title" maxlength="150" :value="old('title')" required
                           class="rounded-xl !text-xs bg-slate-50" placeholder="e.g. New feature available" />
                </div>

                <div class="space-y-1.5">
                    <label for="body" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Message</label>
                    <x-textarea-input id="body" name="body" rows="3" maxlength="500" required
                              class="rounded-xl !text-xs bg-slate-50" placeholder="Notification body text...">{{ old('body') }}</x-textarea-input>
                </div>

                <div class="space-y-1.5">
                    <label for="url" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Link (optional)</label>
                    <x-text-input type="text" id="url" name="url" :value="old('url')"
                           class="rounded-xl !text-xs bg-slate-50" placeholder="/dashboard" />
                    <p class="text-[11px] text-slate-400">Where clicking the notification takes the user. Defaults to the homepage.</p>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Audience</label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                        <button type="button" @click="targetType = 'general'" :class="targetType === 'general' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                            All Users
                        </button>
                        <button type="button" @click="targetType = 'roles'" :class="targetType === 'roles' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                            Specific Roles
                        </button>
                    </div>
                    <input type="hidden" name="target_type" :value="targetType">

                    <div x-show="targetType === 'roles'" class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2" style="display: none;">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-100 rounded-lg text-xs font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" name="target_roles[]" value="{{ $role }}"
                                       @checked(collect(old('target_roles', []))->contains($role))
                                       class="w-3.5 h-3.5 text-primary border-slate-300 rounded focus:ring-primary">
                                {{ str_replace('_', ' ', ucfirst($role)) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Delivery</label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                        <button type="button" @click="sendOption = 'now'" :class="sendOption === 'now' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                            Send Now
                        </button>
                        <button type="button" @click="sendOption = 'schedule'" :class="sendOption === 'schedule' ? 'bg-[#0056D2] text-white' : 'text-slate-700 hover:bg-slate-200/50'" class="py-2 text-xs font-bold rounded-lg transition-all duration-150">
                            Schedule
                        </button>
                    </div>
                    <input type="hidden" name="send_option" :value="sendOption">

                    <div x-show="sendOption === 'schedule'" class="pt-2" style="display: none;">
                        <x-text-input type="datetime-local" name="scheduled_at" :value="old('scheduled_at')"
                               class="rounded-xl !text-xs bg-slate-50" />
                    </div>
                </div>

                <x-primary-button type="submit" class="w-full !text-xs">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span x-text="sendOption === 'now' ? 'Send Notification' : 'Schedule Notification'"></span>
                </x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
