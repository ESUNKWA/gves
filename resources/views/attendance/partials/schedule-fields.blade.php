@foreach ($dayLabels as $day => $label)
    @php
        $startField = "{$day}_start";
        $endField = "{$day}_end";
        $startValue = $schedule->{$startField} ? \Illuminate\Support\Carbon::parse($schedule->{$startField})->format('H:i') : '';
        $endValue = $schedule->{$endField} ? \Illuminate\Support\Carbon::parse($schedule->{$endField})->format('H:i') : '';
    @endphp
    <div class="grid grid-cols-3 items-end gap-2">
        <x-input-label :value="$label" class="col-span-3 sm:col-span-1" />
        <div>
            <x-text-input name="{{ $startField }}" type="time"
                value="{{ old($startField, $startValue) }}"
                class="w-full" />
            <x-input-error :messages="$showErrors ? $errors->get($startField) : []" class="mt-1" />
        </div>
        <div>
            <x-text-input name="{{ $endField }}" type="time"
                value="{{ old($endField, $endValue) }}"
                class="w-full" />
            <x-input-error :messages="$showErrors ? $errors->get($endField) : []" class="mt-1" />
        </div>
    </div>
@endforeach
