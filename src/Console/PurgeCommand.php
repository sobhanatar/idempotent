<?php

namespace Sobhanatar\Idempotent\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Sobhanatar\Idempotent\Exceptions\EntityNotFoundException as IdempotentEntityNotFoundException;
use Sobhanatar\Idempotent\Exceptions\DatabaseTableNotFoundException as IdempotentDatabaseTableNotFoundException;

class PurgeCommand extends Command
{
    /**
     * The database schema.
     *
     * @var Builder
     */
    protected $schema;

    /**
     * Create a new migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->schema = Schema::connection($this->getConnection());
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idempotent:purge
            {--entity= : The entity to remove its hashes from the entities in config file.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the hashes based on the ttl of entity from database';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $entity = $this->option('entity');

        try {
            $this->validateInput($entity);
            $this->info('implement this');
            // todo:
            //  remove entity expired hashes based on ttl from database


        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * @param $entity
     * @return void
     * @throws Exception
     */
    private function validateInput($entity): void
    {
        $entities = collect(config('idempotent.entities'))->keys()->toArray();

        if (!$this->schema->hasTable($this->getTable())) {
            throw new IdempotentDatabaseTableNotFoundException(
                sprintf("The table is missing. Table name is `%s`", $this->getTable())
            );
        }

        if (!$entity || !in_array($entity, $entities, true)) {
            throw new IdempotentEntityNotFoundException(
                sprintf("The entity is missing or not correct. Use one of these: [%s]", implode(', ', $entities))
            );
        }
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection(): ?string
    {
        return config('idempotent.storage.database.connection');
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getTable(): string
    {
        return config('idempotent.storage.database.table');
    }
}
