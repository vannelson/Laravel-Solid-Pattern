<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserNameSplitSeeder extends Seeder
{
    /**
     * Split legacy full name into first/middle/last and populate new columns.
     * - Only runs if all target columns exist.
     * - Reads from legacy 'name' if present; otherwise tries to concatenate existing parts.
     */
    public function run(): void
    {
        $hasFirst = Schema::hasColumn('users', 'first_name');
        $hasMiddle = Schema::hasColumn('users', 'middle_name');
        $hasLast = Schema::hasColumn('users', 'last_name');
        if (!($hasFirst && $hasMiddle && $hasLast)) {
            return; // Columns not present yet
        }

        $hasName = Schema::hasColumn('users', 'name');

        DB::table('users')
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($hasName) {
                foreach ($rows as $u) {
                    $first = $u->first_name ?? '';
                    $last = $u->last_name ?? '';
                    // Skip if already populated
                    if (trim((string)$first) !== '' && trim((string)$last) !== '') {
                        continue;
                    }

                    $source = null;
                    if ($hasName && isset($u->name) && trim((string)$u->name) !== '') {
                        $source = $u->name;
                    } else {
                        // Fallback: if parts exist but incomplete, attempt to reconstruct
                        $joined = trim(implode(' ', array_filter([
                            $u->first_name ?? null,
                            $u->middle_name ?? null,
                            $u->last_name ?? null,
                        ], fn($v) => (string)$v !== '')));
                        if ($joined !== '') {
                            $source = $joined;
                        }
                    }

                    if ($source === null || trim($source) === '') {
                        continue;
                    }

                    [$f, $m, $l] = $this->splitFullName($source);

                    DB::table('users')->where('id', $u->id)->update([
                        'first_name' => $f,
                        'middle_name' => $m,
                        'last_name' => $l,
                    ]);
                }
            });
    }

    /**
     * Split a full name string into [first, middle, last].
     */
    protected function splitFullName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return ['', null, ''];
        }
        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return [$parts[0], null, ''];
        }
        if (count($parts) === 2) {
            return [$parts[0], null, $parts[1]];
        }
        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = implode(' ', $parts);
        return [$first, $middle, $last];
    }
}

