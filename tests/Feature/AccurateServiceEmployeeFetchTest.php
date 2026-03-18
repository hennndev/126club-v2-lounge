<?php

use App\Services\AccurateService;
use Illuminate\Http\Request;

test('get employees fetches detail for each listed employee id', function () {
    $service = \Mockery::mock(AccurateService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $client = \Mockery::mock();

    $listResponse = \Mockery::mock();
    $listResponse->shouldReceive('failed')->andReturn(false);
    $listResponse->shouldReceive('json')->andReturn([
        'd' => [
            ['id' => 11, 'name' => 'List Employee 11', 'email' => 'list11@example.com'],
            ['id' => 22, 'name' => 'List Employee 22', 'email' => 'list22@example.com'],
        ],
    ]);

    $detailResponseOne = \Mockery::mock();
    $detailResponseOne->shouldReceive('failed')->andReturn(false);
    $detailResponseOne->shouldReceive('json')->andReturn([
        'd' => [
            'id' => 11,
            'name' => 'Detail Employee 11',
            'email' => 'detail11@example.com',
            'mobilePhone' => '081111111111',
        ],
    ]);

    $detailResponseTwo = \Mockery::mock();
    $detailResponseTwo->shouldReceive('failed')->andReturn(false);
    $detailResponseTwo->shouldReceive('json')->andReturn([
        'd' => [
            'id' => 22,
            'name' => 'Detail Employee 22',
            'email' => 'detail22@example.com',
            'mobilePhone' => '082222222222',
        ],
    ]);

    $client->shouldReceive('get')
        ->once()
        ->with('/api/employee/list.do', \Mockery::on(function (array $params): bool {
            return ($params['sp.page'] ?? null) === 1
                && ($params['sp.pageSize'] ?? null) === 200
                && ($params['sort'] ?? null) === 'name asc';
        }))
        ->andReturn($listResponse);

    $client->shouldReceive('get')->once()->with('/api/employee/detail.do', ['id' => 11])->andReturn($detailResponseOne);
    $client->shouldReceive('get')->once()->with('/api/employee/detail.do', ['id' => 22])->andReturn($detailResponseTwo);

    $service->shouldReceive('dataClient')->andReturn($client);

    $employees = $service->getEmployees(Request::create('/admin/users/sync-accurate', 'GET'), 200);

    expect($employees)->toHaveCount(2)
        ->and($employees->pluck('name')->all())->toBe(['Detail Employee 11', 'Detail Employee 22'])
        ->and($employees->pluck('mobilePhone')->all())->toBe(['081111111111', '082222222222']);
});

test('get employees uses list payload when detail request fails', function () {
    $service = \Mockery::mock(AccurateService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $client = \Mockery::mock();

    $listResponse = \Mockery::mock();
    $listResponse->shouldReceive('failed')->andReturn(false);
    $listResponse->shouldReceive('json')->andReturn([
        'd' => [
            ['id' => 33, 'name' => 'List Employee 33', 'email' => 'list33@example.com'],
        ],
    ]);

    $failedDetailResponse = \Mockery::mock();
    $failedDetailResponse->shouldReceive('failed')->andReturn(true);
    $failedDetailResponse->shouldReceive('json')->andReturn(['d' => null]);

    $client->shouldReceive('get')
        ->once()
        ->with('/api/employee/list.do', \Mockery::type('array'))
        ->andReturn($listResponse);

    $client->shouldReceive('get')->once()->with('/api/employee/detail.do', ['id' => 33])->andReturn($failedDetailResponse);

    $service->shouldReceive('dataClient')->andReturn($client);

    $employees = $service->getEmployees(Request::create('/admin/users/sync-accurate', 'GET'), 200);

    expect($employees)->toHaveCount(1)
        ->and($employees->first()['name'])->toBe('List Employee 33')
        ->and($employees->first()['email'])->toBe('list33@example.com');
});
