<?php

namespace App\Modules\Rbac\Support;

final class RbacCatalog
{
    public const ROLE_SUPER_ADMIN = 'Super Admin';

    public const ROLE_ADMINISTRATOR = 'Administrator';

    public const ROLE_TOUR_MANAGER = 'Tour Manager';

    public const ROLE_BOOKING_OFFICER = 'Booking Officer';

    public const ROLE_HOTEL_MANAGER = 'Hotel/Accommodation Manager';

    public const ROLE_FLEET_MANAGER = 'Fleet Manager';

    public const ROLE_NOMAD_MANAGER = 'Digital Nomad Manager';

    public const ROLE_FINANCE_OFFICER = 'Finance Officer';

    public const ROLE_CONTENT_SEO_MANAGER = 'Content/SEO Manager';

    public const ROLE_CUSTOMER_SUPPORT = 'Customer Support';

    public const ROLE_CUSTOMER = 'Customer';

    /**
     * @var list<string>
     */
    public const PROTECTED_SYSTEM_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_CUSTOMER,
    ];

    /**
     * Company-operated platform: hotel partners and open multi-vendor roles are strictly prohibited.
     *
     * @var list<string>
     */
    public const FORBIDDEN_ROLE_NAMES = [
        'hotel partner',
        'hotel_partner',
        'hotel-partner',
        'hotel owner',
        'hotel admin',
        'vendor',
        'marketplace vendor',
    ];

    /**
     * @return array<string, list<string>>
     */
    public static function permissionsByModule(): array
    {
        return [
            'Users & Invitations' => [
                'users.view',
                'users.create',
                'users.update',
                'users.deactivate',
                'users.delete',
                'users.invite',
            ],
            'Roles & Permissions' => [
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',
                'roles.assign',
                'permissions.view',
            ],
            'Audit Trail' => [
                'audit.view',
            ],
            'CMS & Site Builder' => [
                'cms.view',
                'cms.update',
                'cms.publish',
            ],
            'SEO & Redirects' => [
                'seo.view',
                'seo.update',
            ],
            'Destinations' => [
                'destinations.view',
                'destinations.manage',
            ],
            'Tours & Itineraries' => [
                'tours.view',
                'tours.create',
                'tours.update',
                'tours.publish',
                'tours.delete',
            ],
            'Tour-Only Hotels' => [
                'hotels.view',
                'hotels.manage',
            ],
            'Fleet & Drivers' => [
                'vehicles.view',
                'vehicles.manage',
                'vehicles.rates.manage',
            ],
            'Digital Nomads' => [
                'nomads.view',
                'nomads.manage',
            ],
            'Bookings' => [
                'bookings.view',
                'bookings.update',
                'bookings.confirm',
                'bookings.cancel',
            ],
            'Finance & Invoices' => [
                'invoices.view',
                'invoices.create',
                'invoices.issue',
                'invoices.void',
                'payments.record',
                'fx.manage',
            ],
            'Support & Communications' => [
                'inquiries.view',
                'inquiries.respond',
                'social.manage',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        $all = [];

        foreach (self::permissionsByModule() as $permissions) {
            array_push($all, ...$permissions);
        }

        return $all;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function initialRoles(): array
    {
        $all = self::allPermissions();

        return [
            self::ROLE_SUPER_ADMIN => $all,

            self::ROLE_ADMINISTRATOR => array_values(array_diff($all, [
                'roles.delete',
                'users.delete',
                'invoices.void',
            ])),

            self::ROLE_TOUR_MANAGER => [
                'destinations.view',
                'destinations.manage',
                'tours.view',
                'tours.create',
                'tours.update',
                'tours.publish',
                'tours.delete',
                'hotels.view',
                'vehicles.view',
                'nomads.view',
                'bookings.view',
                'seo.view',
            ],

            self::ROLE_BOOKING_OFFICER => [
                'destinations.view',
                'tours.view',
                'hotels.view',
                'vehicles.view',
                'nomads.view',
                'bookings.view',
                'bookings.update',
                'bookings.confirm',
                'bookings.cancel',
                'invoices.view',
                'invoices.create',
                'inquiries.view',
                'inquiries.respond',
            ],

            self::ROLE_HOTEL_MANAGER => [
                'destinations.view',
                'tours.view',
                'hotels.view',
                'hotels.manage',
                'bookings.view',
            ],

            self::ROLE_FLEET_MANAGER => [
                'destinations.view',
                'tours.view',
                'vehicles.view',
                'vehicles.manage',
                'vehicles.rates.manage',
                'bookings.view',
            ],

            self::ROLE_NOMAD_MANAGER => [
                'destinations.view',
                'tours.view',
                'nomads.view',
                'nomads.manage',
                'bookings.view',
            ],

            self::ROLE_FINANCE_OFFICER => [
                'bookings.view',
                'invoices.view',
                'invoices.create',
                'invoices.issue',
                'invoices.void',
                'payments.record',
                'fx.manage',
            ],

            self::ROLE_CONTENT_SEO_MANAGER => [
                'cms.view',
                'cms.update',
                'cms.publish',
                'seo.view',
                'seo.update',
                'destinations.view',
                'tours.view',
                'social.manage',
            ],

            self::ROLE_CUSTOMER_SUPPORT => [
                'destinations.view',
                'tours.view',
                'bookings.view',
                'inquiries.view',
                'inquiries.respond',
            ],

            self::ROLE_CUSTOMER => [],
        ];
    }

    public static function isForbiddenRoleName(string $name): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));

        return in_array($normalized, self::FORBIDDEN_ROLE_NAMES, true);
    }

    public static function isProtectedSystemRole(string $name): bool
    {
        return in_array($name, self::PROTECTED_SYSTEM_ROLES, true);
    }
}
