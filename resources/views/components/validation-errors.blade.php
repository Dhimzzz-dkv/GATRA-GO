{{--
    Ringkasan error validasi global.

    Ditampilkan saat Laravel mengembalikan $errors dari submit form. Tanpa ini,
    pengguna dikembalikan ke atas halaman panjang tanpa tahu mengapa submit gagal
    (10 dari 11 halaman sebelumnya tidak menampilkan error sama sekali).

    Menampilkan maksimum 6 baris pertama, dengan tautan "Lihat semua" bila lebih.
    Setiap pesan dapat diklik untuk menggulir ke field yang bermasalah.
--}}
@if ($errors->any())
    @php
        $errorCount = $errors->count();
        $maxVisible = 6;
        $messages = [];
        foreach ($errors->keys() as $key) {
            foreach ($errors->get($key) as $message) {
                $messages[] = ['key' => $key, 'message' => $message];
            }
        }
    @endphp

    <div id="validation-error-summary"
         class="flash-message mb-4 rounded-2xl border border-red-200 bg-red-50 shadow-sm"
         role="alert"
         aria-live="assertive"
         tabindex="-1"
         data-error-count="{{ $errorCount }}">
        <div class="flex items-start gap-3 p-4">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-state-danger" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>

            <div class="min-w-0 flex-1">
                <h3 class="text-sm font-bold text-red-900">
                    Data belum dapat disimpan
                    <span class="font-semibold text-red-700">({{ $errorCount }} masalah ditemukan)</span>
                </h3>
                <p class="mt-0.5 text-xs text-red-700">
                    Perbaiki poin berikut, lalu kirim ulang.
                </p>

                <ul class="mt-3 space-y-1.5">
                    @foreach (array_slice($messages, 0, $maxVisible) as $item)
                        <li>
                            <button type="button"
                                    onclick="focusValidationField(@js($item['key']), @js($item['message']))"
                                    class="group flex w-full items-start gap-2 rounded-lg px-2 py-1.5 text-left text-xs text-red-800 transition hover:bg-red-100">
                                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-400 group-hover:bg-red-600"></span>
                                <span class="flex-1">
                                    <span class="font-semibold">{{ $item['message'] }}</span>
                                    <span class="ml-1 text-xs text-state-danger group-hover:text-red-700">({!! \Illuminate\Support\Str::of($item['key'])->replace(['.', '_'], [' → ', ' '])->headline() !!})</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                @if ($errorCount > $maxVisible)
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs font-bold text-red-700 hover:text-red-900">
                            Lihat {{ $errorCount - $maxVisible }} masalah lainnya
                        </summary>
                        <ul class="mt-2 space-y-1.5">
                            @foreach (array_slice($messages, $maxVisible) as $item)
                                <li>
                                    <button type="button"
                                            onclick="focusValidationField(@js($item['key']), @js($item['message']))"
                                            class="group flex w-full items-start gap-2 rounded-lg px-2 py-1.5 text-left text-xs text-red-800 transition hover:bg-red-100">
                                        <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-400 group-hover:bg-red-600"></span>
                                        <span class="flex-1 font-semibold">{{ $item['message'] }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>

            <button type="button"
                    onclick="document.getElementById('validation-error-summary').remove()"
                    class="flex-shrink-0 text-state-danger transition hover:text-red-800"
                    aria-label="Tutup ringkasan error">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
        const validationErrors = @js($messages);

        function findValidationField(fieldKey) {
            const candidates = [
                `[name="${fieldKey}"]`,
                `[name="${fieldKey}[]"]`,
                `[name="${fieldKey.replace(/\.(\d+)\./g, '[$1][').replace(/\./g, '][')}"]`,
                `#${CSS.escape(fieldKey)}`,
            ];

            for (const selector of candidates) {
                try {
                    const target = document.querySelector(selector);
                    if (target && !(target instanceof HTMLInputElement && target.type === 'hidden')) {
                        return target;
                    }
                } catch (e) {
                    continue;
                }
            }

            return null;
        }

        function findGroupedValidationField(fieldKey) {
            const fieldPrefix = `${fieldKey}[`;
            return Array.from(document.querySelectorAll('input[name], select[name], textarea[name]'))
                .find(field => field.name.startsWith(fieldPrefix)
                    && !(field instanceof HTMLInputElement && field.type === 'hidden'));
        }

        function addInlineValidationError(target, message) {
            const parent = target.parentElement;
            const errorSelector = 'p.text-state-danger, p.text-state-danger, p.text-red-700, [data-server-field-error]';
            const describedError = (target.getAttribute('aria-describedby') || '')
                .split(/\s+/)
                .map(errorId => document.getElementById(errorId))
                .find(element => element?.matches(errorSelector));

            let error = describedError || (parent ? Array.from(parent.children).find(element =>
                element.matches(errorSelector)
            ) : null);

            if (!error) {
                if (!parent) return;

                error = document.createElement('p');
                error.dataset.serverFieldError = 'true';
                error.className = 'mt-1 text-xs font-medium text-red-700';
                error.textContent = message;
                target.insertAdjacentElement('afterend', error);
            }

            if (!error.id) {
                error.id = `server-error-${Math.random().toString(36).slice(2)}`;
            }

            const describedBy = (target.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
            if (!describedBy.includes(error.id)) {
                describedBy.push(error.id);
                target.setAttribute('aria-describedby', describedBy.join(' '));
            }
            target.setAttribute('aria-invalid', 'true');

            const clearServerFieldError = () => {
                const errorId = error.id;
                error.remove();

                document.querySelectorAll('[aria-describedby]').forEach(describedTarget => {
                    const describedBy = (describedTarget.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
                    if (!describedBy.includes(errorId)) return;

                    const remaining = describedBy.filter(id => id !== errorId);

                    if (remaining.length) describedTarget.setAttribute('aria-describedby', remaining.join(' '));
                    else describedTarget.removeAttribute('aria-describedby');

                    const hasLinkedError = remaining.some(remainingId =>
                        document.getElementById(remainingId)?.matches(errorSelector)
                    );
                    if (!hasLinkedError) describedTarget.removeAttribute('aria-invalid');
                });
            };
            target.addEventListener('input', clearServerFieldError, { once: true });
            target.addEventListener('change', clearServerFieldError, { once: true });
        }

        function initializeInlineValidationErrors() {
            validationErrors.forEach(({ key, message }) => {
                const target = findValidationField(key);
                if (target) addInlineValidationError(target, message);
            });

            const firstError = validationErrors[0];
            if (!firstError) return;

            requestAnimationFrame(() => {
                if (findValidationField(firstError.key) || findGroupedValidationField(firstError.key)) {
                    focusValidationField(firstError.key, firstError.message);
                    return;
                }

                document.getElementById('validation-error-summary')?.focus();
            });
        }

        document.addEventListener('DOMContentLoaded', initializeInlineValidationErrors, { once: true });

        /**
         * Gulir ke field yang bermasalah dan fokuskan.
         * Menangani nama field bertingkat seperti "lhps.0.nomor_lhp" atau
         * "temuans_kerugian.1.nilai_kerugian".
         */
        function focusValidationField(fieldKey, errorMessage = '') {
            const exactTarget = findValidationField(fieldKey);
            const target = exactTarget || findGroupedValidationField(fieldKey);

            if (!target) {
                return;
            }

            // Tampilkan error server di dekat field bila belum ada pesan Blade.
            if (errorMessage && exactTarget) {
                addInlineValidationError(target, errorMessage);
            }

            // Gunakan navigasi wizard agar status langkah dan progress bar tetap sinkron.
            const wizardStep = target.closest('.wizard-step');
            if (wizardStep && typeof showStep === 'function') {
                const stepNumber = Number(wizardStep.dataset.step);
                if (stepNumber) showStep(stepNumber);
            }

            // Buka modal atau accordion terdekat bila field berada di dalamnya
            const container = wizardStep ? null : target.closest('.hidden');
            if (container) {
                container.classList.remove('hidden');
                let parent = container.parentElement;
                while (parent) {
                    if (parent.classList.contains('hidden')) {
                        parent.classList.remove('hidden');
                    }
                    parent = parent.parentElement;
                }
            }

            // Buka <details> terdekat
            const details = target.closest('details');
            if (details) {
                details.open = true;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Fokus setelah animasi gulir selesai
            setTimeout(() => {
                try {
                    target.focus({ preventScroll: true });
                } catch (e) {
                    /* abaikan field yang tidak dapat difokuskan */
                }
                target.classList.add('ring-2', 'ring-red-300');
                setTimeout(() => target.classList.remove('ring-2', 'ring-red-300'), 2500);
            }, 350);
        }
    </script>
@endif
