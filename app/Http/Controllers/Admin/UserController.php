<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies('permission_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        // Retrieve all roles
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $validatedData = $request->validate([
            'name' => 'required|regex:/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8', // Adjust the min length as per your requirements
            'role' => 'required|exists:roles,name',
        ]);

        $user = new User();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;

        if ($user->save()) {
            // Assign the role to the user using Spatie
            $user->assignRole($request->role);
            return redirect()->route('admin.users.index')->with('message', 'User created successfully!');
        }
        return redirect()->route('admin.users.index')->with('message', 'User create failed!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        abort_if(Gate::denies('permission_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $roles = Role::all();
        return view('admin.users.edit', compact('user','roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|regex:/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/|max:255',
            'email' => "required|email|unique:users,email,{$user->id}|max:255", // Ignore the current user's email
            'password' => 'nullable|string|min:8', // Password is optional during update
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        // Only update the password if provided
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        // Remove all current roles and assign the selected role
        $user->syncRoles($request->role);

        if ($user->save()) {
            return redirect()->route('admin.users.index')->with('message', 'User updated successfully!');
        }

        return redirect()->route('admin.users.index')->with('message', 'User update failed!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        abort_if(Gate::denies('permission_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($user->delete()) {
            return redirect()->route('admin.users.index')->with('message', 'User deleted successfully!');
        }

        return redirect()->route('admin.users.index')->with('message', 'User delete failed!');
    }

    public function banUnban($id, $status)
    {
        if (auth()->user()->hasRole('Admin')){
            $user = User::findOrFail($id);
            $user->status = $status;
            if ($user->save()){
                return redirect()->back()->with('message', 'User status updated successfully!');
            }
            return redirect()->back()->with('error', 'User status update fail!');
        }
        return redirect(Response::HTTP_FORBIDDEN, '403 Forbidden');
    }
}
