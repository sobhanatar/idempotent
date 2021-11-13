<?php

namespace Sobhanatar\Idempotent\Commands;

use Exception;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Sobhanatar\Idempotent\Contracts\Storage;
use Illuminate\Support\Facades\{DB, Schema};
use Sobhanatar\Idempotent\Exceptions\TableNotFoundException;

class PurgeCommand extends Command
{
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
            $config = $this->validateEntity($entity);
            DB::connection($config['storage'])
                ->table($this->getTable())
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
     * @return array
     * @throws TableNotFoundException
     */
    private function validateEntity($entity): array
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

        if (!Schema::connection($config['storage'])->hasTable($this->getTable())) {
            throw new TableNotFoundException(
                sprintf("The idempotent table is missing. Make sure `%s` exists and reachable.", $this->getTable())
            );
        }

        return $config;
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
