<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('stories')->insert([
            [
                'title' => 'story1',
                'story' => 'Lorem Ipsum',
                'user_id' => 1,
                'category_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'title' => 'story2',
                'story' => 'Lorem Ipsum',
                'user_id' => 1,
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => now(),
            ],
            [
                'title' => 'story3',
                'story' => 'Lorem Ipsum',
                'user_id' => 1,
                'category_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
