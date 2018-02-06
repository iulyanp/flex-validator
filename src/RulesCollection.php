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
    const RULES_CONFIG = 'rules';
    const MESSAGES_CONFIG = 'messages';
    const GROUP_CONFIG = 'group';
    const CONFIG = [self::RULES_CONFIG, self::MESSAGES_CONFIG, self::GROUP_CONFIG];


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
                    implode(', ', self::CONFIG)
                )
            );
        }
        $this->setRulesConfig($rules);

        $this->checkRules();

        return $this;
    }


    /**
     * @param array $rules
     */
    private function setRulesConfig(array $rulesConfig)
    {
        $rulesConfig = $this->mergeWithDefaultRules($rulesConfig);

        foreach ($rulesConfig as $configType => $configValue) {
            if (\in_array($configType, self::CONFIG, true) && !empty($configValue)) {
                $this->setRulesConfigType($configType, $configValue);
            }
        }
    }

    /**
     * Sets config in current rulles collection
     *
     * @param string $configType
     * @param mixed  $configValue
     */
    private function setRulesConfigType(string $configType, $configValue)
    {
        if ($configType == self::RULES_CONFIG) {
            $this->$configType = new AllOf($configValue);
            return;
        }

        $this->$configType = $configValue;
    }

    /**
     * @param $rules
     *
     * @return array
     */
    private function mergeWithDefaultRules(array $rules): array
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(self::RULES_CONFIG);
        $resolver->setDefaults(
            [
                self::MESSAGES_CONFIG => [],
                self::GROUP_CONFIG => '',
            ]
        );
        $resolver->setAllowedTypes(self::MESSAGES_CONFIG, 'array');
        $resolver->setAllowedTypes(self::GROUP_CONFIG, 'string');

        return $resolver->resolve($rules);
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
