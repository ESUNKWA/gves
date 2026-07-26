@props(['selected' => null])

<x-select {{ $attributes }}>
    <option value="">—</option>
    @foreach (\App\Models\Country::options($selected) as $country)
        <option value="{{ $country }}" @selected($country === $selected)>{{ $country }}</option>
    @endforeach
</x-select>
