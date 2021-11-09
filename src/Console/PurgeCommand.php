<?php

namespace Sobhanatar\Idempotent\Console;

use Exception;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Builder;
use Sobhanatar\Idempotent\Contracts\Storage;
use Illuminate\Support\Facades\{DB, Schema};
use Sobhanatar\Idempotent\Exceptions\TableNotFoundException;

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
            {--entity= : The entity to remove its keys/hashes from the entities in config file.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the keys/hashes based on the ttl of an entity from database';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $entity = $this->option('entity');

        try {
            $this->info(sprintf('Purging %s idempotent keys/hashes', $entity));
            $this->validateEntity($entity);
            DB::table($this->getTable())
                ->where('entity', '=', $entity)
                ->where('expired_ut', '<', now()->unix())
                ->delete();
            $this->info(sprintf('`%s` expired idempotent keys/hashes has removed.', $entity));

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Validate input and return the config if available
     *
     * @param $entity
     * @return void
     * @throws TableNotFoundException
     */
    private function validateEntity($entity): void
    {
        $entities = collect(config('idempotent.entities'))->keys()->toArray();
        if (!$entity || !in_array($entity, $entities, true)) {
            throw new InvalidArgumentException(
                sprintf("The entity is missing or not exists. Use one of these entities: [%s]",
                    implode(', ', $entities)
                )
            );
        }

        $config = config('idempotent.entities.' . $entity);
        if ($config['storage'] !== Storage::MYSQL) {
            throw new InvalidArgumentException("The entity storage is not database");
        }

        if (!$this->schema->hasTable($this->getTable())) {
            throw new TableNotFoundException(
                sprintf("The idempotent table is missing. Make sure `%s` exists and reachable.", $this->getTable())
            );
        }
    }

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    private function getConnection(): string
    {
        return Storage::MYSQL;
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    private function getTable(): string
    {
        return config('idempotent.table');
    }
}
