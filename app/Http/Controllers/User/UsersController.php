<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\DB;
use App\Domains\User\Actions\UserAction;
use App\Domains\User\DTO\UserDTO\CreateUserData;
use App\Domains\User\DTO\UserDTO\UpdateUserData;
use App\Domains\User\Gateways\UserGateway;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    /**
     * Get all users
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function index(Request $request)
    {
        // $gateway = new UserGateway;
        $filters = json_decode($request->get('filters'), true);

        $query = User::query();

        if (!empty($request->get('filters'))) {
            if (!empty($filters['role'])) {
                $query->where('role', $filters['role']);
            }
            if (!empty($filters['start_created_date'])) {
                $query->where('created_at', '>=', $filters['start_created_date']);
            }
            if (!empty($filters['end_created_date'])) {
                $query->where('created_at', '<=', $filters['end_created_date']);
            }
        }

        if (!empty($request->get('keywords'))) {
            $query->where('name', 'like', '%' . $request->get('keywords') . '%')
                ->orWhere('email', 'like', '%' . $request->get('keywords') . '%');
        }

        $users = $query->get();
        return $users;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|max:50',
            'role' => 'required|int'
        ]);

        $data = new CreateUserData([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        $user = (new UserAction)->create($data);

        return $user;
    }

    public function show(Request $request, $userId)
    {
        $user = User::find($userId);
        abort_unless((bool)$user, 404, 'user not found');
        return response()->json([$user]);
    }

    public function edit(Request $request, $userId)
    {
        $user = User::find($userId);
        abort_unless((bool)$user, 404, 'user not found');
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ]);
    }

    public function update(Request $request, $userId)
    {
        $validated = $request->validated([
            'name' => 'nullable|string',
            'password' => 'nullable|string',
            'email' => 'nullable|string',
            'role' => 'nullable|integer',
            'id' => 'required|integer'
        ]);
        $user = User::find($request->id);
        abort_unless((bool)$user, 404, 'User not found');
        if (!empty($request->name)) {
            $user->name = $request->name;
        }
        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }
        if (!empty($request->role)) {
            $user->role = $request->role;
        }
        if (!empty($request->email)) {
            $userEmail = DB::table('users')->where('email', $user->email)->first();
            if ($userEmail != $user->email) {
                $user->email = $request->email;
            } else {
                abort(406, 'this user email already exists');
            }
        }
        $user->save();
        // $data = new UpdateUserData([
        //     'name' => $request->name,
        //     'password' => $request->password,
        //     'email' => $request->email,
        //     'role' => (int)$request->role,
        //     'id' => (int)$userId
        // ]);

        // $user = (new UserAction)->update($data);
        // return $user;
    }

    public function delete($userId)
    {
        $user = User::find($userId);
        abort_unless((bool)$user, 404, 'user not found');
        $user->delete();
        return $user;
    }
}
