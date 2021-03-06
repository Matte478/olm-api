<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FillPermissionsForUserExperiment extends Migration
{
    protected $roles;
    protected $permissions;
    protected $guardName = 'api';

    public function __construct()
    {
        //New permissions
        $this->permissions = [
            ['name' => 'user_experiment.show_own'],
            ['name' => 'user_experiment.show_all'],
            ['name' => 'user_experiment.create'],
            ['name' => 'user_experiment.update_own'],
            ['name' => 'user_experiment.update_all'],
            ['name' => 'user_experiment.delete_own'],
            ['name' => 'user_experiment.delete_all'],
            ['name' => 'user_experiment.restore_own'],
            ['name' => 'user_experiment.restore_all'],
        ];

        //Role should already exists
        $this->roles = [
            [
                'name' => 'Administrator',
                'permissions' => [
                    'user_experiment.show_own',
                    'user_experiment.show_all',
                    'user_experiment.create',
                    'user_experiment.update_own',
                    'user_experiment.update_all',
                    'user_experiment.delete_own',
                    'user_experiment.delete_all',
                    'user_experiment.restore_own',
                    'user_experiment.restore_all',
                ],
            ],
            [
                'name' => 'Teacher',
                'permissions' => [
                    'user_experiment.show_own',
                    'user_experiment.show_all',
                    'user_experiment.create',
                    'user_experiment.update_own',
                    'user_experiment.update_all',
                    'user_experiment.delete_own',
                    'user_experiment.delete_all',
                    'user_experiment.restore_own',
                    'user_experiment.restore_all',
                ],
            ],
            [
                'name' => 'Student',
                'permissions' => [
                    'user_experiment.show_own',
                    'user_experiment.create',
                    'user_experiment.update_own',
                    'user_experiment.delete_own',
                    'user_experiment.restore_own',
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
        app()['cache']->forget(config('permission.cache.key'));
        DB::transaction(function () {
            foreach ($this->permissions as $permission) {
                $permission = array_merge($permission, [
                    'guard_name' => $this->guardName,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
                if(!($permissionItem = DB::table('permissions')->where('name', '=', $permission['name'])->first())) {
                    DB::table('permissions')->insert($permission);
                }
            }

            foreach ($this->roles as $role) {
                $permissions = $role['permissions'];
                unset($role['permissions']);

                if($roleItem = DB::table('roles')->where('name', '=', $role['name'])->first()) {
                    $roleId = $roleItem->id;

                    $permissionItems = DB::table('permissions')->whereIn('name', $permissions)->get();
                    foreach ($permissionItems as $permissionItem) {
                        if(!($rolePermissionItem = DB::table('role_has_permissions')
                            ->where('permission_id', '=', $permissionItem->id)
                            ->where('role_id', '=', $roleId)->first())) {
                            DB::table('role_has_permissions')->insert(['permission_id' => $permissionItem->id, 'role_id' => $roleId]);
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        app()['cache']->forget(config('permission.cache.key'));
        DB::transaction(function () {
            foreach ($this->permissions as $permission) {
                if(!empty($permissionItem = DB::table('permissions')->where('name', '=', $permission['name'])->first())) {
                    DB::table('permissions')->where('id', '=', $permissionItem->id)->delete();
                    DB::table('model_has_permissions')->where('permission_id', '=', $permissionItem->id)->delete();
                }
            }
        });
    }
}
