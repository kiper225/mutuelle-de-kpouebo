<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class WebPushTest extends Command
{
    /**
    * The name and signature of the console command.
    *
    * @var string
    */
    protected $signature = 'webpush:test';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Test web push notifications';
}