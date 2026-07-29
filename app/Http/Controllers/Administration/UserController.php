<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        return view('administration.users.index', [
            'users' => User::with('employee')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
            'roleLabels' => $this->roleLabels(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403, 'Vous ne pouvez pas modifier votre propre rôle.');

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in(Role::pluck('name'))],
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('administration.users.index')->with('status', 'Rôle mis à jour.');
    }

    private function roleLabels(): array
    {
        return [
            'super-admin' => 'Super administrateur',
            'rh-admin' => 'Administrateur RH',
            'manager' => 'Manager',
            'direction' => 'Direction',
            'employe' => 'Employé',
        ];
    }
}
