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
        'hidden_in_review' => 'Similarity detected with a proposal that is submitted and still under review. Only its title and similarity score are shown; its details are hidden to protect the idea of its author.',
        'hidden_title'     => 'Hidden for Privacy',
        'hidden_domain'    => 'Active Confirmed Proposal',
        'unknown_project'  => 'Unknown Project',
    ],

    'team' => [
        'locked'             => 'This proposal can no longer be changed.',
        'self_invite'        => 'You cannot send a request to yourself.',
        'team_full'          => 'Your team is already complete (3 students).',
        'already_paired'     => 'You are already in another team.',
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

    'leave' => [
        'needs_request'      => 'Your team already has an active proposal. Send a leave request to the department instead.',
        'use_direct'         => 'Your team has no active proposal, so you can leave the team directly.',
        'alone'              => 'You are the only member of this team.',
        'left'               => 'You left the team.',
        'reason_required'    => 'Please write the reason for leaving the team.',
        'reason_max'         => 'The reason must not be longer than 1000 characters.',
        'already_requested'  => 'You already have a pending leave request for this team.',
        'requested'          => 'Your leave request was sent to the department.',
        'withdrawn'          => 'Your leave request was withdrawn.',
        'missing'            => 'This leave request is no longer available.',
        'already_decided'    => 'This leave request has already been decided.',
        'approved'           => 'Leave request approved. The proposal was cancelled and every team member must submit a new proposal.',
        'rejected'           => 'Leave request rejected. The team and the proposal stay as they are.',
    ],

    'supervisor' => [
        'name_required'        => 'The academic supervisor name is required to submit the proposal.',
        'name_max'             => 'The academic supervisor name cannot exceed 150 characters.',
    ],

];
