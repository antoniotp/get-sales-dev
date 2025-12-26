<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatAssignmentController extends Controller
{
    public function update(Conversation $conversation, Request $request): JsonResponse
    {
        // Authorize the action using the ConversationPolicy
        $this->authorize('assign', $conversation);

        // Validate request
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        // Check if the user to be assigned is in the same org
        if ($validated['user_id']) {
            $organization = $conversation->chatbotChannel->chatbot->organization;
            $agentToAssign = User::find($validated['user_id']);
            if (!$agentToAssign || !$agentToAssign->belongsToOrganization($organization)) {
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
