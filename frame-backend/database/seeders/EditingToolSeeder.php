<?php

namespace Database\Seeders;

use App\Models\EditingTool;
use Illuminate\Database\Seeder;

class EditingToolSeeder extends Seeder
{
    public function run(): void
    {
        // Editable reference values for the editing tool catalog.
        foreach (['Adobe Premiere Pro', 'DaVinci Resolve', 'Final Cut Pro', 'Adobe After Effects', 'CapCut'] as $name) {
            EditingTool::withTrashed()->firstOrCreate(['name' => $name]);
        }
    }
}
