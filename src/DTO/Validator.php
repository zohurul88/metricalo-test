<?php

namespace App\DTO;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function validate(DTOInterface $data): void
    {
        $errors = $this->validator->validate($data);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $violation) {
                $errorMessages[] = [
                    'type'=>'validation_error',
                    'code' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
            throw new ValidatorException($errorMessages);
        } 
    }
}
