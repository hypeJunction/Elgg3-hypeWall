<?php

use hypeJunction\Wall\Menus;
use hypeJunction\Wall\Notifications;
use hypeJunction\Wall\Permissions;
use hypeJunction\Wall\Post;

return [
    'plugin' => [
        'name' => 'hypeWall',
        'activate_on_install' => false,
    ],
    'bootstrap' => \hypeJunction\Wall\Bootstrap::class,

    'entities' => [
        [
            'type' => 'object',
            'subtype' => 'hjwall',
            'class' => Post::class,
            'capabilities' => [
                'searchable' => true,
            ],
        ],
    ],

    'settings' => [
        'url' => true,
        'photo' => true,
        'third_party_wall' => true,
        'character_limit' => 0,
    ],

    'user_settings' => [
        'river_access_id' => ACCESS_PRIVATE,
        'third_party_wall' => 1,
    ],

    'actions' => [
        'wall/status' => [],
        'wall/remove_tag' => [],
    ],

    'routes' => [
        'default:object:hjwall' => [
            'path' => '/wall/owner',
            'resource' => 'wall/owner',
            'middleware' => [
                \Elgg\Router\Middleware\Gatekeeper::class,
            ],
        ],
        'collection:object:hjwall' => [
            'path' => '/wall/{guid}',
            'resource' => 'wall/container',
        ],
        'collection:object:hjwall:owner' => [
            'path' => '/wall/owner/{username?}/{post_guids?}',
            'resource' => 'wall/owner',
        ],
        'collection:object:hjwall:group' => [
            'path' => '/wall/group/{guid}/{post_guids?}',
            'resource' => 'wall/container',
        ],
        'view:object:hjwall' => [
            'path' => '/wall/post/{guid}',
            'resource' => 'wall/view',
        ],
        'edit:object:hjwall' => [
            'path' => '/wall/edit/{guid}',
            'resource' => 'wall/edit',
        ],
    ],

    'view_extensions' => [
        'elgg.css' => [
            'framework/wall/stylesheet.css' => [],
        ],
        'page/layouts/elements/content' => [
            'page/components/wall' => ['priority' => 100],
        ],
    ],

    'views' => [
        'default' => [
            'output/wall/url' => ['ajax' => true],
        ],
    ],

    'events' => [
        'publish' => [
            'object' => [
                Notifications::class . '::sendCustomNotifications' => [],
            ],
        ],
    ],

    'notifications' => [
        'object' => [
            'hjwall' => [
                'publish' => true,
            ],
        ],
    ],

    'hooks' => [
        'prepare' => [
            'notification:publish:object:hjwall' => [
                Notifications::class . '::formatMessage' => [],
            ],
        ],
        'likes:is_likable' => [
            'object:hjwall' => [
                'Elgg\\Values::getTrue' => [],
            ],
        ],
        'container_permissions_check' => [
            'object' => [
                Permissions::class . '::containerPermissionsCheck' => [],
            ],
        ],
        'register' => [
            'menu:river' => [Menus::class . '::riverMenuSetup' => []],
            'menu:entity' => [Menus::class . '::entityMenuSetup' => []],
            'menu:owner_block' => [Menus::class . '::ownerBlockMenuSetup' => []],
            'menu:user_hover' => [Menus::class . '::userHoverMenuSetup' => []],
            'menu:scraper:card' => [Menus::class . '::setupCardMenu' => []],
            'menu:wall:quick_links' => [Menus::class . '::setupQuickLinks' => []],
        ],
        'aliases' => [
            'graph' => [Post::class . '::getGraphAlias' => []],
        ],
        'graph:properties' => [
            'object:hjwall' => [Post::class . '::getPostProperties' => []],
        ],
    ],

    'widgets' => [
        'wall' => [
            'context' => ['profile', 'dashboard'],
        ],
    ],

    'group_tools' => [
        'wall' => [
            'label' => 'wall:groups:enable',
            'default_on' => false,
        ],
    ],
];
