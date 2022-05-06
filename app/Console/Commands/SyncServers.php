<?php

namespace App\Console\Commands;

use App\Actions\SyncServer;
use App\Models\Server;
use Illuminate\Console\Command;

class SyncServers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'servers:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync experimental servers';

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
     * @return void
     */
    public function handle()
    {
        $servers = Server::all();

        foreach ($servers as $server) {
            try {
                app(SyncServer::class)->execute($server);
            } catch (\Throwable $exception) {}
        }

        $this->info("Experimental servers sync completed.");
    }
}
