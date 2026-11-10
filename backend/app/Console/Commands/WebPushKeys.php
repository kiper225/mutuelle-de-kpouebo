<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class WebPushKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VAPID keys for web push notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $keys = VAPID::createVapidKeys();

        $this->info('Public Key: ' . $keys['publicKey']);
        $this->info('Private Key: ' . $keys['privateKey']);

        return 0;
    }
}
