<?php
declare(strict_types=1);

namespace Iulyanp;

use Respect\Validation\Rules\AbstractComposite;
use Respect\Validation\Rules\AbstractWrapper;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Rules\Optional;

class FlexValidator
{
    /**
     * @var bool
     */
    private $showRulesName = true;
    /**
     * @var bool
     */
    private $dotErrorKeys = false;
    /**
     * @var array
     */
    private $errors = [];
    /**
     * @var array
     */
    private $values = [];
    /**
     * @var array
     */
    private $defaultMessages;

    /**
     * FlexValidator constructor.
     *
     * @param array $defaultMessages
     */
    public function __construct(array $defaultMessages = [])
    {
        $this->defaultMessages = $defaultMessages;
    }

    /**
     * @return $this
     */
    public function disableRulesName()
    {
        $this->showRulesName = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function useDotErrorKeys()
    {
        $this->dotErrorKeys = true;

        return $this;
    }

    /**
     * @param             $input
     * @param array       $rules
     * @param string|null $group
     * @param array       $globalMessages
     *
     * @return array
     */
    public function validate($input, array $rules, string $group = null, array $globalMessages = [])
    {
        if (\is_array($input)) {
            return $this->validateArray($input, $rules, $group, $globalMessages);
        }

        return $this->validateSingleValue($input, $rules, $group, $globalMessages);
    }

    /**
     * @param             $input
     * @param array       $rules
     * @param string      $group
     * @param array       $messages
     *
     * @return mixed
     */
    public function validateSingleValue($input, array $rules, string $group = null, array $messages = [])
    {
        $rulesCollection = new RulesCollection($rules, null, $group);

        $this->validateInput($input, $rulesCollection, $messages);

        if (!empty($group)) {
            return $this->getValue($group);
        }

        if (!empty($rulesCollection->getGroup())) {
            return $this->getValue($rulesCollection->getGroup());
        }

        return $this->getValue('0');
    }

    /**
     * @param array  $array
     * @param array  $rules
     * @param string $group
     * @param array  $globalMessages
     *
     * @return array
     */
    public function validateArray(array $array, array $rules, string $group = null, array $globalMessages = [])
    {
        foreach ($rules as $key => $ruleCollection) {
            $ruleSet = new RulesCollection($ruleCollection, $key, $group);

            $value = array_get($array, $key);

            $this->validateInput($value, $ruleSet, $globalMessages);
        }

        return $this->getValues();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getValue(string $key = null)
    {
        return array_get($this->values, $key);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values ?? [];
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * @param string|null $key
     *
     * @return array
     */
    public function getErrors(string $key = null): array
    {
        if (null !== $key) {
            return array_get($this->errors, $key, []);
        }

        return $this->errors;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getError(string $key)
    {
        $error = array_get($this->errors, $key);

        if (is_array($error)) {
            return reset($error);
        }

        return $error;
    }

    /**
     * @param        $value
     * @param string $key
     * @param string $group
     *
     * @return $this
     */
    private function setValue($value, string $key = null, string $group = null)
    {
        if (!empty($group) && !empty($key)) {
            array_set($this->values, sprintf('%s.%s', $group, $key), $value);

            return $this;
        }

        if (!empty($group) && empty($key)) {
            array_set($this->values, $group, $value);

            return $this;
        }

        if (empty($group) && empty($key)) {
            array_set($this->values, 0, $value);

            return $this;
        }

        array_set($this->values, $key, $value);

        return $this;
    }

    /**
     * @param                 $input
     * @param RulesCollection $ruleSet
     * @param array           $globalMessages
     */
    private function validateInput($input, RulesCollection $ruleSet, array $globalMessages = [])
    {
        try {
            $ruleSet->getValidationRules()->assert($input);
        } catch (NestedValidationException $e) {
            $this->handleValidationException($e, $ruleSet, $globalMessages);
        }

        $this->setValue($input, $ruleSet->getKey(), $ruleSet->getGroup());
    }

    /**
     * @param $errors
     *
     * @return array
     */
    private function mergeMessages(array $errors): array
    {
        $errors = array_filter(array_merge(...$errors));

        return $this->showRulesName ? $errors : array_values($errors);
    }

    /**
     * Returns all rule names which were failed
     *
     * @param NestedValidationException $exception
     *
     * @return array
     */
    private function getFailedRulesNames(NestedValidationException $exception): array
    {
        $rulesNames = [];
        foreach ($exception->getIterator() as $nestedException) {
            $exceptionId = $nestedException->getId();
            if (in_array($exceptionId, $rulesNames)) {
                continue;
            }
            $rulesNames[] = $exceptionId;
        }

        return $rulesNames;
    }

    /**
     * @param NestedValidationException $exception
     * @param RulesCollection           $ruleSet
     * @param array                     $globalMessages
     */
    private function handleValidationException(
        NestedValidationException $exception,
        RulesCollection $ruleSet,
        array $globalMessages = []
    ) {
        $errors = [
            $exception->findMessages($this->getFailedRulesNames($exception)),
        ];
        if (!empty($this->defaultMessages)) {
            $errors[] = $exception->findMessages($this->defaultMessages);
        }

        if (!empty($globalMessages)) {
            $errors[] = $exception->findMessages($globalMessages);
        }

        if ($ruleSet->hasMessages()) {
            $errors[] = $exception->findMessages($ruleSet->getMessages());
        }

        $this->setErrors($this->mergeMessages($errors), $ruleSet->getKey(), $ruleSet->getGroup());
    }

    /**
     * @param array  $errors
     * @param string $key
     * @param string $group
     */
    private function setErrors(array $errors, string $key = null, string $group = null)
    {
        if (!$this->dotErrorKeys) {
            $this->setErrorsWithNestedKeys($errors, $key, $group);

            return;
        }

        $this->setErrorsWithDotKeys($errors, $key, $group);
    }

    /**
     * @param array  $errors
     * @param string $key
     * @param string $group
     */
    private function setErrorsWithDotKeys(array $errors, string $key = null, string $group = null)
    {
        if (!empty($group) && !empty($key)) {
            $this->errors[sprintf('%s.%s', $group, $key)] = $errors;

            return;
        }

        if (!empty($group) && empty($key)) {
            $this->errors[$group] = $errors;

            return;
        }

        if (empty($group) && empty($key)) {
            $this->errors = $errors;

            return;
        }

        $this->errors[$key] = $errors;
    }

    /**
     * @param array  $errors
     * @param string $key
     * @param string $group
     */
    private function setErrorsWithNestedKeys(array $errors, string $key = null, string $group = null)
    {
        if (!empty($group) && !empty($key)) {
            array_set($this->errors, sprintf('%s.%s', $group, $key), $errors);

            return;
        }

        if (!empty($group) && empty($key)) {
            array_set($this->errors, $group, $errors);

            return;
        }

        if (empty($group) && empty($key)) {
            $this->errors = $errors;

            return;
        }

        array_set($this->errors, $key, $errors);
    }
}

