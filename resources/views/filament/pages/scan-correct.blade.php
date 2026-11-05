<x-filament-panels::page>
    <div x-data="scanApp()" class="space-y-5 max-w-5xl mx-auto">

        {{-- Error Alert --}}
        <div x-show="errorMsg" x-cloak x-transition class="rounded-xl bg-danger-50 dark:bg-danger-500/10 p-4 flex items-center gap-3 ring-1 ring-danger-300 dark:ring-danger-500/20">
            <div class="w-10 h-10 rounded-full bg-danger-100 dark:bg-danger-500/20 flex items-center justify-center shrink-0">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-danger-600 dark:text-danger-400" />
            </div>
            <p class="text-danger-800 dark:text-danger-300 text-sm font-medium flex-1" x-text="errorMsg"></p>
            <button @click="errorMsg = ''" class="text-danger-400 hover:text-danger-600 transition p-1 rounded-lg hover:bg-danger-100 dark:hover:bg-danger-500/10">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Camera result panel (shows the annotated sheet to verify reading) --}}
        <div x-show="resultPanel" x-cloak class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between gap-4"
                 :class="resultPanel && resultPanel.result.status === 'pass' ? 'bg-success-600' : 'bg-danger-600'">
                <div class="min-w-0">
                    <p class="text-white font-bold text-base truncate" x-text="resultPanel && resultPanel.student.name"></p>
                    <p class="text-white/60 text-xs truncate" x-text="resultPanel && (resultPanel.student.id + ' • ' + resultPanel.student.exam)"></p>
                </div>
                <div class="text-end shrink-0">
                    <div class="text-white font-black text-3xl leading-none">
                        <span x-text="resultPanel && resultPanel.result.score"></span><span class="text-lg text-white/40" x-text="resultPanel && ('/' + resultPanel.result.total_mark)"></span>
                    </div>
                    <div class="text-white/60 text-xs font-semibold" x-text="resultPanel && (resultPanel.result.percentage + '% · ' + resultPanel.result.correct_count + ' / ' + resultPanel.result.wrong_count)"></div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 p-3">
                <template x-if="resultPanel && resultPanel.overlay">
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                        <img :src="'data:image/jpeg;base64,' + resultPanel.overlay" class="w-full" />
                    </div>
                </template>
                <div class="rounded-lg ring-1 ring-gray-100 dark:ring-gray-800 overflow-hidden">
                    <div class="overflow-y-auto" style="max-height: 320px">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800/80">
                                <tr>
                                    <th class="py-2 px-3 text-start text-[10px] font-bold text-gray-400 uppercase">#</th>
                                    <th class="py-2 px-3 text-start text-[10px] font-bold text-gray-400 uppercase">{{ __('app.correction.student_answer') }}</th>
                                    <th class="py-2 px-3 text-start text-[10px] font-bold text-gray-400 uppercase">{{ __('app.correction.correct_answer') }}</th>
                                    <th class="py-2 px-3 text-center text-[10px] font-bold text-gray-400 uppercase">{{ __('app.correction.result') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                <template x-for="d in (resultPanel ? resultPanel.result.details : [])" :key="d.q">
                                    <tr :class="d.is_correct ? '' : 'bg-danger-50/40 dark:bg-danger-500/5'">
                                        <td class="py-1.5 px-3 text-gray-500 text-xs font-mono font-bold" x-text="d.q"></td>
                                        <td class="py-1.5 px-3"><span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold" :class="d.is_correct ? 'bg-success-100 dark:bg-success-500/15 text-success-700 dark:text-success-400' : 'bg-danger-100 dark:bg-danger-500/15 text-danger-700 dark:text-danger-400'" x-text="d.student_answer || '-'"></span></td>
                                        <td class="py-1.5 px-3"><span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-400 text-xs font-bold" x-text="d.correct_answer || '-'"></span></td>
                                        <td class="py-1.5 px-3 text-center" x-html="d.is_correct ? '<span class=\'text-success-500\'>&#10003;</span>' : '<span class=\'text-danger-500\'>&#10007;</span>'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="p-3 border-t border-gray-100 dark:border-gray-800 flex justify-center gap-3">
                <button @click="rescanResult()" :disabled="resultPanel && resultPanel.saving" class="inline-flex items-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50 transition-all">
                    <x-heroicon-o-arrow-path class="w-5 h-5" />
                    {{ __('app.scan.scan_another') }}
                </button>
                <button @click="confirmResult()" :disabled="resultPanel && resultPanel.saving" class="inline-flex items-center gap-2 rounded-xl bg-success-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-success-500 active:scale-[0.98] disabled:opacity-50 transition-all">
                    <x-heroicon-s-check-circle class="w-5 h-5" />
                    <span x-text="(resultPanel && resultPanel.saving) ? '{{ __('app.loading') }}' : '{{ __('app.confirm') }}'"></span>
                </button>
            </div>
        </div>

        @if($showResult && $scanResult)
        {{-- ===== UPLOAD RESULT VIEW ===== --}}
        <div class="space-y-5">
            <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
                <div class="relative overflow-hidden {{ $scanResult['status'] === 'pass' ? 'bg-gradient-to-r from-success-600 to-success-500' : 'bg-gradient-to-r from-danger-600 to-danger-500' }} px-6 py-5">
                    <div class="relative flex items-center justify-between flex-wrap gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                                @if($scanResult['status'] === 'pass')
                                    <x-heroicon-s-check-circle class="w-7 h-7 text-white" />
                                @else
                                    <x-heroicon-s-x-circle class="w-7 h-7 text-white" />
                                @endif
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-lg">{{ $scanResult['student_name'] }}</h3>
                                <p class="text-white/60 text-sm">{{ $scanResult['student_id'] }} &bull; {{ $scanResult['exam_name'] }}</p>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-white font-black text-4xl leading-none">{{ $scanResult['score'] }}<span class="text-xl text-white/40">/{{ $scanResult['total_mark'] }}</span></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-800 rtl:divide-x-reverse">
                    <div class="py-4 text-center">
                        <div class="text-2xl font-black text-primary-600 dark:text-primary-400">{{ $scanResult['percentage'] }}%</div>
                        <div class="text-[11px] text-gray-400 font-medium mt-0.5">{{ __('app.correction.percentage') }}</div>
                    </div>
                    <div class="py-4 text-center">
                        <div class="text-2xl font-black text-success-600 dark:text-success-400">{{ $scanResult['correct_count'] }}</div>
                        <div class="text-[11px] text-gray-400 font-medium mt-0.5">{{ __('app.scan.correct') }}</div>
                    </div>
                    <div class="py-4 text-center">
                        <div class="text-2xl font-black text-danger-600 dark:text-danger-400">{{ $scanResult['wrong_count'] }}</div>
                        <div class="text-[11px] text-gray-400 font-medium mt-0.5">{{ __('app.scan.wrong') }}</div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @if($overlayImage)
                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                        <x-heroicon-o-document-magnifying-glass class="w-4 h-4 text-primary-500" />
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('app.scan.corrected_sheet') }}</span>
                    </div>
                    <div class="p-3"><img src="data:image/jpeg;base64,{{ $overlayImage }}" class="w-full rounded-lg" /></div>
                </div>
                @endif
                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                        <x-heroicon-o-list-bullet class="w-4 h-4 text-primary-500" />
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('app.result.answer_details') }}</span>
                    </div>
                    <div class="overflow-y-auto" style="max-height: 420px">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800/80 z-10">
                                <tr>
                                    <th class="py-2.5 px-3 text-start text-[10px] font-bold text-gray-400 uppercase">#</th>
                                    <th class="py-2.5 px-3 text-start text-[10px] font-bold text-gray-400 uppercase">{{ __('app.correction.student_answer') }}</th>
                                    <th class="py-2.5 px-3 text-start text-[10px] font-bold text-gray-400 uppercase">{{ __('app.correction.correct_answer') }}</th>
                                    <th class="py-2.5 px-3 text-center text-[10px] font-bold text-gray-400 uppercase">{{ __('app.correction.result') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach($scanResult['details'] as $d)
                                <tr class="{{ $d['is_correct'] ? '' : 'bg-danger-50/40 dark:bg-danger-500/5' }}">
                                    <td class="py-2 px-3 text-gray-500 text-xs font-mono font-bold">{{ $d['q'] }}</td>
                                    <td class="py-2 px-3"><span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold {{ $d['is_correct'] ? 'bg-success-100 dark:bg-success-500/15 text-success-700 dark:text-success-400' : 'bg-danger-100 dark:bg-danger-500/15 text-danger-700 dark:text-danger-400' }}">{{ is_array($d['student_answer'] ?? null) ? implode(', ', $d['student_answer']) : ($d['student_answer'] ?? '-') }}</span></td>
                                    <td class="py-2 px-3"><span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-400 text-xs font-bold">{{ is_array($d['correct_answer'] ?? null) ? implode(', ', $d['correct_answer']) : ($d['correct_answer'] ?? '-') }}</span></td>
                                    <td class="py-2 px-3 text-center">{!! $d['is_correct'] ? '<span class="text-success-500">&#10003;</span>' : '<span class="text-danger-500">&#10007;</span>' !!}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="flex justify-center pt-1">
                <button wire:click="resetScan" class="inline-flex items-center gap-2.5 rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-primary-500 active:scale-[0.98] transition-all">
                    <x-heroicon-s-camera class="w-5 h-5" />
                    {{ __('app.scan.scan_another') }}
                </button>
            </div>
        </div>
        @else

        {{-- ===== CAMERA VIEW (continuous scanning) ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5" wire:ignore>

            {{-- Camera Section (2/3) --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">

                {{-- Camera Header --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center">
                            <x-heroicon-s-video-camera class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('app.scan.mode_camera') }}</h3>
                            <p class="text-[11px] text-gray-400 leading-tight">{{ __('app.scan.description') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div x-show="correctedCount > 0" x-cloak class="flex items-center gap-1.5 text-xs font-bold bg-success-50 dark:bg-success-500/10 text-success-700 dark:text-success-400 px-2.5 py-1 rounded-lg">
                            <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                            <span x-text="correctedCount"></span>
                        </div>
                        <div x-show="cameraOn" x-cloak class="flex items-center gap-1.5 text-xs font-mono text-gray-400 bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse"></span>
                            <span x-text="scanCount"></span>
                            <span x-text="detectorKind" class="text-[10px] opacity-70"></span>
                        </div>
                    </div>
                </div>

                {{-- Debug readout (helps diagnose QR + OMR detection) --}}
                <div x-show="cameraOn" x-cloak class="px-4 py-1.5 bg-gray-900 text-gray-300 text-[11px] font-mono border-b border-gray-800 space-y-0.5">
                    <div class="truncate" x-text="'QR: ' + (debugRaw || '—')"></div>
                    <div class="truncate text-amber-400" x-text="debugServer || 'server: —'"></div>
                    <div x-show="debugFills" class="truncate text-cyan-400 text-[10px]" x-text="debugFills"></div>
                </div>

                {{-- Camera View --}}
                <div class="relative bg-gray-950" style="aspect-ratio:3/4; max-height:82vh; min-height:60vh">
                    <video x-ref="video" autoplay playsinline muted class="absolute inset-0 w-full h-full object-contain"></video>
                    <canvas x-ref="scanCanvas" class="hidden"></canvas>

                    {{-- Idle State --}}
                    <div x-show="!cameraOn && !processing" class="absolute inset-0 flex flex-col items-center justify-center text-gray-500">
                        <div class="w-16 h-16 rounded-2xl bg-gray-800/80 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ __('app.scan.camera_loading') }}</p>
                        <p class="text-xs text-gray-600 mt-1">{{ __('app.scan.step1_desc') }}</p>
                    </div>

                    {{-- Processing Overlay with Student Info --}}
                    <div x-show="processing" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm z-20">
                        <div class="text-center">
                            {{-- Student info card --}}
                            <div x-show="currentStudent" x-cloak class="bg-white/10 rounded-2xl px-6 py-4 mb-4 backdrop-blur-sm max-w-xs mx-auto">
                                <p class="text-white font-bold text-base" x-text="currentStudent?.student_name"></p>
                                <p class="text-white/50 text-xs mt-0.5" x-text="currentStudent?.student_id"></p>
                                <p class="text-white/40 text-[11px] mt-1" x-text="currentStudent?.exam_name"></p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mb-3 mx-auto">
                                <svg class="animate-spin w-6 h-6 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                            <p class="text-white text-sm font-bold">{{ __('app.scan.auto_correcting') }}</p>
                            <p class="text-white/50 text-xs mt-1">{{ __('app.scan.processing') }}</p>
                        </div>
                    </div>

                    {{-- Scan Overlay --}}
                    <div x-show="cameraOn && !processing" x-cloak class="absolute inset-0 z-10 pointer-events-none">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-52 h-52 relative">
                                <div class="absolute top-0 left-0 w-10 h-10 border-t-[3px] border-l-[3px] rounded-tl-lg transition-colors duration-300" :class="qrFound ? 'border-green-400' : 'border-white/60'"></div>
                                <div class="absolute top-0 right-0 w-10 h-10 border-t-[3px] border-r-[3px] rounded-tr-lg transition-colors duration-300" :class="qrFound ? 'border-green-400' : 'border-white/60'"></div>
                                <div class="absolute bottom-0 left-0 w-10 h-10 border-b-[3px] border-l-[3px] rounded-bl-lg transition-colors duration-300" :class="qrFound ? 'border-green-400' : 'border-white/60'"></div>
                                <div class="absolute bottom-0 right-0 w-10 h-10 border-b-[3px] border-r-[3px] rounded-br-lg transition-colors duration-300" :class="qrFound ? 'border-green-400' : 'border-white/60'"></div>
                                <div x-show="!qrFound" class="absolute left-3 right-3 h-0.5 bg-gradient-to-r from-transparent via-primary-400 to-transparent animate-scan-line"></div>
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2">
                            <div class="px-4 py-2 rounded-full backdrop-blur-md text-xs font-semibold flex items-center gap-2 transition-all duration-300"
                                 :class="qrFound ? 'bg-green-500/90 text-white' : 'bg-black/50 text-white/80'">
                                <span class="w-2 h-2 rounded-full animate-pulse" :class="qrFound ? 'bg-white' : 'bg-red-400'"></span>
                                <span x-text="qrFound ? '{{ __("app.scan.qr_found") }}' : '{{ __("app.scan.searching_qr") }}'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Result Toast (shows over camera then auto-hides) --}}
                    <div x-show="showToast" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="translate-y-4 opacity-0"
                         x-transition:enter-end="translate-y-0 opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="translate-y-0 opacity-100"
                         x-transition:leave-end="-translate-y-4 opacity-0"
                         class="absolute top-3 left-3 right-3 z-30">
                        <div class="rounded-xl shadow-2xl overflow-hidden backdrop-blur-md"
                             :class="lastResult?.status === 'pass' ? 'bg-success-600/95' : 'bg-danger-600/95'">
                            <div class="px-4 py-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                                        <template x-if="lastResult?.status === 'pass'">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                        </template>
                                        <template x-if="lastResult?.status !== 'pass'">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-white font-bold text-sm truncate" x-text="lastResult?.student_name"></p>
                                        <p class="text-white/60 text-xs truncate" x-text="lastResult?.student_id + ' &bull; ' + lastResult?.exam_name"></p>
                                    </div>
                                </div>
                                <div class="text-end shrink-0">
                                    <div class="text-white font-black text-2xl leading-none">
                                        <span x-text="lastResult?.score"></span><span class="text-sm text-white/40" x-text="'/' + lastResult?.total_mark"></span>
                                    </div>
                                    <div class="text-white/50 text-[10px] font-semibold mt-0.5" x-text="lastResult?.percentage + '%'"></div>
                                </div>
                            </div>
                            <div class="h-1 bg-white/10">
                                <div class="h-full bg-white/40 transition-all duration-100 ease-linear" :style="'width:' + toastProgress + '%'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Camera Controls --}}
                <div class="p-3 flex justify-center border-t border-gray-100 dark:border-gray-800">
                    <template x-if="!cameraOn">
                        <button @click="startCamera()" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-primary-500 active:scale-[0.98] transition-all">
                            <x-heroicon-s-video-camera class="w-4 h-4" />
                            {{ __('app.scan.start_camera') }}
                        </button>
                    </template>
                    <template x-if="cameraOn">
                        <button @click="stopCamera()" class="inline-flex items-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 px-6 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 active:scale-[0.98] transition-all">
                            <x-heroicon-s-stop class="w-4 h-4" />
                            {{ __('app.scan.stop_camera') }}
                        </button>
                    </template>
                </div>
            </div>

            {{-- Sidebar (1/3) --}}
            <div class="space-y-4">

                {{-- Steps --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-light-bulb class="w-4 h-4 text-warning-500" />
                            {{ __('app.scan.how_it_works') }}
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-500/15 flex items-center justify-center shrink-0 mt-0.5">
                                <span class="text-xs font-black text-primary-600 dark:text-primary-400">1</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('app.scan.step1_title') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ __('app.scan.step1_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-warning-100 dark:bg-warning-500/15 flex items-center justify-center shrink-0 mt-0.5">
                                <span class="text-xs font-black text-warning-600 dark:text-warning-400">2</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('app.scan.step2_title') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ __('app.scan.step2_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-success-100 dark:bg-success-500/15 flex items-center justify-center shrink-0 mt-0.5">
                                <span class="text-xs font-black text-success-600 dark:text-success-400">3</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('app.scan.step3_title') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ __('app.scan.step3_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Results (live list from camera corrections) --}}
                <div x-show="results.length > 0" x-cloak class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-success-500" />
                            {{ __('app.scan.today_stats') }}
                        </h3>
                        <span class="text-xs font-bold text-gray-400 bg-gray-50 dark:bg-gray-800 px-2 py-0.5 rounded-md" x-text="results.length"></span>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-800 max-h-64 overflow-y-auto">
                        <template x-for="(r, i) in results" :key="i">
                            <div class="px-4 py-2.5 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="r.student_name"></p>
                                    <p class="text-[11px] text-gray-400 truncate" x-text="r.student_id"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-sm font-black" :class="r.status === 'pass' ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'" x-text="r.score + '/' + r.total_mark"></span>
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center" :class="r.status === 'pass' ? 'bg-success-100 dark:bg-success-500/15' : 'bg-danger-100 dark:bg-danger-500/15'">
                                        <template x-if="r.status === 'pass'">
                                            <svg class="w-3 h-3 text-success-600 dark:text-success-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                        </template>
                                        <template x-if="r.status !== 'pass'">
                                            <svg class="w-3 h-3 text-danger-600 dark:text-danger-400" fill="currentColor" viewBox="0 0 20 20"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Upload Fallback --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm overflow-hidden">
                    <button @click="uploadOpen = !uploadOpen" class="w-full px-4 py-3 flex items-center justify-between text-sm hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400 font-medium">
                            <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                            {{ __('app.scan.mode_upload') }}
                        </span>
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform duration-200" ::class="uploadOpen && 'rotate-180'" />
                    </button>
                    <div x-show="uploadOpen" x-collapse x-cloak class="px-4 pb-4">
                        <template x-if="!previewUrl">
                            <div @click="$refs.fileInput.click()" @dragover.prevent="dragOver=true" @dragleave.prevent="dragOver=false" @drop.prevent="dragOver=false; handleDrop($event)"
                                 :class="dragOver ? 'border-primary-400 bg-primary-50 dark:bg-primary-500/5' : 'border-gray-200 dark:border-gray-700'"
                                 class="border-2 border-dashed rounded-xl cursor-pointer hover:border-primary-400 transition-all flex flex-col items-center justify-center py-8 text-center group">
                                <input x-ref="fileInput" type="file" accept="image/*" @change="handleFile($event)" class="hidden" />
                                <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-2.5 group-hover:bg-primary-50 dark:group-hover:bg-primary-500/10 transition">
                                    <x-heroicon-o-photo class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition" />
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 text-sm font-semibold">{{ __('app.scan.upload_title') }}</p>
                                <p class="text-gray-400 text-[11px] mt-1">{{ __('app.scan.upload_desc') }}</p>
                            </div>
                        </template>
                        <template x-if="previewUrl">
                            <div class="space-y-3">
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-xl overflow-hidden">
                                    <img :src="previewUrl" class="w-full object-contain" style="max-height:220px" />
                                </div>
                                <div class="flex gap-2">
                                    <button @click="uploadCorrect()" :disabled="processing" class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-success-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-success-500 disabled:opacity-50 transition-all">
                                        <template x-if="!processing"><x-heroicon-s-document-check class="w-4 h-4" /></template>
                                        <template x-if="processing"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                                        <span x-text="processing ? '{{ __("app.scan.processing") }}' : '{{ __("app.scan.capture_correct") }}'"></span>
                                    </button>
                                    <button @click="clearFile()" class="inline-flex items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2.5 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        @endif

    <style>
        @keyframes scan-line {
            0%, 100% { top: 12px; opacity: 0.4; }
            50% { top: calc(100% - 12px); opacity: 1; }
        }
        .animate-scan-line {
            animation: scan-line 2.5s ease-in-out infinite;
            position: absolute;
        }
        [x-cloak] { display: none !important; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
    function scanApp() {
        return {
            stream: null, cameraOn: false, processing: false,
            qrFound: false, scanRAF: null, scanCount: 0,
            uploadOpen: false, previewUrl: null, dragOver: false,
            errorMsg: '',
            lastProcessedQr: null,
            correctedCount: 0,
            results: [],
            showToast: false,
            lastResult: null,
            toastProgress: 100,
            toastTimer: null,
            currentStudent: null,
            debugRaw: '',
            debugServer: '',
            debugFills: '',
            detectorKind: '',
            resultPanel: null,

            _onResult(r) {
                if (r && r.student_name) {
                    this.lastResult = r;
                    this.results.unshift(r);
                    this.correctedCount = this.results.length;
                    this._showResultToast();
                }
            },

            _onError(msg) {
                if (msg) {
                    this.errorMsg = msg;
                    setTimeout(() => { if (this.errorMsg === msg) this.errorMsg = ''; }, 5000);
                }
                this.lastProcessedQr = null;
            },

            _showResultToast() {
                if (this.toastTimer) clearInterval(this.toastTimer);
                this.showToast = true;
                this.toastProgress = 100;
                const total = 3000;
                const step = 50;
                this.toastTimer = setInterval(() => {
                    this.toastProgress -= (step / total) * 100;
                    if (this.toastProgress <= 0) {
                        this.showToast = false;
                        clearInterval(this.toastTimer);
                        this.toastTimer = null;
                    }
                }, step);
            },

            _resumeScanning() {
                if (this.cameraOn) {
                    this.qrFound = false;
                    setTimeout(() => { this._startScanning(); }, 800);
                }
            },

            async startCamera() {
                if (this.cameraOn) return;

                if (typeof jsQR === 'undefined') {
                    await new Promise(r => setTimeout(r, 1000));
                    if (typeof jsQR === 'undefined') {
                        alert('QR library failed to load. Refresh the page.');
                        return;
                    }
                }

                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1280, max: 1920 },
                            height: { ideal: 720, max: 1080 }
                        }
                    });
                    const video = this.$refs.video;
                    video.srcObject = this.stream;

                    await new Promise((resolve) => {
                        video.onloadedmetadata = () => { video.play().then(resolve); };
                    });
                    await new Promise((resolve) => {
                        const check = () => {
                            if (video.videoWidth > 0 && video.readyState >= 2) resolve();
                            else requestAnimationFrame(check);
                        };
                        check();
                    });

                    this.cameraOn = true;
                    this.qrFound = false;
                    this.scanCount = 0;
                    this._startScanning();
                } catch (e) {
                    console.error('Camera error:', e);
                    this.errorMsg = '{{ __("app.scan.camera_error") }}: ' + e.message;
                }
            },

            stopCamera() {
                this._stopScanning();
                if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                if (this.$refs.video) this.$refs.video.srcObject = null;
                this.cameraOn = false;
                this.qrFound = false;
                this.processing = false;
                this.currentStudent = null;
            },

            _startScanning() {
                this._stopScanning();
                const scan = async () => {
                    if (!this.cameraOn || this.processing) {
                        this.scanRAF = requestAnimationFrame(scan);
                        return;
                    }
                    await this._scanFrame();
                    setTimeout(() => {
                        if (this.cameraOn && !this.processing) {
                            this.scanRAF = requestAnimationFrame(scan);
                        }
                    }, 120);
                };
                this.scanRAF = requestAnimationFrame(scan);
            },

            _stopScanning() {
                if (this.scanRAF) { cancelAnimationFrame(this.scanRAF); this.scanRAF = null; }
            },

            async _detectQr(canvas, w, h, ctx) {
                // Native BarcodeDetector is far more reliable than jsQR when available
                if (this._bd === undefined) {
                    try {
                        this._bd = ('BarcodeDetector' in window)
                            ? new BarcodeDetector({ formats: ['qr_code'] })
                            : null;
                    } catch (e) { this._bd = null; }
                    this.detectorKind = this._bd ? 'native' : 'jsQR';
                }
                if (this._bd) {
                    try {
                        const codes = await this._bd.detect(canvas);
                        if (codes && codes.length && codes[0].rawValue) return codes[0].rawValue;
                    } catch (e) { /* fall through to jsQR */ }
                }
                const imageData = ctx.getImageData(0, 0, w, h);
                const qr = jsQR(imageData.data, w, h, { inversionAttempts: 'attemptBoth' });
                return (qr && qr.data) ? qr.data : null;
            },

            async _scanFrame() {
                const v = this.$refs.video;
                if (!v || v.videoWidth === 0 || v.readyState < 2) return;

                const c = this.$refs.scanCanvas;
                const scale = Math.min(1, 1600 / v.videoWidth);
                const w = Math.round(v.videoWidth * scale);
                const h = Math.round(v.videoHeight * scale);
                c.width = w;
                c.height = h;

                const ctx = c.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(v, 0, 0, w, h);

                this.scanCount++;

                const raw = await this._detectQr(c, w, h, ctx);

                if (raw) {
                    this.debugRaw = String(raw).slice(0, 40);
                    let parsed = this._parseQr(raw);

                    if (parsed && parsed.exam_id && parsed.student_id) {
                        const qrKey = parsed.exam_id + '_' + parsed.student_id;
                        if (this.lastProcessedQr === qrKey) {
                            return;
                        }

                        this.qrFound = true;
                        this._stopScanning();
                        this.lastProcessedQr = qrKey;
                        // Hold: let the camera settle/focus, then grab the
                        // sharpest full-res frame before correcting.
                        this.processing = true;
                        this.currentStudent = null;
                        this.debugServer = 'server: hold steady...';
                        this._captureBest(parsed);
                        return;
                    }
                }
                this.qrFound = false;
            },

            _frameSharpness(v) {
                const sc = this._sharpCanvas || (this._sharpCanvas = document.createElement('canvas'));
                const w = 240;
                const h = Math.max(1, Math.round(240 * v.videoHeight / v.videoWidth));
                sc.width = w; sc.height = h;
                const ctx = sc.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(v, 0, 0, w, h);
                const d = ctx.getImageData(0, 0, w, h).data;
                let sum = 0;
                for (let y = 0; y < h; y++) {
                    for (let x = 1; x < w; x++) {
                        const i = (y * w + x) * 4, j = (y * w + x - 1) * 4;
                        const l1 = d[i] * 0.3 + d[i + 1] * 0.59 + d[i + 2] * 0.11;
                        const l0 = d[j] * 0.3 + d[j + 1] * 0.59 + d[j + 2] * 0.11;
                        const dd = l1 - l0;
                        sum += dd * dd;
                    }
                }
                return sum / (w * h);
            },

            async _captureBest(parsed) {
                const v = this.$refs.video;
                const big = this.$refs.scanCanvas;
                // Brief settle (autofocus), then pick the sharpest of 3 frames.
                await new Promise(r => setTimeout(r, 150));
                let bestURL = null, bestScore = -1;
                for (let i = 0; i < 3; i++) {
                    if (!v || v.videoWidth === 0) break;
                    const s = this._frameSharpness(v);
                    if (s > bestScore) {
                        bestScore = s;
                        big.width = v.videoWidth;
                        big.height = v.videoHeight;
                        big.getContext('2d', { willReadFrequently: true }).drawImage(v, 0, 0);
                        bestURL = big.toDataURL('image/jpeg', 0.92);
                    }
                    if (i < 2) await new Promise(r => setTimeout(r, 80));
                }
                if (!bestURL) {
                    this._onError('{{ __("app.scan.invalid_image") }}');
                    this._finishScan();
                    return;
                }
                await this._sendScan(parsed, bestURL);
                this._finishScan();
            },

            _parseQr(raw) {
                if (raw.includes('|') && raw.startsWith('E')) {
                    const result = {};
                    raw.split('|').forEach(p => {
                        if (p.startsWith('E')) result.exam_id = parseInt(p.substring(1)) || p.substring(1);
                        else if (p.startsWith('S')) result.student_id = p.substring(1);
                        else if (p.startsWith('G')) result.stage_id = parseInt(p.substring(1)) || p.substring(1);
                        else if (p.startsWith('T')) result.study_type_id = parseInt(p.substring(1)) || p.substring(1);
                        else if (p.startsWith('P')) result.page = parseInt(p.substring(1));
                    });
                    return (result.exam_id && result.student_id) ? result : null;
                }
                try {
                    const j = JSON.parse(raw);
                    return (j && j.exam_id && j.student_id) ? j : null;
                } catch(e) { return null; }
            },

            async _autoCorrect(qrData, canvas) {
                this.processing = true;
                this.currentStudent = null;
                await this._sendScan(qrData, canvas);
                this._finishScan();
            },

            // Plain fetch to a normal Laravel route — no Livewire. The JSON
            // response comes straight back, which is reliable unlike Livewire
            // return values / events inside a Filament page.
            async _sendScan(qrData, canvas) {
                this.debugServer = 'server: sending...';
                try {
                // canvas may be a HTMLCanvasElement or already a data-URL string
                const imageB64 = (typeof canvas === 'string') ? canvas : canvas.toDataURL('image/jpeg', 0.85);
                    const resp = await fetch('{{ route("scan.process") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ qr: JSON.stringify(qrData), image: imageB64 }),
                    });
                    if (!resp.ok) {
                        this.debugServer = 'server: HTTP ' + resp.status;
                        this._onError('{{ __("app.scan.correction_failed") }} (' + resp.status + ')');
                        return;
                    }
                    const data = await resp.json();
                    if (data.ok) {
                        const dbg = data.debug || {};
                        this.debugServer = 'server: OK detected=' + (dbg.detected ?? '?') + ' markers=' + (dbg.has_markers ? 'yes' : 'NO');
                        // Show fill ratios for each question to help diagnose accuracy
                        if (dbg.fills) {
                            const f = dbg.fills;
                            this.debugFills = 'fills: ' + Object.keys(f).map(q =>
                                'Q' + (parseInt(q)+1) + ':' + f[q].top_fill + '(+' + f[q].gap + ')=' + (f[q].choice||'?')
                            ).join(' ');
                        }
                    } else {
                        this.debugServer = 'server: err=' + data.error;
                    }
                    if (!data.ok) {
                        this._onError(data.error || '{{ __("app.scan.correction_failed") }}');
                        return;
                    }
                    // Pause and show the result for the user to verify. Nothing
                    // is saved until they confirm.
                    this.resultPanel = {
                        token: data.token,
                        student: data.student,
                        result: data.result,
                        overlay: data.result.overlay || null,
                        saving: false,
                    };
                } catch (e) {
                    this.debugServer = 'server: FETCH ERR ' + (e && e.message ? e.message : String(e));
                    this._onError('{{ __("app.scan.service_unavailable") }}');
                }
            },

            _finishScan() {
                this.processing = false;
                this.currentStudent = null;
                // Stay paused while a result panel is shown; otherwise keep scanning.
                if (!this.resultPanel) this._resumeScanning();
            },

            async confirmResult() {
                if (!this.resultPanel || this.resultPanel.saving) return;
                this.resultPanel.saving = true;
                try {
                    const resp = await fetch('{{ route("scan.save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ token: this.resultPanel.token }),
                    });
                    const data = await resp.json();
                    if (!data.ok) {
                        this._onError(data.error || '{{ __("app.scan.correction_failed") }}');
                        this.resultPanel.saving = false;
                        return;
                    }
                    const r = this.resultPanel;
                    this._onResult({
                        student_name: r.student.name,
                        student_id: r.student.id,
                        exam_name: r.student.exam,
                        score: r.result.score,
                        total_mark: r.result.total_mark,
                        correct_count: r.result.correct_count,
                        wrong_count: r.result.wrong_count,
                        percentage: r.result.percentage,
                        status: r.result.status,
                        details: r.result.details,
                    });
                } catch (e) {
                    this._onError('{{ __("app.scan.service_unavailable") }}');
                    this.resultPanel.saving = false;
                    return;
                }
                this.resultPanel = null;
                if (this.cameraOn) this._resumeScanning();
            },

            rescanResult() {
                // Discard the preview and allow the same sheet to be scanned again.
                this.resultPanel = null;
                this.lastProcessedQr = null;
                if (this.cameraOn) this._resumeScanning();
            },

            closeResult() {
                this.rescanResult();
            },

            handleFile(e) { const f = e.target.files[0]; if (f) this._preview(f); },
            handleDrop(e) { const f = e.dataTransfer.files[0]; if (f && f.type.startsWith('image/')) this._preview(f); },
            _preview(f) { const r = new FileReader(); r.onload = e => this.previewUrl = e.target.result; r.readAsDataURL(f); },
            clearFile() { this.previewUrl = null; if (this.$refs.fileInput) this.$refs.fileInput.value = ''; },

            async uploadCorrect() {
                if (!this.previewUrl || this.processing) return;
                this.processing = true;
                this.currentStudent = null;
                try {
                    const img = new Image();
                    img.src = this.previewUrl;
                    await new Promise((res, rej) => { img.onload = res; img.onerror = () => rej(new Error('image load failed')); });
                    const c = this.$refs.scanCanvas;
                    c.width = img.naturalWidth;
                    c.height = img.naturalHeight;
                    const ctx = c.getContext('2d', { willReadFrequently: true });
                    ctx.drawImage(img, 0, 0);
                    const raw = await this._detectQr(c, c.width, c.height, ctx);
                    const parsed = raw ? this._parseQr(raw) : null;
                    if (!parsed || !parsed.exam_id || !parsed.student_id) {
                        this._onError('{{ __("app.scan.qr_not_found") }}');
                        return;
                    }
                    await this._sendScan(parsed, c);
                } catch (e) {
                    this.debugServer = 'server: UPLOAD ERR ' + (e && e.message ? e.message : String(e));
                    this._onError('{{ __("app.scan.correction_failed") }}');
                } finally {
                    this.processing = false;
                }
            }
        }
    }
    </script>
    </div>
</x-filament-panels::page>
