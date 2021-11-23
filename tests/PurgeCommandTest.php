<?php

namespace Sobhanatar\Idempotent\Tests;

use Sobhanatar\Idempotent\Models\Idempotent;
use Sobhanatar\Idempotent\StorageService as Storage;


class PurgeCommandTest extends TestCase
{
    /**
     * @test
     */
    public function assert_entity_should_be_present(): void
    {
        $entities = collect(config('idempotent.entities'))->keys()->toArray();
        $this
            ->artisan('idempotent:purge')
            ->expectsOutput(
                sprintf("The entity is missing or not exists. Use one of these entities: [%s]",
                    implode(', ', $entities))
            );
    }

    /**
     * @test
     */
    public function assert_entity_should_use_mysql_as_connection(): void
    {
        config()->set('idempotent.entities.news_post.storage', Storage::REDIS);
        $this->artisan('idempotent:purge --entity=news_post')
            ->expectsOutput('The entity storage is not database');
    }

    /**
     * @test
     */
    public function assert_table_should_exist(): void
    {
        config()->set('idempotent.entities.news_post.storage', Storage::MYSQL);
        $this
            ->artisan('idempotent:purge --entity=news_post')
            ->expectsOutput(
                sprintf('The idempotent table is missing. Make sure `%s` exists and reachable.',
                    config('idempotent.table'))
            );
    }

    /**
     * @test
     */
    public function assert_config_is_present(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        config()->set('idempotent.entities.news_post.storage', Storage::MYSQL);
        $this
            ->artisan('idempotent:purge --entity=news_post')
            ->expectsOutput(sprintf('`%s` expired idempotent keys/hashes has removed.', 'news_post'));
    }

    /**
     * @test
     */
    public function assert_data_will_delete_after_ttl(): void
    {
        $ttl = 1;
        config()->set('idempotent.entities.news_post.storage', Storage::MYSQL);
        config()->set('idempotent.entities.news_post.ttl', $ttl);
        $now = now();

        $this->loadMigrationsFrom(self::MIGRATION_PATH);

        Idempotent::factory()->connection('mysql')->create([
            'entity' => 'news_post',
            'hash' => 'some_hash',
            'status' => 'done',
            'response' => 'Hi',
            'code' => '200',
            'expired_ut' => $now->unix() + $ttl,
            'created_ut' => $now->unix(),
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_ut' => $now->unix(),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');

        sleep($ttl + 1);
        $this->artisan('idempotent:purge --entity=news_post')->execute();
        $this->assertDatabaseCount(config('idempotent.table'), 0, 'mysql');
    }

    /**
     * @test
     */
    public function assert_data_wont_delete_before_ttl(): void
    {
        $ttl = 15;
        config()->set('idempotent.entities.news_post.storage', Storage::MYSQL);
        config()->set('idempotent.entities.news_post.ttl', $ttl);
        $now = now();

        $this->loadMigrationsFrom(self::MIGRATION_PATH);

        Idempotent::factory()->connection('mysql')->create([
            'entity' => 'news_post',
            'hash' => 'some_hash',
            'status' => 'done',
            'response' => 'Hi',
            'code' => '200',
            'expired_ut' => $now->unix() + $ttl,
            'created_ut' => $now->unix(),
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_ut' => $now->unix(),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');

        $this->artisan('idempotent:purge --entity=news_post')->execute();
        $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');
    }

    /**
     * @test
     */
    public function assert_data_wont_delete_other_entities(): void
    {
        $ttl = 15;
        config()->set('idempotent.entities.news_post.storage', Storage::MYSQL);
        config()->set('idempotent.entities.news_post.ttl', $ttl);
        $now = now();

        $this->loadMigrationsFrom(self::MIGRATION_PATH);

        Idempotent::factory()->connection('mysql')->create([
            'entity' => 'news_post',
            'hash' => 'some_hash',
            'status' => 'done',
            'response' => 'Hi',
            'code' => '200',
            'expired_ut' => $now->unix() + $ttl,
            'created_ut' => $now->unix(),
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_ut' => $now->unix(),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');

        $this->artisan('idempotent:purge --entity=users_post')->execute();
        $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');
    }
}
