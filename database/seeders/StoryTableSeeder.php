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
            'title' => 'shin',
            'story' => 'Lorem Ipsum',
            'user_id' => 1,
            'category_id' => 1,
        ]);
    }
}
