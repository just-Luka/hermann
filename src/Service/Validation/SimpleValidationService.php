<?php

declare(strict_types=1);

namespace App\Service\Validation;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class SimpleValidationService
{
    private array $messages = [];
    private array $data = [];

    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {}

    public function fails(Request $request, string $type = FormType::class): bool
    {
        $input = $request->request->all();

        $form = $this->formFactory->create($type);
        $form->submit($input);
    
        if (!$form->isSubmitted() || !$form->isValid()) {
            foreach ($form->getErrors(true, true) as $error) {
                $field = $error->getOrigin()->getName();
                $this->messages[$field] = $error->getMessage();
            }
    
            return true;
        }
        
        return false;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getData(): array
    {
        return $this->data;
    }
}