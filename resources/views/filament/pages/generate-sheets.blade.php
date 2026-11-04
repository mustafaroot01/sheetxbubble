<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="generate">
            {{ $this->form }}

            @if($exam_id)
                @if($studentCount !== null)
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-user-group class="w-5 h-5 text-blue-500" />
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                {{ __('app.sheet.student_count', ['count' => $studentCount]) }}
                            </span>
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-printer">
                        {{ __('app.sheet.generate_btn') }}
                    </x-filament::button>
                </div>
            @endif
        </form>

        @if($downloadUrl)
            <div class="p-6 bg-green-50 dark:bg-green-900/30 rounded-xl border border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-check-circle class="w-8 h-8 text-green-500" />
                        <div>
                            <h3 class="text-lg font-bold text-green-800 dark:text-green-200">{{ __('app.sheet.success') }}</h3>
                            <p class="text-sm text-green-600 dark:text-green-400">{{ __('app.sheet.ready_download') }}</p>
                        </div>
                    </div>
                    <a href="{{ $downloadUrl }}" target="_blank"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        {{ __('app.sheet.download_pdf') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
