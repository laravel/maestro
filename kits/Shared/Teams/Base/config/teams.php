<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team Invitations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the behavior of team invitations. You can control
    | whether notifications are sent when users join teams and set a default
    | expiration time for invitations.
    |
    */

    'invitations' => [

        /*
        |--------------------------------------------------------------------------
        | Notify on Join
        |--------------------------------------------------------------------------
        |
        | When set to true, the team owner will receive a notification whenever
        | a user accepts an invitation and joins the team. Set to false to
        | disable these notifications.
        |
        */

        'notify_on_join' => false,

        /*
        |--------------------------------------------------------------------------
        | Default Expiry
        |--------------------------------------------------------------------------
        |
        | This value determines the default expiration time for team invitations
        | in minutes. Set to null for invitations that never expire.
        |
        | Examples: 60 (1 hour), 1440 (1 day), 10080 (7 days), null
        |
        */

        'default_expiry' => null,

    ],

];
