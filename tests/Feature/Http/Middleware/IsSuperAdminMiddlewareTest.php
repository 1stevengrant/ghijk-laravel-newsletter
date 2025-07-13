<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\IsSuperAdminMiddleware;

describe('IsSuperAdminMiddleware', function () {
    test('allows access for authenticated super admin user', function () {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Success');
        };

        $response = $middleware->handle($request, $next);

        expect($response->getContent())->toBe('Success')
            ->and($response->getStatusCode())->toBe(200);
    });

    test('denies access for authenticated regular user', function () {
        $regularUser = User::factory()->create(['super_admin' => false]);
        $this->actingAs($regularUser);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Success');
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied. Super admin privileges required.');

        $middleware->handle($request, $next);
    });

    test('denies access for guest user', function () {
        $this->assertGuest();

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Success');
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied. Super admin privileges required.');

        $middleware->handle($request, $next);
    });

    test('denies access when user super_admin field is false by default', function () {
        // Since super_admin defaults to false, this tests the default behavior
        $user = User::factory()->create(); // super_admin will default to false
        $this->actingAs($user);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Success');
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied. Super admin privileges required.');

        $middleware->handle($request, $next);
    });

    test('passes request parameters through to next middleware', function () {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET', ['param' => 'value']);

        $next = function (Request $req) {
            return new Response($req->get('param'));
        };

        $response = $middleware->handle($request, $next);

        expect($response->getContent())->toBe('value');
    });

    test('handles POST requests correctly', function () {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'POST', ['data' => 'test-data']);

        $next = function (Request $req) {
            return new Response('POST:' . $req->get('data'));
        };

        $response = $middleware->handle($request, $next);

        expect($response->getContent())->toBe('POST:test-data');
    });

    test('handles JSON requests correctly', function () {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'POST', [], [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"key": "value"}'
        );

        $next = function (Request $req) {
            return response()->json(['received' => $req->json('key')]);
        };

        $response = $middleware->handle($request, $next);

        $decodedResponse = json_decode($response->getContent(), true);
        expect($decodedResponse['received'])->toBe('value');
    });

    test('throws 403 status code on access denial', function () {
        $regularUser = User::factory()->create(['super_admin' => false]);
        $this->actingAs($regularUser);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Success');
        };

        try {
            $middleware->handle($request, $next);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            expect($e->getStatusCode())->toBe(403)
                ->and($e->getMessage())->toBe('Access denied. Super admin privileges required.');
        }
    });

    test('works with different user attributes', function () {
        // Test with super admin having other attributes
        $superAdmin = User::factory()->create([
            'super_admin' => true,
            'name' => 'Super Admin User',
            'email' => 'admin@example.com',
        ]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Admin Access Granted');
        };

        $response = $middleware->handle($request, $next);

        expect($response->getContent())->toBe('Admin Access Granted');
    });

    test('evaluates super_admin as boolean correctly', function () {
        // Test with super_admin as integer 1 (truthy)
        $user1 = User::factory()->create(['super_admin' => 1]);
        $this->actingAs($user1);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('Access with 1');
        };

        $response = $middleware->handle($request, $next);
        expect($response->getContent())->toBe('Access with 1');

        // Test with super_admin as integer 0 (falsy)
        $user0 = User::factory()->create(['super_admin' => 0]);
        $this->actingAs($user0);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, $next);
    });

    test('does not interfere with response headers from next middleware', function () {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        $next = function () {
            return response('Success')->header('X-Custom-Header', 'test-value');
        };

        $response = $middleware->handle($request, $next);

        expect($response->headers->get('X-Custom-Header'))->toBe('test-value')
            ->and($response->getContent())->toBe('Success');
    });

    test('handles middleware chain correctly', function () {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $this->actingAs($superAdmin);

        $middleware = new IsSuperAdminMiddleware;
        $request = Request::create('/test', 'GET');

        // Simulate another middleware in the chain
        $next = function (Request $req) {
            // Another middleware that adds a header
            return response('Final Response')->header('X-Processed', 'true');
        };

        $response = $middleware->handle($request, $next);

        expect($response->getContent())->toBe('Final Response')
            ->and($response->headers->get('X-Processed'))->toBe('true');
    });
});
