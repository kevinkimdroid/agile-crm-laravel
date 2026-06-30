<?php

namespace App\Console\Commands;

use App\Models\ScheduledSocialPost;
use App\Services\FacebookPublisherService;
use Illuminate\Console\Command;

class PublishScheduledSocialPosts extends Command
{
    protected $signature = 'social:publish-scheduled {--limit=20 : Max posts to publish per run}';

    protected $description = 'Publish due scheduled social media posts (Facebook Page feed)';

    public function handle(FacebookPublisherService $publisher): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));

        $posts = ScheduledSocialPost::with('socialAccount')
            ->due()
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No due scheduled posts.');

            return self::SUCCESS;
        }

        $published = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($publisher->publishScheduledPost($post)) {
                $published++;
                $this->line("Published #{$post->id} ({$post->platform})");
            } else {
                $failed++;
                $this->warn("Failed #{$post->id}: " . ($post->fresh()->error_message ?? 'unknown'));
            }
        }

        $this->info("Done. Published: {$published}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
