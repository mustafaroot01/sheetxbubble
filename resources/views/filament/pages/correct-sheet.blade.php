<x-filament-panels::page>
    <div class="space-y-6">
        @if(!$showResult)
            <form wire:submit="correct">
                {{ $this->form }}

                @if($exam_id)
                    <div class="mt-4">
                        <x-filament::button type="submit" size="lg">
                            {{ __('app.correction.correct_btn') }}
                        </x-filament::button>
                    </div>
                @endif
            </form>
        @else
            {{-- Result Display --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('app.correction.result_title') }}</h2>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $correctionResult['status'] === 'pass' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                        {{ $correctionResult['status'] === 'pass' ? '✓ ' . __('app.result.pass') : '✗ ' . __('app.result.fail') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.correction.student') }}</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $correctionResult['student_name'] }}</div>
                        <div class="text-xs text-gray-400">{{ $correctionResult['student_id'] }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.correction.score') }}</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $correctionResult['score'] }}/{{ $correctionResult['total_mark'] }}</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.correction.percentage') }}</div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $correctionResult['percentage'] }}%</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-900/30 rounded-lg p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.correction.correct_wrong') }}</div>
                        <div class="text-lg font-bold">
                            <span class="text-green-600 dark:text-green-400">{{ $correctionResult['correct_count'] }}</span>
                            /
                            <span class="text-red-600 dark:text-red-400">{{ $correctionResult['wrong_count'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Answer Details Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left py-2 px-3">{{ __('app.correction.question_num') }}</th>
                                <th class="text-left py-2 px-3">{{ __('app.correction.student_answer') }}</th>
                                <th class="text-left py-2 px-3">{{ __('app.correction.correct_answer') }}</th>
                                <th class="text-left py-2 px-3">{{ __('app.correction.mark') }}</th>
                                <th class="text-left py-2 px-3">{{ __('app.correction.result') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($correctionResult['details'] as $detail)
                                <tr class="border-b dark:border-gray-700 {{ $detail['is_correct'] ? '' : 'bg-red-50 dark:bg-red-900/20' }}">
                                    <td class="py-2 px-3 font-medium">Q{{ $detail['q'] }}</td>
                                    <td class="py-2 px-3 {{ $detail['is_correct'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400 font-bold' }}">
                                        {{ is_array($detail['student_answer'] ?? null) ? implode(', ', $detail['student_answer']) : ($detail['student_answer'] ?? '-') }}
                                    </td>
                                    <td class="py-2 px-3">{{ is_array($detail['correct_answer'] ?? null) ? implode(', ', $detail['correct_answer']) : ($detail['correct_answer'] ?? '-') }}</td>
                                    <td class="py-2 px-3">{{ $detail['earned'] }}/{{ $detail['mark'] }}</td>
                                    <td class="py-2 px-3">
                                        @if($detail['is_correct'])
                                            <span class="text-green-600 dark:text-green-400">✓</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400">✗</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex gap-3">
                    <x-filament::button wire:click="correctAnother" color="primary" size="lg">
                        {{ __('app.correction.correct_another') }}
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
