<?php

namespace App\Tests\Unit;

use App\DTO\LowestPriceEnquiry;
use App\Event\AfterDtoCreatedEvent;
use App\EventSubscriber\DtoSubscriber;
use App\Service\ServiceException;
use App\Tests\ServiceTestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DtoSubscriberTest extends ServiceTestCase
{
//    /** @test */
//    public function a_dto_is_validated_after_it_has_been_created(): void
//    {
//        $dto = new LowestPriceEnquiry();
//        $dto->setQuantity(-5);
//
//        $event = new AfterDtoCreatedEvent($dto);
//
//        /** @var EventDispatcherInterface $eventDispatcher */
//        $eventDispatcher = $this->container->get('event_dispatcher');
//
//        $this->expectException(ValidationFailedException::class);
//        $this->expectExceptionMessage('This value should be positive.');
//
//        $eventDispatcher->dispatch($event, $event::NAME);
//    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(AfterDtoCreatedEvent::NAME, DtoSubscriber::getSubscribedEvents());
    }

    public function testValidateDto(): void
    {
        $dto = new LowestPriceEnquiry();
        $dto->setQuantity(-5);
        $event = new AfterDtoCreatedEvent($dto);
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('ConstraintViolationList');

        $dispatcher->dispatch($event, $event::NAME);
    }
}