<?php

namespace Tests\Feature\OrderTable;

class OrderTableAuthorizationTest extends OrderTableTestCase
{
    public function test_unauthenticated_users_are_redirected_and_authenticated_users_without_permissions_are_forbidden(): void
    {
        $outlet = $this->outlet('Auth');
        $user = $this->userFor($outlet);

        $routes = [
            'order-table/outlet-settings',
            'order-table/dining-tables',
            'order-table/payment-methods',
        ];

        foreach ($routes as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_users_cannot_access_records_or_create_records_for_another_outlet(): void
    {
        $ownOutlet = $this->outlet('Owned');
        $otherOutlet = $this->outlet('Other');
        $user = $this->userFor($ownOutlet, [
            'update order-table/outlet-settings',
            'create order-table/dining-tables',
            'update order-table/dining-tables',
            'update order-table/payment-methods',
        ]);
        $setting = $this->setting($otherOutlet);
        $table = $this->diningTable($otherOutlet);
        $method = $this->paymentMethod($otherOutlet);

        $this->actingAs($user)
            ->get(route('order-table/outlet-settings/edit', $setting->id))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('order-table/dining-tables/edit', $table->id))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('order-table/payment-methods/edit', $method->id))
            ->assertNotFound();

        $this->actingAs($user)->postJson(route('order-table/dining-tables/store'), [
            'outlet_id' => $otherOutlet->id,
            'code' => 'X01',
            'name' => 'Foreign table',
        ])->assertForbidden();
    }
}
