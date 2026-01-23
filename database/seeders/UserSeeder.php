<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $faker = Faker::create();

        // Generate fake data for validation rules
        for ($i = 0; $i < 100; $i++) { // Change 10 to the number of users you want to create
            // Generate a random UUID
            $userId = Uuid::uuid4()->toString();

            // Generate a birthdate less than 7 years from the current date
            $birthdate = $faker->dateTimeBetween('-7 years', 'now')->format('Y-m-d');

            // Generate random gender
            $gender = $faker->randomElement(['male', 'female', 'others']);

            // Set the default profile photo based on gender
            $photoPath = '';
            switch ($gender) {
                case 'male':
                    $photoPath = 'storage/defaultProfile/male.png';
                    break;
                case 'female':
                    $photoPath = 'storage/defaultProfile/female.png';
                    break;
                default:
                    $photoPath = 'storage/defaultProfile/others.jpeg';
            }

            $newUser = User::create([
                'user_id' => $userId,
                'user_fname' => $faker->firstName(),
                'user_lname' => $faker->lastName(),
                'email' => $faker->unique()->safeEmail(),
                'profile_picture' => $photoPath,
                'password' => Hash::make('password123'), // Change to desired default password
                'gender' => $gender,
                'birthdate' => $birthdate,
                'identifier' => Str::uuid()->toString(),
            ]);
            $token = $newUser->createToken('user')->plainTextToken;
        }
    }
}
