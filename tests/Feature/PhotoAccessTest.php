<?php

use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a guest cannot load a volunteer photo through the app routes', function () {
    Storage::fake('local');
    $volunteer = User::factory()->create([
        'photo_path' => UploadedFile::fake()->image('photo.jpg')->store('photos', 'local'),
    ]);
    $volunteer->editions()->attach(Edition::factory()->create(['status' => 'active'])->id);

    $this->get(route('admin.volunteers.photo', $volunteer))->assertRedirect(route('login'));
    $this->get(route('profile.photo'))->assertRedirect(route('login'));
});

test('photos on the private disk have no public URL', function () {
    Storage::fake('local');
    $photoPath = UploadedFile::fake()->image('photo.jpg')->store('photos', 'local');

    $this->get('/storage/'.$photoPath)->assertNotFound();
    $this->get('/'.$photoPath)->assertNotFound();
});
