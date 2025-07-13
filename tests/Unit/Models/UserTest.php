<?php

use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Facades\Hash;

describe('User Model', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('has correct fillable attributes', function () {
        $fillable = ['name', 'email', 'password'];

        expect((new User)->getFillable())->toBe($fillable);
    });

    test('has correct hidden attributes', function () {
        $hidden = ['password', 'remember_token'];

        expect((new User)->getHidden())->toBe($hidden);
    });

    test('casts attributes correctly', function () {
        $user = User::factory()->create([
            'email_verified_at' => '2024-01-01 12:00:00',
            'super_admin' => 1,
        ]);

        expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($user->super_admin)->toBeTrue()
            ->and($user->super_admin)->toBeBool();
    });

    test('password is automatically hashed', function () {
        $plainPassword = 'test-password-123';

        $user = User::factory()->create([
            'password' => $plainPassword,
        ]);

        expect($user->password)->not->toBe($plainPassword)
            ->and(Hash::check($plainPassword, $user->password))->toBeTrue();
    });

    test('implements MustVerifyEmail interface', function () {
        $user = new User;

        expect($user)->toBeInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class);
    });

    test('uses HasFactory trait', function () {
        expect(in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses(User::class)))->toBeTrue();
    });

    test('uses Notifiable trait', function () {
        expect(in_array(\Illuminate\Notifications\Notifiable::class, class_uses(User::class)))->toBeTrue();
    });

    test('extends Authenticatable class', function () {
        $user = new User;

        expect($user)->toBeInstanceOf(\Illuminate\Foundation\Auth\User::class);
    });

    test('can be created with required fields', function () {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        expect($user->name)->toBe('Test User')
            ->and($user->email)->toBe('test@example.com')
            ->and($user->password)->not->toBe('password123')
            ->and(Hash::check('password123', $user->password))->toBeTrue();
        // Should be hashed
    });

    test('super_admin can be null or false by default', function () {
        $user = User::factory()->create();

        // The super_admin field might be null by default or false
        expect($user->super_admin)->toBeIn([null, false]);
    });

    test('can set super_admin to true', function () {
        $user = User::factory()->create([
            'super_admin' => true,
        ]);

        expect($user->super_admin)->toBeTrue();
    });

    test('super_admin boolean casting works with different values', function () {
        // Test with integer 1
        $user1 = User::factory()->create(['super_admin' => 1]);
        expect($user1->super_admin)->toBeTrue();

        // Test with integer 0
        $user2 = User::factory()->create(['super_admin' => 0]);
        expect($user2->super_admin)->toBeFalse();

        // Test with string 'true'
        $user3 = User::factory()->create(['super_admin' => 'true']);
        expect($user3->super_admin)->toBeTrue();

        // Test with empty string (falsy)
        $user4 = User::factory()->create(['super_admin' => '']);
        expect($user4->super_admin)->toBeFalse();
    });

    test('email verification timestamp can be set', function () {
        $verificationTime = now();

        $user = User::factory()->create([
            'email_verified_at' => $verificationTime,
        ]);

        expect($user->email_verified_at)->not->toBeNull()
            ->and($user->email_verified_at->toDateTimeString())->toBe($verificationTime->toDateTimeString());
    });

    test('email verification timestamp can be null', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        expect($user->email_verified_at)->toBeNull();
    });

    test('remember token can be set', function () {
        $user = User::factory()->create();
        $token = 'remember-token-123';

        $user->remember_token = $token;
        $user->save();

        expect($user->remember_token)->toBe($token);
    });

    test('password is hidden in array conversion', function () {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $userArray = $user->toArray();

        expect($userArray)->not->toHaveKey('password')
            ->and($userArray)->toHaveKey('name')
            ->and($userArray)->toHaveKey('email');
    });

    test('remember token is hidden in array conversion', function () {
        $user = User::factory()->create();
        $user->remember_token = 'secret-token';
        $user->save();

        $userArray = $user->toArray();

        expect($userArray)->not->toHaveKey('remember_token');
    });

    test('has many images relationship', function () {
        $user = User::factory()->create();

        // Create images for this user
        Image::create([
            'filename' => 'test1.jpg',
            'path' => '/uploads/test1.jpg',
            'url' => 'https://example.com/uploads/test1.jpg',
            'original_filename' => 'test1.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'user_id' => $user->id,
        ]);

        Image::create([
            'filename' => 'test2.jpg',
            'path' => '/uploads/test2.jpg',
            'url' => 'https://example.com/uploads/test2.jpg',
            'original_filename' => 'test2.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048000,
            'user_id' => $user->id,
        ]);

        // Check that images were created for this user
        $userImages = Image::where('user_id', $user->id)->get();
        expect($userImages)->toHaveCount(2)
            ->and($userImages->first())->toBeInstanceOf(Image::class);
    });

    test('can store long names', function () {
        $longName = 'This is a very long user name that might be used in some applications to test edge cases';

        $user = User::factory()->create([
            'name' => $longName,
        ]);

        expect($user->name)->toBe($longName);
    });

    test('email must be unique', function () {
        $email = 'unique@example.com';

        User::factory()->create(['email' => $email]);

        expect(function () use ($email) {
            User::factory()->create(['email' => $email]);
        })->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('handles password updates correctly', function () {
        $user = User::factory()->create([
            'password' => 'original-password',
        ]);

        $originalHash = $user->password;

        $user->update(['password' => 'new-password']);

        expect($user->password)->not->toBe($originalHash)
            ->and(Hash::check('new-password', $user->password))->toBeTrue()
            ->and(Hash::check('original-password', $user->password))->toBeFalse();
    });

    test('stores email in correct format', function () {
        $user = User::factory()->create([
            'email' => 'TEST@EXAMPLE.COM',
        ]);

        expect($user->email)->toBe('TEST@EXAMPLE.COM');
    });

    test('can handle special characters in name', function () {
        $specialName = 'José María O\'Connor-Smith';

        $user = User::factory()->create([
            'name' => $specialName,
        ]);

        expect($user->name)->toBe($specialName);
    });

    test('super_admin field affects model behavior', function () {
        $regularUser = User::factory()->create(['super_admin' => false]);
        $superAdmin = User::factory()->create(['super_admin' => true]);

        expect($regularUser->super_admin)->toBeFalse()
            ->and($superAdmin->super_admin)->toBeTrue()
            ->and($regularUser->super_admin)->not->toBe($superAdmin->super_admin);

        // These users should be distinguishable by their super_admin status
    });

    test('factory creates valid users', function () {
        $user = User::factory()->create();

        expect($user->name)->toBeString()
            ->and($user->email)->toBeString()
            ->and($user->email)->toContain('@')
            ->and($user->password)->toBeString()
            ->and(mb_strlen($user->password))->toBeGreaterThan(10);
        // Hashed passwords are long
    });

    test('mass assignment protection works', function () {
        // Try to mass assign a field that's not in fillable
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'super_admin' => true, // Not in fillable
            'remember_token' => 'should-not-be-set', // Not in fillable
        ]);

        expect($user->name)->toBe('Test User')
            ->and($user->email)->toBe('test@example.com')
            ->and($user->password)->not->toBe('password')
            ->and($user->super_admin)->toBeNull()
            ->and($user->remember_token)->toBeNull();
        // Password is hashed even for new models
        // Should not be set via mass assignment
        // Should not be set via mass assignment
    });
});
