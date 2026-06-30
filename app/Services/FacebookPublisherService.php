<?php

namespace App\Services;

use App\Models\ScheduledSocialPost;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPublisherService
{
    /**
     * @return array{success: bool, post_id: string|null, error: string|null}
     */
    public function publishPost(SocialAccount $account, string $content): array
    {
        $pageId = $account->metadata['page_id'] ?? null;
        $pageToken = $account->metadata['page_access_token'] ?? $account->access_token;

        if (!$pageId || !$pageToken) {
            return [
                'success' => false,
                'post_id' => null,
                'error' => 'Facebook Page not linked. Disconnect and reconnect Facebook to grant Page access.',
            ];
        }

        $response = Http::timeout(30)
            ->post("https://graph.facebook.com/v18.0/{$pageId}/feed", [
                'message' => $content,
                'access_token' => $pageToken,
            ]);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? $response->body();
            Log::warning("Facebook publish failed: {$err}");

            return [
                'success' => false,
                'post_id' => null,
                'error' => is_string($err) ? $err : 'Publish failed',
            ];
        }

        return [
            'success' => true,
            'post_id' => (string) ($response->json('id') ?? ''),
            'error' => null,
        ];
    }

    public function publishScheduledPost(ScheduledSocialPost $post): bool
    {
        $post->loadMissing('socialAccount');
        $account = $post->socialAccount;

        if (!$account || $account->platform !== 'facebook') {
            $post->update([
                'status' => ScheduledSocialPost::STATUS_FAILED,
                'error_message' => 'Only Facebook publishing is supported currently.',
            ]);

            return false;
        }

        $result = $this->publishPost($account, $post->content);

        if ($result['success']) {
            $post->update([
                'status' => ScheduledSocialPost::STATUS_PUBLISHED,
                'external_id' => $result['post_id'],
                'published_at' => now(),
                'error_message' => null,
            ]);

            return true;
        }

        $post->update([
            'status' => ScheduledSocialPost::STATUS_FAILED,
            'error_message' => $result['error'],
        ]);

        return false;
    }

    /**
     * Resolve page token after OAuth — stores first manageable Page.
     */
    public function attachPageToAccount(SocialAccount $account, string $userAccessToken): void
    {
        $response = Http::withToken($userAccessToken)
            ->get('https://graph.facebook.com/v18.0/me/accounts', [
                'fields' => 'id,name,access_token,tasks,category',
                'limit' => 25,
            ]);

        if (!$response->successful()) {
            Log::warning('Facebook pages fetch failed: ' . ($response->json('error.message') ?? $response->body()));

            return;
        }

        $pages = $response->json('data') ?? [];
        if ($pages === []) {
            return;
        }

        $page = $pages[0];
        $metadata = $account->metadata ?? [];
        $metadata['page_id'] = $page['id'] ?? null;
        $metadata['page_name'] = $page['name'] ?? null;
        $metadata['page_access_token'] = $page['access_token'] ?? null;
        $metadata['page_tasks'] = $page['tasks'] ?? [];

        $account->update([
            'account_name' => $page['name'] ?? $account->account_name,
            'account_id' => (string) ($page['id'] ?? $account->account_id),
            'metadata' => $metadata,
        ]);
    }
}
