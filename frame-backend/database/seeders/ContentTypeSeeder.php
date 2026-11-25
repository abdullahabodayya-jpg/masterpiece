<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContentType;

class ContentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contentTypes = [
            'Short-form Videos',
            'YouTube Videos',
            'Podcasts',
            'Commercials',
            'Documentaries',
            'Gaming Videos',
            'Educational Videos',
            'Corporate Videos',
            'Music Videos',
            'Cinematic Videos',
            'Motion Graphics',
            'Vlogs',
            'Event Highlights',
            'Product Videos',
            'Interviews',
        ];
        foreach ($contentTypes as $type) {
            ContentType::withTrashed()->firstOrCreate([
                'name' => $type,
            ]);
        }
    }
}
