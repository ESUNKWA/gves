<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.profile.edit', [
            'employee' => $request->user()->employee,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        $data = $request->validate([
            'personal_email' => 'nullable|email|max:255',
            'personal_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => ['nullable', 'string', Rule::in(Country::options($employee->country))],
        ]);

        $employee->update($data);

        return redirect()->route('portal.profile.edit')->with('status', 'Profil mis à jour.');
    }
}
