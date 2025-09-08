<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatAssignmentController extends Controller
{
    public function __construct(private Organization $organization)
    {
    }

    public function update(Conversation $conversation, Request $request): JsonResponse
    {
        // Authorization check for the current user
        $user = $request->user();
        $role = $user->getRoleInOrganization($this->organization);
        if (!$role || $role->level <= 40) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if the conversation belongs to the organization
        if ($conversation->chatbotChannel->chatbot->organization_id !== $this->organization->id) {
            return response()->json(['error' => 'Conversation not in organization'], 403);
        }

        // Validate request
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        // Check if the user to be assigned is in the same org
        if ($validated['user_id']) {
            $agentToAssign = User::find($validated['user_id']);
            if (!$agentToAssign || !$agentToAssign->belongsToOrganization($this->organization)) {
                return response()->json(['error' => 'User to be assigned not in organization'], 422);
            }
        }

        // Update conversation
        $conversation->update([
            'assigned_user_id' => $validated['user_id'],
        ]);

        $conversation->refresh();

        return response()->json([
            'success' => true,
            'assigned_user_id' => $conversation->assigned_user_id,
            'assigned_user_name' => $conversation->assignedUser?->name,
        ]);
    }
}
