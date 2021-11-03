<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceIdempotentTable extends Migration
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
        $this->schema = Schema::connection($this->getConnection());
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create($this->getTable(), function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('entity')->index()->comment('entity of the hash');
            $table->string('hash')->index()->comment('the hash of configured fields');
            $table->string('status', 191)->nullable(true)->comment('the status of operation, progress, done, failure');
            $table->text('response')->nullable(true)->comment('the complete body of response should be returned');
            $table->unsignedInteger('expired_ut')->comment('the expire time in unix timestamp');
            $table->unsignedInteger('created_ut')->comment('the creation time in unix timestamp');
            $table->timestamp('created_at')->useCurrent()->comment('the creation timestamp');

            $table->index(['entity', 'hash', 'expired_ut']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->dropIfExists($this->getTable());
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection(): ?string
    {
        return config('idempotent.database.connection');
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getTable(): string
    {
        return config('idempotent.database.table');
    }
}
