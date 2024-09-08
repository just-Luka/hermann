<?php

declare(strict_types=1);

namespace App\Channel\Signals\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Range;

class TradingSignalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assetType', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Asset type is required.']),
                ],
            ])
            ->add('assetName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Asset name is required.']),
                ],
            ])
            ->add('entryPrice', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Entry price is required.']),
                    new GreaterThan(['value' => 0, 'message' => 'Entry price must be greater than 0.']),
                ],
            ])
            ->add('targetPrice', TextType::class, [
                'constraints' => [
                    new Optional(),
                    new GreaterThan(['value' => 0, 'message' => 'Target price must be greater than 0.']),
                ],
            ])
            ->add('stopPrice', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Stop price is required.']),
                    new GreaterThan(['value' => 0, 'message' => 'Stop price must be greater than 0.']),
                ],
            ])
            ->add('timeFrame', TextType::class, [
                'constraints' => [
                    new Optional(),
                ]
            ])
            ->add('tradeDirection', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Trade direction is required.']),
                    new Choice([
                        'choices' => ['SHORT', 'LONG'],
                        'message' => 'Trade direction must be either "SHORT" or "LONG".',
                    ]),
                ],
            ])
            ->add('positionSize', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Position size is required.']),
                    new Range([
                        'min' => 0, 
                        'minMessage' => 'Position size must be at least {{ limit }}.',
                    ]),
                ],
            ])
            ->add('successRate', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Success rate is required.']),
                    new Range([
                        'min' => 0, 
                        'minMessage' => 'Success rate must be between 0 and 100.'
                    ]),
                ],
            ]);
    }
}
