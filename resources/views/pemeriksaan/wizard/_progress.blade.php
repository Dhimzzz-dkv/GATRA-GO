{{-- Wizard Progress Bar Component --}}
<div class="sticky top-20 z-30 rounded-2xl bg-white/95 backdrop-blur-md border border-gray-200/90 p-4 shadow-lg mb-6" role="group" aria-label="Progres pengisian pemeriksaan">
    <div class="flex items-center justify-between">
        @foreach([
            ['num' => 1, 'label' => 'SPT & Tim', 'icon' => '📋'],
            ['num' => 2, 'label' => 'Data LHP', 'icon' => '📄'],
            ['num' => 3, 'label' => 'Temuan', 'icon' => '🔍'],
            ['num' => 4, 'label' => 'Review', 'icon' => '✅']
        ] as $index => $step)
            <div class="flex items-center {{ $index < 3 ? 'flex-1' : '' }}">
                {{-- Step Circle --}}
                <div class="flex flex-col items-center">
                    <div class="wizard-step-circle flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition-all duration-300
                        @if($currentStep > $step['num']) bg-emerald-500 text-white border-2 border-emerald-500
                        @elseif($currentStep == $step['num']) bg-primary-600 text-white border-2 border-primary-600 ring-4 ring-primary-100
                        @else bg-gray-100 text-gray-500 border-2 border-gray-200
                        @endif"
                        data-step="{{ $step['num'] }}" aria-hidden="true">
                        @if($currentStep > $step['num'])
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            {{ $step['num'] }}
                        @endif
                    </div>
                    <span class="mt-2 text-xs font-bold transition-colors
                        @if($currentStep >= $step['num']) text-gray-900
                        @else text-gray-500
                        @endif">
                        <span class="sr-only">{{ $step['label'] }}</span>
                        <span class="hidden sm:inline" aria-hidden="true">{{ $step['icon'] }} {{ $step['label'] }}</span>
                        <span class="sm:hidden" aria-hidden="true">{{ $step['icon'] }}</span>
                    </span>
                </div>

                {{-- Connecting Line --}}
                @if($index < 3)
                    <div class="flex-1 h-0.5 mx-2 transition-colors duration-300
                        @if($currentStep > $step['num']) bg-emerald-500
                        @else bg-gray-200
                        @endif">
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Progress Bar --}}
    <div class="mt-4 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
        <div class="wizard-progress-bar bg-primary-700 h-1.5 rounded-full transition-all duration-500 ease-out" role="progressbar" aria-label="Langkah pengisian" aria-valuemin="1" aria-valuemax="4" aria-valuenow="{{ $currentStep }}"
             style="width: {{ (($currentStep - 1) / 3) * 100 }}%"></div>
    </div>

    {{-- Step Description --}}
    <div class="mt-3 text-center">
        <p id="wizard-step-description" class="text-xs text-gray-600 font-medium" aria-live="polite" aria-atomic="true">
            @if($currentStep == 1)
                Masukkan data Surat Perintah Tugas dan susunan tim pemeriksa
            @elseif($currentStep == 2)
                Input informasi Laporan Hasil Pemeriksaan (LHP) dan OPD target
            @elseif($currentStep == 3)
                Tambahkan temuan pemeriksaan (administrasi dan/atau kerugian daerah)
            @elseif($currentStep == 4)
                Tinjau ulang semua data sebelum menyimpan
            @endif
        </p>
    </div>
</div>
