<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Delivery\DeliveryTransportRegistryInterface;
use Setono\SyliusFeedPlugin\Model\DeliveryTarget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One delivery target row (§12): the `transport` (from the registered transport types), the
 * `pathTemplate` and the `transportConfig`/`match` maps edited as JSON. Secrets in `transportConfig`
 * are expected to be env placeholders resolved by the application, not clear-text values.
 */
final class DeliveryTargetType extends AbstractType
{
    public function __construct(private readonly DeliveryTransportRegistryInterface $transportRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transport', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.delivery_target.transport',
                'choices' => $this->transportChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('pathTemplate', TextType::class, [
                'label' => 'setono_sylius_feed.form.delivery_target.path_template',
                'help' => 'setono_sylius_feed.form.delivery_target.path_template_help',
            ])
            ->add('transportConfig', TextareaType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.delivery_target.transport_config',
                'help' => 'setono_sylius_feed.form.delivery_target.transport_config_help',
            ])
            ->add('match', TextareaType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.delivery_target.match',
                'help' => 'setono_sylius_feed.form.delivery_target.match_help',
            ])
        ;

        $builder->get('transportConfig')->addModelTransformer($this->jsonTransformer());
        $builder->get('match')->addModelTransformer($this->jsonTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', DeliveryTarget::class);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_delivery_target';
    }

    /**
     * @return array<string, string>
     */
    private function transportChoices(): array
    {
        $choices = [];
        foreach (array_keys($this->transportRegistry->all()) as $type) {
            $choices[$type] = $type;
        }

        ksort($choices);

        return $choices;
    }

    /**
     * Edits a stored `array<string, mixed>` as a JSON object string.
     */
    private function jsonTransformer(): CallbackTransformer
    {
        return new CallbackTransformer(
            static function (?array $value): string {
                if (null === $value || [] === $value) {
                    return '';
                }

                $json = json_encode($value, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

                return false === $json ? '' : $json;
            },
            static function ($value): array {
                if (!is_string($value) || '' === trim($value)) {
                    return [];
                }

                $decoded = json_decode($value, true);
                if (!is_array($decoded)) {
                    throw new TransformationFailedException('The value must be a valid JSON object.');
                }

                return $decoded;
            },
        );
    }
}
