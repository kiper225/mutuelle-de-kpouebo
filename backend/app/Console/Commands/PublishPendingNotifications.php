<?php

namespace App\Console\Commands;

use App\Models\Publication;
use App\Services\ContentNotifications;
use Illuminate\Console\Command;

class PublishPendingNotifications extends Command
{
    protected $signature = 'notifications:publish-pending';

    protected $description = 'Notifie les membres des publications programmées qui viennent d\'être mises en ligne';

    public function handle(ContentNotifications $content): int
    {
        Publication::published()->whereNull('notified_at')->get()->each(fn (Publication $p) => $content->publication($p));

        return self::SUCCESS;
    }
}