<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class EmployeeAccountController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_if($employee->user_id, 400, "Cet employé a déjà un accès.");

        $data = $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
        ]);

        $user = User::create([
            'name' => $employee->full_name,
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('employe');
        $employee->update(['user_id' => $user->id]);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', "Accès créé. Un lien pour définir le mot de passe a été envoyé à {$user->email}.");
    }

    public function resend(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($employee->user_id, 404);

        Password::sendResetLink(['email' => $employee->user->email]);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', "Lien de connexion renvoyé à {$employee->user->email}.");
    }
}
