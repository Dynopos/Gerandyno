<?php

namespace Tests\Unit\Services\SalesPlay;

use App\Services\SalesPlay\Exceptions\SalesPlayApiException;
use App\Services\SalesPlay\SalesPlayApiClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fixture below is a real response captured from the live SalesPlay
 * Developer API (https://api.salesplaypos.com/v1.0/receipts) during
 * integration testing — not a guess at the response shape.
 */
class SalesPlayApiClientTest extends TestCase
{
    private function realReceiptsFixture(): array
    {
        return [
            'receipts' => [
                [
                    'receipt_number' => '10-0003',
                    'receipt_type' => 'SALE',
                    'refund_for' => null,
                    'receipt_date_time' => '2026-07-04 09:07:01',
                    'total_money' => 21.25,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_charge' => 0,
                    'order_type' => 'Dine in',
                    'receipt_delete_status' => false,
                    'line_products' => [
                        [
                            'product_id' => 'TE5yRUJUS1ZSRms0UEhqamd3eDNGUT09',
                            'product_name' => '*Brisket',
                            'quantity' => 1,
                            'price' => 7.5,
                            'total_discount' => 0,
                            'total_money' => 7.5,
                        ],
                        [
                            'product_id' => 'S1hOQTYyWXRmZ1VnZXFNTkR0U3FMQT09',
                            'product_name' => '*Blade',
                            'quantity' => 1,
                            'price' => 7.5,
                            'total_discount' => 0,
                            'total_money' => 7.5,
                        ],
                        [
                            'product_id' => 'VFRaQ3pieHU0cGY3MFNLdHFtUlVLZz09',
                            'product_name' => '*Ayam Sekor',
                            'quantity' => 1,
                            'price' => 6.25,
                            'total_discount' => 0,
                            'total_money' => 6.25,
                        ],
                    ],
                    'payments' => [
                        [
                            'payment_type' => 'Cash',
                            'money_amount' => 21.25,
                            'paid_at' => '2026-07-04 09:07:01',
                        ],
                    ],
                ],
                [
                    'receipt_number' => 'deleted-1',
                    'receipt_date_time' => '2026-07-04 09:10:00',
                    'total_money' => 5,
                    'receipt_delete_status' => true,
                    'line_products' => [],
                    'payments' => [],
                ],
            ],
            'cursor' => 'NSw1',
        ];
    }

    public function test_it_maps_a_real_salesplay_response_into_receipt_dtos(): void
    {
        Http::fake([
            'api.salesplaypos.com/*' => Http::response($this->realReceiptsFixture(), 200),
        ]);

        $client = new SalesPlayApiClient(baseUrl: 'https://api.salesplaypos.com/v1.0', timeout: 30);

        $page = $client->fetchReceipts(
            shopId: 'shop-abc',
            apiToken: 'test-token',
            since: Carbon::parse('2026-07-01 00:00:00'),
            cursor: null,
        );

        // The soft-deleted receipt is excluded, leaving only the real sale.
        $this->assertCount(1, $page->items);

        $receipt = $page->items[0];
        $this->assertSame('10-0003', $receipt->salesplayReceiptId);
        $this->assertSame('10-0003', $receipt->receiptNumber);
        $this->assertTrue($receipt->transactionDate->equalTo(Carbon::parse('2026-07-04 09:07:01')));
        $this->assertSame(21.25, $receipt->subtotal);
        $this->assertSame(0.0, $receipt->discount);
        $this->assertSame(0.0, $receipt->tax);
        $this->assertSame(21.25, $receipt->total);

        $this->assertCount(3, $receipt->items);
        $this->assertSame('*Brisket', $receipt->items[0]->productName);
        $this->assertSame('TE5yRUJUS1ZSRms0UEhqamd3eDNGUT09', $receipt->items[0]->salesplayProductId);
        $this->assertSame(7.5, $receipt->items[0]->unitPrice);

        $this->assertCount(1, $receipt->payments);
        $this->assertSame('Cash', $receipt->payments[0]->paymentMethod);
        $this->assertSame(21.25, $receipt->payments[0]->amount);

        $this->assertSame('NSw1', $page->nextCursor);
    }

    public function test_it_sends_the_token_as_a_custom_header_not_authorization(): void
    {
        Http::fake([
            'api.salesplaypos.com/*' => Http::response(['receipts' => [], 'cursor' => null], 200),
        ]);

        $client = new SalesPlayApiClient(baseUrl: 'https://api.salesplaypos.com/v1.0', timeout: 30);

        $client->fetchReceipts(shopId: 'shop-abc', apiToken: 'my-token', since: null, cursor: null);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Token', 'Bearer my-token')
                && $request->method() === 'GET'
                && $request->url() === 'https://api.salesplaypos.com/v1.0/receipts'
                && $request['shop_id'] === 'shop-abc';
        });
    }

    public function test_it_stops_paginating_when_a_page_comes_back_empty(): void
    {
        Http::fake([
            'api.salesplaypos.com/*' => Http::response(['receipts' => [], 'cursor' => 'same-cursor'], 200),
        ]);

        $client = new SalesPlayApiClient(baseUrl: 'https://api.salesplaypos.com/v1.0', timeout: 30);

        $page = $client->fetchReceipts(shopId: 'shop-abc', apiToken: 'token', since: null, cursor: 'same-cursor');

        $this->assertFalse($page->hasMore);
    }

    public function test_it_throws_on_an_error_response(): void
    {
        Http::fake([
            'api.salesplaypos.com/*' => Http::response(['errors' => ['code' => 'UNAUTHORIZED', 'details' => 'Access token is not valid.']], 401),
        ]);

        $this->expectException(SalesPlayApiException::class);

        (new SalesPlayApiClient(baseUrl: 'https://api.salesplaypos.com/v1.0', timeout: 30))
            ->fetchReceipts(shopId: 'shop-abc', apiToken: 'bad-token', since: null, cursor: null);
    }
}
