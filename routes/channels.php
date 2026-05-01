<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Adiciona autorização para membros do grupo ouvirem o canal
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    return $user->groups()->where('group_id', $groupId)->exists();
});