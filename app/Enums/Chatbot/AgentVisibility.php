<?php

namespace App\Enums\Chatbot;

enum AgentVisibility: string
{
    case ALL = 'all';
    case ASSIGNED_ONLY = 'assigned_only';
}