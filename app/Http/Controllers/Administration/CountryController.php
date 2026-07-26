<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        return view('administration.countries.index', [
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Country::create($this->validated($request));

        return redirect()->route('administration.countries.index')->with('status', 'Pays ajouté.');
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $country->update($this->validated($request, $country));

        return redirect()->route('administration.countries.index')->with('status', 'Pays mis à jour.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()->route('administration.countries.index')->with('status', 'Pays supprimé.');
    }

    private function validated(Request $request, ?Country $country = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('countries', 'name')->ignore($country?->id)],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
