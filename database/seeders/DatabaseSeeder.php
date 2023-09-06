<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ])->each(function ($user) {
            $user->ownedTeams()->save(\App\Models\Team::factory()->make([
                'user_id' => $user->id,
            ]));

            $user->ownedTeams->first()->users()->attach(
                $user->id, ['role' => 'admin']
            );
            $user->current_team_id = $user->ownedTeams->first()->id;
            $user->save();

            $token = $user->createToken('test-token')->plainTextToken;
            file_put_contents(storage_path('/app') . '/token.txt', $token);

            $user->ownedTeams->first()->lists()->save(\App\Models\Lists::factory()->make([
                'team_id' => $user->current_team_id,
                'name' => 'demo_list',
            ]));
        });
    }
}
