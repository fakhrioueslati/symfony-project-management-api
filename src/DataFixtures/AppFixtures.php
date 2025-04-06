<?php 
namespace App\DataFixtures;

use App\Entity\Status;
use App\Entity\Role;
use App\Entity\Permission;
use App\Entity\RolePermission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Step 1: Add default statuses
        $statuses = [
            'To Do', 'In Progress', 'Completed', 'On Hold', 
            'Pending', 'Under Review', 'Blocked', 'Delayed', 
            'Reopened', 'Ready for Testing'
        ];

        foreach ($statuses as $statusName) {
            $status = new Status();
            $status->setName($statusName);
            $manager->persist($status);
        }

        // Step 2: Add default project roles (Owner, Manager, Member)
        $Roles = ['Owner', 'Manager', 'Member'];
        $RoleEntities = [];

        foreach ($Roles as $roleName) {
            $role = new Role();
            $role->setName($roleName);
            $manager->persist($role);
            $RoleEntities[$roleName] = $role;
        }

        // Step 3: Add default project and comment permissions
        $Permissions = [
            // Project permissions
            ['View Project', 'Project', 'Read'],
            ['Delete Project', 'Project', 'Delete'],
            ['Update Project', 'Project', 'Update'],
            ['Remove Member', 'Project', 'Remove'],

            // Task permissions
            ['Create Task', 'Task', 'Create'],
            ['Edit Task', 'Task', 'Update'],
            ['Delete Task', 'Task', 'Delete'],
            ['Assign Task', 'Task', 'Assign'],
            ['Comment Task', 'Task', 'Comment'],

            // Comment permissions
            ['Add Comment', 'Comment', 'Create'],
            ['View Comment', 'Comment', 'Read'],
            ['Update Comment', 'Comment', 'Update'],
            ['Delete Comment', 'Comment', 'Delete']
        ];

        $PermissionEntities = [];
        foreach ($Permissions as $permissionData) {
            $permission = new Permission();
            $permission->setName($permissionData[0]);
            $permission->setEntity($permissionData[1]);
            $permission->setAction($permissionData[2]);
            $manager->persist($permission);
            $PermissionEntities[$permissionData[0]] = $permission;
        }

        // Step 4: Assign project permissions to roles
        $this->assignPermissionsToRole($RoleEntities['Owner'], array_values($PermissionEntities), $manager);

        // Manager permissions (excluding category permissions)
        $managerPermissions = [
            $PermissionEntities['View Project'],
            $PermissionEntities['Delete Project'],
            $PermissionEntities['Update Project'],
            $PermissionEntities['Remove Member'],
            $PermissionEntities['Create Task'],
            $PermissionEntities['Edit Task'],
            $PermissionEntities['Delete Task'],
            $PermissionEntities['Assign Task'],
            $PermissionEntities['Comment Task'],
            $PermissionEntities['Add Comment'],
            $PermissionEntities['View Comment'],
            $PermissionEntities['Update Comment'],
            $PermissionEntities['Delete Comment']
        ];
        $this->assignPermissionsToRole($RoleEntities['Manager'], $managerPermissions, $manager);

        // Member permissions (viewing, commenting, and updating own comments)
        $memberPermissions = [
            $PermissionEntities['View Project'],
            $PermissionEntities['Comment Task'],
            $PermissionEntities['Add Comment'],
            $PermissionEntities['View Comment'],
            $PermissionEntities['Update Comment']  // Member can update their own comments
        ];
        $this->assignPermissionsToRole($RoleEntities['Member'], $memberPermissions, $manager);

        $manager->flush();
    }

    private function assignPermissionsToRole(Role $role, array $permissions, ObjectManager $manager)
    {
        foreach ($permissions as $permission) {
            $rolePermission = new RolePermission();
            $rolePermission->setRole($role);
            $rolePermission->setPermission($permission);
            $manager->persist($rolePermission);
        }
    }
}
