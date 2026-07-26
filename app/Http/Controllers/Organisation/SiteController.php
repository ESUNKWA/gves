<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(Request $request): View
    {
        $sites = Site::query()
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('organisation.sites.index', [
            'sites' => $sites,
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $data = $this->validated($request);

        Site::create($data);

        return redirect()->route('organisation.sites.index')->with('status', 'Site créé.');
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $data = $this->validated($request, $site);

        $site->update($data);

        return redirect()->route('organisation.sites.index')->with('status', 'Site mis à jour.');
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $site->delete();

        return redirect()->route('organisation.sites.index')->with('status', 'Site supprimé.');
    }

    private function validated(Request $request, ?Site $site = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('sites', 'code')->ignore($site?->id)],
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => ['nullable', 'string', Rule::in(Country::options($site?->country))],
            'phone' => 'nullable|string|max:50',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
