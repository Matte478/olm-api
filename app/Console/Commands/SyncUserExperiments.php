<?php

namespace App\Console\Commands;

use App\Actions\SyncUserExperiment;
use App\Models\UserExperiment;
use Illuminate\Console\Command;

class SyncUserExperiments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-experiments:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user experiments with experimental server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $unfinished = UserExperiment::unfinished(false)->get();

        foreach ($unfinished as $userExperiment) {
            app(SyncUserExperiment::class)->execute($userExperiment);
        }

        $this->info("User experiments sync completed.");
    }
}
