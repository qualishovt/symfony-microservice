<?php

namespace App\Tests\Unit;

use App\DTO\LowestPriceEnquiry;
use App\Entity\Product;
use App\Entity\Promotion;
use App\Filter\LowestPriceFilter;
use App\Tests\ServiceTestCase;

class LowestPriceFilterTest extends ServiceTestCase
{
    /** @test */
    public function lowest_price_promotions_filtering_is_applied_correctly(): void
    {
        // Given
        $product = new Product();
        $product->setPrice(100);

        $enquiry = new LowestPriceEnquiry();
        $enquiry->setProduct($product);
        $enquiry->setQuantity(5);
        $enquiry->setRequestDate('2022-11-27');
        $enquiry->setVoucherCode('OU812');

        $promotions = $this->promotionsDataProvider();

        $lowestPriceFilter = $this->container->get(LowestPriceFilter::class);

        // When
        $filteredEnquiry = $lowestPriceFilter->apply($enquiry, ...$promotions);

        // Then
        $this->assertSame(100, $filteredEnquiry->getPrice());
        $this->assertSame(250, $filteredEnquiry->getDiscountedPrice());
        $this->assertSame('Black Friday half price sale', $filteredEnquiry->getPromotionName());;
    }

    public function promotionsDataProvider(): array
    {
        $promotion1 = new Promotion();
        $promotion1->setName('Black Friday half price sale');
        $promotion1->setAdjustment(0.5);
        $promotion1->setCriteria(['from' => '2022-11-25', 'to' => '2022-11-28']);
        $promotion1->setType('date_range_multiplier');

        $promotion2 = new Promotion();
        $promotion2->setName('Voucher OU812');
        $promotion2->setAdjustment(100);
        $promotion2->setCriteria(['code' => 'OU812']);
        $promotion2->setType('fixed_price_voucher');

        $promotion3 = new Promotion();
        $promotion3->setName('Buy one get one free');
        $promotion3->setAdjustment(0.5);
        $promotion3->setCriteria(['minimum_quantity' => 2]);
        $promotion3->setType('even_items_multiplier');

        return [$promotion1, $promotion2, $promotion3];

    }
}