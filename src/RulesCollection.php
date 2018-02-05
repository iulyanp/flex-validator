<?php
declare(strict_types=1);

namespace Iulyanp;

use Respect\Validation\Rules\AbstractComposite;
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
     * @param mixed  $rules
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
        if ($rules instanceof AbstractComposite) {
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
        $this->rules = new AllOf($rules);

        $this->checkRules();

        return $this;
    }


    /**
     * Verifies that rules are set and valid.
     *
     * @throws \InvalidArgumentException
     */
    private function checkRules()
    {
        if (!$this->rules instanceof AbstractComposite) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Validation rules are missing or invalid on `%s` key. Use only Respect Validation rules.',
                    $this->getKey()
                )
            );
        }
    }
}
