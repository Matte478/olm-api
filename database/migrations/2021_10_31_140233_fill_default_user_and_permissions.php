<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FillDefaultUserAndPermissions extends Migration
{
    protected $roles;
    protected $permissions;
    protected $guardName = 'api';
    protected $users;
    protected $email;
    protected $name;
    protected $password;

    /**
     * FillDefaultUserAndPermissions constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->email = config('default-admin-user.email');
        $this->name = config('default-admin-user.name');
        $this->password = config('default-admin-user.password');

        if(!$this->email || !$this->name || !$this->password) {
            throw new Exception('Invalid default admin user credentials.');
        }

        $adminPermissions = collect([
            // users
            'user.show',
            'user.update',
            'user.delete',

            // roles
            'role.show',
            'role.create',
            'role.update',
            'role.delete',
        ]);

        $teacherPermissions = collect([
            //
        ]);

        $studentPermissions = collect([
            //
        ]);

        $allPermissions = $adminPermissions
            ->merge($teacherPermissions)
            ->merge($studentPermissions)
            ->unique();

        $this->permissions = $allPermissions->map(function($permission) {
            return [
                'name' => $permission,
                'guard_name' => $this->guardName,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        })->toArray();

        $this->roles = [
            [
                'name' => 'Administrator',
                'guard_name' => $this->guardName,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'permissions' => $adminPermissions,
            ],
            [
                'name' => 'Teacher',
                'guard_name' => $this->guardName,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'permissions' => $teacherPermissions,
            ],
            [
                'name' => 'Student',
                'guard_name' => $this->guardName,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'permissions' => $studentPermissions,
            ],
        ];

        $this->users = [
            [
                'name' => 'Administrator',
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'roles' => [
                    'Administrator'
                ],
                'permissions' => [
                    //
                ],
            ],
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->permissions as $permission) {
                DB::table('permissions')->insert($permission);
            }

            foreach ($this->roles as $role) {
                $permissions = $role['permissions'];
                unset($role['permissions']);

                $roleId = DB::table('roles')->insertGetId($role);

                $permissionItems = DB::table('permissions')->whereIn('name', $permissions)->get();
                foreach ($permissionItems as $permissionItem) {
                    DB::table('role_has_permissions')->insert(['permission_id' => $permissionItem->id, 'role_id' => $roleId]);
                }
            }

            foreach ($this->users as $user) {
                $roles = $user['roles'];
                unset($user['roles']);
                $permissions = $user['permissions'];
                unset($user['permissions']);

                $userId = DB::table('users')->insertGetId($user);

                $roleItems = DB::table('roles')->whereIn('name', $roles)->get();
                foreach ($roleItems as $roleItem) {
                    DB::table('model_has_roles')->insert(['role_id' => $roleItem->id, 'model_id' => $userId, 'model_type' => 'App\Models\User']);
                }

                $permissionItems = DB::table('permissions')->whereIn('name', $permissions)->get();
                foreach ($permissionItems as $permissionItem) {
                    DB::table('model_has_permissions')->insert(['permission_id' => $permissionItem->id, 'model_id' => $userId, 'model_type' => 'App\Models\User']);
                }
            }
        });
        app()['cache']->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            foreach ($this->users as $user) {
                if(!empty($userItem = DB::table('users')->where('email', '=', $user['email'])->first())) {
                    DB::table('users')->where('id', '=', $userItem->id)->delete();
                    DB::table('model_has_permissions')->where('model_id', '=', $userItem->id)->where('model_type', '=', 'App\Models\User')->delete();
                    DB::table('model_has_roles')->where('model_id', '=', $userItem->id)->where('model_type', '=', 'App\Models\User')->delete();
                }
            }

            foreach ($this->roles as $role) {
                if(!empty($roleItem = DB::table('roles')->where('name', '=', $role['name'])->first())) {
                    DB::table('roles')->where('id', '=', $roleItem->id)->delete();
                    DB::table('model_has_roles')->where('role_id', '=', $roleItem->id)->delete();
                }
            }

            foreach ($this->permissions as $permission) {
                if(!empty($permissionItem = DB::table('permissions')->where('name', '=', $permission['name'])->first())) {
                    DB::table('permissions')->where('id', '=', $permissionItem->id)->delete();
                    DB::table('model_has_permissions')->where('permission_id', '=', $permissionItem->id)->delete();
                }
            }
        });
        app()['cache']->forget(config('permission.cache.key'));
    }
}
