<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChatbotIsSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route('chatbot')) {
            return $next($request);
        }

        return redirect()->route('chatbots.index')
            ->with('warning', 'Please select a chatbot to continue.');
    }
}
