<?php

namespace Sobhanatar\Idempotent\Console;

use Illuminate\Console\Command;

class PurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idempotent:purge
            {--driver : The driver to remove its entities}
            {--entity : The entity to remove its hashes. for example users,devices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the hashes based on the ttl of entity';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // todo:
        //  1. check if database is used
        //  2. check if entity is correct
        //  3. remove entity expired hashes based on ttl

        if (!$this->option('entity')) {
            $this->error('Entity should be declared for purge');
            return;
        }

        $this->info('should be implemented');
    }
}
