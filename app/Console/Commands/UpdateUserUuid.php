<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateUserUuid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:user_uuid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::whereNull('uuid')->get();

        Log::info('Found ' . $users->count() . ' users to update');

        $users->each(function ($user) {
            $user->uuid = (string) Str::uuid();
            $user->save();

            Log::info('Updated user with id ' . $user->id);
        });

        $this->info('All users have been updated with a UUID');
    }
}
