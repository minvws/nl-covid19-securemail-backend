<?php

namespace MinVWS\MessagingApp\Tests\Feature\Service;

use function implode;
use function sprintf;

trait AssertionTrait
{
    public function assertDatabaseHas(string $table, array $attributes): array
    {
        return $this->assertDatabaseCount($table, $attributes, 1);
    }

    public function assertDatabaseCount(string $table, array $attributes, int $count): array
    {
        $where = [];

        foreach ($attributes as $field => $value) {
            if ($value === null) {
                $where[] = sprintf("`%s` IS NULL", $field);
            } else {
                $where[] = sprintf("`%s` = '%s'", $field, $value);
            }
        }

        $databaseResults = $this->databaseConnection->select(
            sprintf("SELECT * FROM `%s` WHERE %s", $table, implode(' AND ', $where))
        );

        $this->assertCount($count, $databaseResults);

        return $databaseResults;
    }
}
