<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b dark:border-gray-700">
                <th class="text-start py-2 px-3">{{ __('app.correction.question_num') }}</th>
                <th class="text-start py-2 px-3">{{ __('app.correction.student_answer') }}</th>
                <th class="text-start py-2 px-3">{{ __('app.correction.correct_answer') }}</th>
                <th class="text-start py-2 px-3">{{ __('app.correction.mark') }}</th>
                <th class="text-start py-2 px-3">{{ __('app.correction.result') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details ?? [] as $detail)
                <tr class="border-b dark:border-gray-700 {{ $detail['is_correct'] ? '' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <td class="py-2 px-3 font-medium">{{ $detail['q'] }}</td>
                    <td class="py-2 px-3 {{ $detail['is_correct'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400 font-bold' }}">
                        {{ $detail['student_answer'] ?? '-' }}
                    </td>
                    <td class="py-2 px-3">{{ $detail['correct_answer'] ?? '-' }}</td>
                    <td class="py-2 px-3">{{ $detail['earned'] }}/{{ $detail['mark'] }}</td>
                    <td class="py-2 px-3">
                        @if($detail['is_correct'])
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @else
                            <span class="text-red-600 dark:text-red-400">✗</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-400">{{ __('app.no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
