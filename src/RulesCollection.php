<?php
declare(strict_types=1);

namespace Iulyanp;

use Respect\Validation\Rules\AllOf;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RulesCollection
{
    private $key;
    private $rules;
    private $messages;
    private $group;
    const OPTIONS = ['rules', 'messages', 'group'];

    /**
     * RulesCollection constructor.
     *
     * @param        $rules
     * @param string $key
     * @param string $group
     */
    public function __construct($rules, string $key = null, string $group = null)
    {
        $this->key = $key;
        $this->group = $group;
        $this->setRules($rules);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return null|string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return AllOf
     */
    public function getValidationRules()
    {
        return $this->rules;
    }

    /**
     * @return bool
     */
    public function hasMessages()
    {
        return !empty($this->messages);
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param array $rules
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    private function setRules($rules)
    {
        if ($rules instanceof AllOf) {
            $this->rules = $rules;

            return $this;
        }

        if (!\is_array($rules)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'For %s key use only Respect Validation rules or an array with options: %s.',
                    $this->getKey(),
                    implode(', ', self::OPTIONS)
                )
            );
        }

        $this->setArrayRules($rules);

        $this->checkRules();

        return $this;
    }

    /**
     * @param array $rules
     */
    private function setArrayRules(array $rules)
    {
        $rules = $this->mergeWithDefaultRules($rules);

        foreach ($rules as $key => $value) {
            if (\in_array($key, self::OPTIONS, true) && !empty($value)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param $rules
     *
     * @return array
     */
    private function mergeWithDefaultRules(array $rules): array
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired('rules');
        $resolver->setDefaults(
            [
                'messages' => [],
                'group' => '',
            ]
        );
        $resolver->setAllowedTypes('messages', 'array');
        $resolver->setAllowedTypes('group', 'string');

        return $resolver->resolve($rules);
    }

    /**
     * Verifies that rules are set and valid.
     *
     * @throws \InvalidArgumentException
     */
    private function checkRules()
    {
        if (!$this->rules instanceof AllOf) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Validation rules are missing or invalid on `%s` key. Use only Respect Validation rules.',
                    $this->getKey()
                )
            );
        }
    }
}
