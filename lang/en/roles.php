<?php

return [
    'names' => [
        'system-admin' => 'System Admin',
        'social-researcher' => 'Social Researcher',
        'data-entry' => 'Data Entry',
        'manager' => 'Manager',
    ],
    'field_name' => 'Role Name',
    'field_permissions' => 'Permissions',
    'edit_title' => 'Edit Role',
    'edit_subtitle' => 'Update role details and permissions',
    'create_title' => 'Create New Role',
    'create_subtitle' => 'Create a new role and assign permissions',
    'index_title' => 'Roles',
    'index_subtitle' => 'Manage roles and permissions',
    'create_button' => 'New Role',
    'empty_title' => 'No Roles',
    'empty_description' => 'No roles found. Start by creating a new role.',
    'field_users_count' => 'Number of Users',
    'field_permissions_count' => 'Number of Permissions',
    'confirm_delete' => 'Are you sure you want to delete this role?',
    'results_count' => '{0} No results|{1} 1 result|[2,*] :count results',
    'messages' => [
        'cannot_rename_default' => 'Cannot rename default roles.',
        'cannot_delete_default' => 'Cannot delete default roles.',
        'cannot_delete_in_use' => 'Cannot delete a role that is in use by users.',
        'deleted' => 'Role deleted successfully.',
    ],
    'groups' => [
        'users' => 'Users',
        'roles' => 'Roles',
        'beneficiaries' => 'Beneficiaries',
        'aids' => 'Aids',
        'approvals' => 'Approvals',
        'disbursements' => 'Disbursements',
        'messages' => 'Messages',
        'notifications' => 'Notifications',
        'surveys' => 'Surveys',
        'settings' => 'Settings',
        'reports' => 'Reports',
    ],
    'permissions' => [
        'users' => [
            'view' => 'View Users',
            'create' => 'Create Users',
            'update' => 'Update Users',
            'delete' => 'Delete Users',
            'suspend' => 'Suspend and Activate Users',
        ],
        'roles' => [
            'view' => 'View Roles',
            'create' => 'Create Roles',
            'update' => 'Update Roles',
            'delete' => 'Delete Roles',
            'assign' => 'Assign Roles to Users',
        ],
        'beneficiaries' => [
            'view' => 'View Beneficiaries',
            'create' => 'Create Beneficiary Records',
            'update' => 'Update Beneficiary Data',
            'delete' => 'Delete Beneficiaries',
            'restore' => 'Restore Deleted Beneficiaries',
            'import' => 'Import Beneficiaries',
            'export' => 'Export Beneficiaries',
            'bank-data' => [
                'view' => 'View Bank Data',
                'manage' => 'Manage Bank Data',
            ],
        ],
        'aids' => [
            'view' => 'View Own Aids',
            'view-any' => 'View All Aids',
            'create' => 'Create Aids',
            'update' => 'Update Aids',
            'delete' => 'Delete Aids',
            'submit' => 'Submit Aids for Approval',
            'export' => 'Export Aids',
            'recurring' => [
                'manage' => 'Manage recurring aids',
            ],
        ],
        'approvals' => [
            'view' => 'View Approvals',
            'act' => 'Act on Approvals',
            'configure' => 'Configure Approval Workflows',
        ],
        'disbursements' => [
            'view' => 'View Disbursements',
            'manage' => 'Manage Disbursements',
            'confirm' => 'Confirm Aid Delivery',
        ],
        'messages' => [
            'broadcast' => 'Send Broadcast Messages',
        ],
        'notifications' => [
            'settings' => [
                'manage' => 'Manage Notification Settings',
            ],
        ],
        'surveys' => [
            'view' => 'View Surveys',
            'manage' => 'Manage Surveys',
            'results' => [
                'view' => 'View Survey Results',
            ],
        ],
        'settings' => [
            'view' => 'View Settings',
            'manage' => 'Manage Settings',
        ],
        'reports' => [
            'view' => 'View Reports',
            'export' => 'Export Reports',
        ],
    ],
];
