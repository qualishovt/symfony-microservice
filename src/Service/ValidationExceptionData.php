<?php

namespace App\Service;

use Symfony\Component\Validator\ConstraintViolationList;

class ValidationExceptionData extends ServiceExceptionData
{
    private ConstraintViolationList $violationList;

    public function __construct(int $statusCode, string $type, ConstraintViolationList $violationList)
    {
        parent::__construct($statusCode, $type);

        $this->violationList = $violationList;
    }

    public function toArray(): array
    {
        return [
            'type' => 'ConstraintViolationList',
            'violations' => $this->getViolationsArray(),
        ];
    }

    public function getViolationsArray(): array
    {
        $violations = [];

        foreach ($this->violationList as $violation) {
            $violations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $violations;
    }
}