<?php

use App\Http\Controllers\Action\WithdrawController;
use App\Models\User;
use App\Services\PalmPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('restricted roles cannot access cashout actions before any processing', function (string $role, string $action) {
    Auth::shouldReceive('user')->once()->andReturn(new User(['role' => $role]));
    $controller = new WithdrawController(Mockery::mock(PalmPayService::class));

    try {
        $controller->{$action}(Request::create('/withdraw', 'POST'));
        $this->fail('Expected cashout access to be forbidden.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
})->with(['personal', 'agent'])->with(['index', 'verifyAccount', 'processWithdrawal']);
