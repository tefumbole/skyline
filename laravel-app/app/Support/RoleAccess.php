<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

/**
 * Permission checks for menus that were split onto their own names.
 */
class RoleAccess
{
    public static function allows($user, array $names)
    {
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasFullAdminAccess') && $user->hasFullAdminAccess()) {
            return true;
        }
        $role = Role::find($user->role_id);
        if (! $role) {
            return false;
        }
        foreach (self::expand($names) as $name) {
            try {
                if ($role->hasPermissionTo($name)) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        return false;
    }

    public static function expand(array $names)
    {
        $alias = [
            'whatsapp.view' => ['whatsapp_module', 'whatsapp-command'],
            'whatsapp.conversations' => ['whatsapp-conversations', 'whatsapp-people'],
            'whatsapp.calls' => ['whatsapp-calls'],
            'whatsapp.settings' => ['whatsapp-settings'],
            'whatsapp.leads' => ['whatsapp-leads'],
            'whatsapp.owner' => ['whatsapp-groups'],
            'whatsapp.attendance' => ['whatsapp-attendance'],
            'whatsapp.attendance.view' => ['whatsapp-attendance'],
            'whatsapp.ai' => ['whatsapp-assistant', 'whatsapp-brief'],
            'whatsapp.ai.manage' => ['whatsapp-assistant'],
            'whatsapp.ai.knowledge' => ['whatsapp-brief'],
        ];
        $out = [];
        foreach ($names as $name) {
            $out[] = $name;
            if (isset($alias[$name])) {
                foreach ($alias[$name] as $extra) {
                    $out[] = $extra;
                }
            }
        }

        return array_values(array_unique($out));
    }
}
