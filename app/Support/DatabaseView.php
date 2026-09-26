<?php

namespace App\Support;

use RuntimeException;

/**
 * Loads the SQL definition for a database view from `database/views`.
 *
 * View migrations pass the result straight to `DB::statement()`, which requires
 * a string. `file_get_contents()` returns `string|false`, so this helper throws
 * on a missing or unreadable file rather than letting a `false` reach the driver.
 */
class DatabaseView
{
    /**
     * Read the SQL body of a view definition file by its filename.
     *
     * @param  string  $fileName  Filename under `database/views`, e.g. `2026_09_19_150000_practice_run_history.sql`.
     */
    public static function sql(string $fileName): string
    {
        $path = database_path("views/{$fileName}");
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException("Unable to read database view definition at [{$path}].");
        }

        return $sql;
    }
}
