<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Messages (English)
    |--------------------------------------------------------------------------
    |
    | رسائل النظام المنظّمة (إشعارات، تنبيهات، رسائل التشابه…).
    | تُستعمل عبر: __('messages.similarity.hidden')
    |
    */

    'similarity' => [
        'hidden'           => 'Potentially significant similarity detected with an approved project from the current academic year. The project details are hidden for privacy reasons. Please consider adjusting your project scope or selecting a different direction.',
        'running'          => 'Similarity analysis is running.',
        'hidden_title'     => 'Hidden for Privacy',
        'hidden_domain'    => 'Active Confirmed Proposal',
        'unknown_project'  => 'Unknown Project',
    ],

    'team' => [
        'locked'             => 'This proposal can no longer be changed.',
        'self_invite'        => 'You cannot send a request to yourself.',
        'team_full'          => 'Your team is already complete (2 students).',
        'already_paired'     => 'You already have a teammate.',
        'request_pending'    => 'You already have a pending team request. You can send a new one only if it is declined.',
        'invitee_paired'     => 'This student is already in a team.',
        'invitee_busy'       => 'This student already belongs to another active proposal.',
        'request_sent'       => 'Team request sent. Waiting for the student to accept it.',
        'request_missing'    => 'This team request is no longer available.',
        'accept_paired'      => 'You are already in a team, so you cannot accept this request.',
        'accept_busy'        => 'You already have another active submitted proposal.',
        'accepted'           => 'You joined the team.',
        'declined'           => 'Team request declined.',
        'cancelled'          => 'Team request cancelled.',
    ],

];
