<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('holidays:sync')]
#[Description('Command description')]
class SyncHolidays extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
