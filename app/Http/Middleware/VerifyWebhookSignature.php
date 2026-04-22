<?php

namespace App\Http\Middleware;

use App\Mixins\Integrations\Freshdesk\SignatureVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function __construct(private SignatureVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Freshdesk-Signature')
            ?? $request->header('X-Webhook-Signature')
            ?? $request->header('X-Hub-Signature-256');

        if (!$this->verifier->verify($request->getContent(), $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
