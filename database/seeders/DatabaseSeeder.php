<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Porperty;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\RentalRequest;
use App\Models\Contract;
use App\Models\Review;
use App\Models\SavedPost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User (or get existing)
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('amir777858@'),
                'role' => 'admin',
                'status' => 'active',
                'avatar' => null,
            ]
        );

        // Create Regular Users (or get existing)
        $user1 = User::firstOrCreate(
            ['email' => 'ahmed@example.com'],
            [
                'name' => 'Ahmed Ali',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'avatar' => null,
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'sara@example.com'],
            [
                'name' => 'Sara Mohamed',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'avatar' => null,
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'mohamed@example.com'],
            [
                'name' => 'Mohamed Hassan',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'avatar' => null,
            ]
        );

        // Create Properties (or get existing)
        $property1 = Porperty::firstOrCreate(['name' => 'Apartment']);
        $property2 = Porperty::firstOrCreate(['name' => 'House']);
        $property3 = Porperty::firstOrCreate(['name' => 'Villa']);
        $property4 = Porperty::firstOrCreate(['name' => 'Office']);
        $property5 = Porperty::firstOrCreate(['name' => 'Building']);
        $property6 = Porperty::firstOrCreate(['name' => 'Land']);

        // Create Posts
        $post1 = Post::create([
            'user_id' => $user1->id,
            'Title' => 'Modern Apartment in Downtown',
            'Price' => 1500,
            'Address' => '123 Main Street, Downtown',
            'Description' => 'Beautiful modern apartment with 2 bedrooms, fully furnished, close to all amenities.',
            'City' => 'Cairo',
            'Bedrooms' => 2,
            'Bathrooms' => 2,
            'Latitude' => '30.0444',
            'Longitude' => '31.2357',
            'Type' => 'rent',
            'porperty_id' => $property1->id,
            'Utilities_Policy' => 'owner',
            'Pet_Policy' => true,
            'Income_Policy' => '3x rent',
            'Total_Size' => 120,
            'Bus' => 5,
            'Resturant' => 3,
            'School' => 5,
            'status' => 'active',
        ]);

        $post2 = Post::create([
            'user_id' => $user2->id,
            'Title' => 'Luxury Villa with Garden',
            'Price' => 5000,
            'Address' => '456 Villa Road, Heliopolis',
            'Description' => 'Spacious villa with 4 bedrooms, large garden, and private pool.',
            'City' => 'Cairo',
            'Bedrooms' => 4,
            'Bathrooms' => 3,
            'Latitude' => '30.0875',
            'Longitude' => '31.3244',
            'Type' => 'rent',
            'porperty_id' => $property3->id,
            'Utilities_Policy' => 'tenant',
            'Pet_Policy' => true,
            'Income_Policy' => '4x rent',
            'Total_Size' => 300,
            'Bus' => 10,
            'Resturant' => 5,
            'School' => 15,
            'status' => 'active',
        ]);

        $post3 = Post::create([
            'user_id' => $user1->id,
            'Title' => 'Cozy House in Residential Area',
            'Price' => 2000,
            'Address' => '789 Residential Street, Maadi',
            'Description' => 'Comfortable house with 3 bedrooms, perfect for families.',
            'City' => 'Cairo',
            'Bedrooms' => 3,
            'Bathrooms' => 2,
            'Latitude' => '29.9608',
            'Longitude' => '31.2700',
            'Type' => 'rent',
            'porperty_id' => $property2->id,
            'Utilities_Policy' => 'owner',
            'Pet_Policy' => false,
            'Income_Policy' => '3x rent',
            'Total_Size' => 180,
            'Bus' => 5,
            'Resturant' => 2,
            'School' => 10,
            'status' => 'pending',
        ]);

        $post4 = Post::create([
            'user_id' => $user3->id,
            'Title' => 'Office Space in Business District',
            'Price' => 3000,
            'Address' => '321 Business Avenue, New Cairo',
            'Description' => 'Modern office space, fully equipped, ideal for startups.',
            'City' => 'Cairo',
            'Bedrooms' => 0,
            'Bathrooms' => 2,
            'Latitude' => '30.0131',
            'Longitude' => '31.6949',
            'Type' => 'rent',
            'porperty_id' => $property4->id,
            'Utilities_Policy' => 'share',
            'Pet_Policy' => false,
            'Income_Policy' => 'N/A',
            'Total_Size' => 200,
            'Bus' => 2,
            'Resturant' => 1,
            'School' => 0,
            'status' => 'active',
        ]);

        // Create Post Images (placeholder)
        PostImage::create([
            'post_id' => $post1->id,
            'image_url' => 'https://via.placeholder.com/800x600?text=Apartment+Image+1',
        ]);

        PostImage::create([
            'post_id' => $post2->id,
            'image_url' => 'https://via.placeholder.com/800x600?text=Villa+Image+1',
        ]);

        PostImage::create([
            'post_id' => $post3->id,
            'image_url' => 'https://via.placeholder.com/800x600?text=House+Image+1',
        ]);

        PostImage::create([
            'post_id' => $post4->id,
            'image_url' => 'https://via.placeholder.com/800x600?text=Office+Image+1',
        ]);

        // Create Rental Requests
        $rentalRequest1 = RentalRequest::create([
            'user_id' => $user2->id,
            'post_id' => $post1->id,
            'status' => 'pending',
            'message' => 'I am interested in renting this apartment. Please contact me.',
            'requested_at' => now()->subDays(2),
        ]);

        $rentalRequest2 = RentalRequest::create([
            'user_id' => $user3->id,
            'post_id' => $post2->id,
            'status' => 'approved',
            'message' => 'I would like to rent this villa for my family.',
            'requested_at' => now()->subDays(5),
        ]);

        $rentalRequest3 = RentalRequest::create([
            'user_id' => $user2->id,
            'post_id' => $post4->id,
            'status' => 'rejected',
            'message' => 'Looking for office space for my business.',
            'requested_at' => now()->subDays(1),
        ]);

        // Create Contracts
        Contract::create([
            'rental_request_id' => $rentalRequest2->id,
            'user_id' => $user3->id,
            'post_id' => $post2->id,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addMonths(12),
            'monthly_rent' => 5000,
            'status' => 'active',
            'terms' => '12 months contract. Security deposit required. Utilities separate.',
        ]);

        // Create Reviews
        Review::create([
            'user_id' => $user2->id,
            'post_id' => $post1->id,
            'rating' => 5,
            'comment' => 'Great apartment! Very clean and well-maintained. Highly recommended!',
            'status' => 'active',
        ]);

        Review::create([
            'user_id' => $user3->id,
            'post_id' => $post2->id,
            'rating' => 4,
            'comment' => 'Beautiful villa, spacious and comfortable. The garden is amazing!',
            'status' => 'active',
        ]);

        Review::create([
            'user_id' => $user1->id,
            'post_id' => $post4->id,
            'rating' => 5,
            'comment' => 'Perfect office space for our startup. Great location and facilities.',
            'status' => 'active',
        ]);

        // Create Saved Posts
        SavedPost::create([
            'user_id' => $user2->id,
            'post_id' => $post3->id,
        ]);

        SavedPost::create([
            'user_id' => $user3->id,
            'post_id' => $post1->id,
        ]);

        SavedPost::create([
            'user_id' => $user1->id,
            'post_id' => $post2->id,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin credentials: admin@gmail.com / amir777858@');
        $this->command->info('User credentials: ahmed@example.com / password123');
    }
}
