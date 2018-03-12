<?php
declare(strict_types=1);

namespace Iulyanp\Tests;

use Iulyanp\FlexValidator;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Validator as v;

class FlexValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new FlexValidator();
    }

    /**
     * @test
     */
    public function validatorOverwritesDefaultRespectErrorMessages()
    {
        $validator = new FlexValidator(['notBlank' => 'The value should not be empty.']);
        $validator->disableRulesName();
        $validator->useDotErrorKeys();
        $validator->validate(
            [
                'contact' => [
                    'phone' => '',
                ],
            ],
            ['contact.phone' => v::notBlank()]
        );

        $this->assertEquals('The value should not be empty.', $validator->getError('contact.phone'));
    }

    /**
     * @test
     */
    public function validatorGlobalMessagesOverwritesDefaultErrorMessages()
    {
        $validator = new FlexValidator(['notBlank' => 'The value should not be empty.']);
        $validator->disableRulesName();
        $validator->validate(
            '',
            [
                'rules' => v::notBlank(),
            ],
            'group',
            [
                'notBlank' => 'The value should not be blank.',
            ]
        );

        $this->assertEquals('The value should not be blank.', $validator->getError('group'));
    }

    /**
     * @test
     */
    public function validateSingleValue()
    {
        $this->validator->disableRulesName();
        $validatedValue = $this->validator->validate(
            'Flex Validator',
            [
                'rules' => v::notBlank()->alnum('_')->noWhitespace(),
            ]
        );

        $this->assertEquals(null, $validatedValue);
        $this->assertEquals('"Flex Validator" must not contain whitespace', $this->validator->getError('0'));
    }

    /**
     * @test
     */
    public function validateSingleValueWithSpecificGroup()
    {
        $this->validator->disableRulesName();
        $this->validator->useDotErrorKeys();
        $this->validator->validate(
            'Flex Validator',
            [
                'rules' => v::notBlank()->alnum('_')->noWhitespace(),
                'group' => 'specificGroup',
            ]
        );

        $this->assertArrayHasKey('specificGroup', $this->validator->getErrors());
        $this->assertEquals(
            '"Flex Validator" must not contain whitespace',
            $this->validator->getError('specificGroup')
        );
    }

    /**
     * @test
     */
    public function validateSingleValueWithGlobalGroup()
    {
        $this->validator->disableRulesName();
        $this->validator->validate(
            'Flex Validator',
            [
                'rules' => v::notBlank()->alnum('_')->noWhitespace(),
            ],
            'global'
        );

        $this->assertArrayHasKey('global', $this->validator->getErrors());
        $this->assertEquals(
            '"Flex Validator" must not contain whitespace',
            $this->validator->getError('global')
        );
    }

    /**
     * @test
     */
    public function validateSingleValueWithDotKeysErrorMessages()
    {
        $this->validator->disableRulesName();
        $this->validator->useDotErrorKeys();
        $validatedValue = $this->validator->validate(
            'Flex Validator',
            [
                'rules' => v::notBlank()->alnum('_')->noWhitespace(),
            ]
        );

        $this->assertEquals(null, $validatedValue);
        $this->assertEquals('"Flex Validator" must not contain whitespace', $this->validator->getError('0'));
    }

    /**
     * @test
     */
    public function validatorAcceptsRespectValidationRulesDirectly()
    {
        $rules = [
            'name' => v::notBlank()->noWhitespace(),
        ];

        $validatedValues = $this->validator->validate($this->getData(), $rules);

        $this->assertFalse($this->validator->isValid());
        $this->assertArrayNotHasKey('name', $validatedValues);
    }

    /**
     * @test
     */
    public function validatorReturnsValidatedValues()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank(),
            ],
        ];

        $validatedValues = $this->validator->validate($this->getData(), $rules);

        $this->assertTrue($this->validator->isValid());
        $this->assertArrayHasKey('name', $validatedValues);
        $this->assertArrayNotHasKey('contact', $validatedValues);
    }

    /**
     * @test
     */
    public function setRulesThrowsErrorsIfRulesAreMissing()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'For name key use only Respect Validation rules or an array with options: rules, messages, group.'
        );

        $rules = [
            'name' => null,
        ];

        $this->validator->validate($this->getData(), $rules);

        $this->assertTrue($this->validator->isValid());
    }

    /**
     * @test
     */
    public function setRulesThrowsErrorsIfRulesAreNotSetCorrectly()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Validation rules are missing or invalid on `name` key. Use only Respect Validation rules.'
        );

        $rules = [
            'name' => [
                'rules' => '',
            ],
        ];

        $this->validator->validate($this->getData(), $rules);

        $this->assertTrue($this->validator->isValid());
    }

    /**
     * @test
     */
    public function validatorHasNamedErrorsKeys()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank()->noWhitespace(),
            ],
        ];

        $this->validator->validate($this->getData(), $rules);

        $this->assertFalse($this->validator->isValid());
        $this->assertArrayHasKey('noWhitespace', $this->validator->getErrors('name'));
    }

    /**
     * @test
     */
    public function validatorHasNumericErrorsKeys()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank()->noWhitespace(),
            ],
        ];
        $this->validator->disableRulesName();
        $this->validator->validate($this->getData(), $rules);

        $this->assertArrayHasKey(0, $this->validator->getErrors('name'));
        $this->assertArrayNotHasKey('noWhitespace', $this->validator->getErrors('name'));
    }

    /**
     * @test
     */
    public function validatorValidateNestedArrayKeys()
    {
        $rules = [
            'contact.phone' => [
                'rules' => v::notBlank(),
            ],
        ];

        $this->validator->validate($this->getData(), $rules);

        $this->assertArrayHasKey('notBlank', $this->validator->getErrors('contact.phone'));
    }

    /**
     * @test
     */
    public function validatorHasErrorsFieldsWithDot()
    {
        $rules = [
            'contact.phone' => [
                'rules' => v::notBlank(),
            ],
        ];

        $this->validator->useDotErrorKeys();

        $this->validator->validate($this->getData(), $rules);

        $this->assertArrayHasKey('contact.phone', $this->validator->getErrors());
    }

    /**
     * @test
     */
    public function validatorReturnsCustomErrorMessagesOnSpecificField()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank()->noWhitespace(),
                'messages' => [
                    'noWhitespace' => 'The name should not contain spaces.',
                ],
            ],
            'contact.phone' => [
                'rules' => v::notBlank(),
                'messages' => [
                    'notBlank' => 'The phone number should not be blank.',
                ],
            ],
        ];

        $this->validator->disableRulesName();
        $this->validator->validate($this->getData(), $rules);

        $this->assertEquals('The name should not contain spaces.', $this->validator->getError('name'));
        $this->assertEquals('The phone number should not be blank.', $this->validator->getError('contact.phone'));
    }

    /**
     * @test
     */
    public function validatorHasSpecificFieldErrorGrouped()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank()->noWhitespace(),
                'group' => 'identity',
            ],
            'contact.phone' => [
                'rules' => v::notBlank(),
            ],
        ];

        $this->validator->validate($this->getData(), $rules);

        $this->assertArrayHasKey('identity', $this->validator->getErrors());
        $this->assertArrayHasKey('name', $this->validator->getErrors('identity'));
        $this->assertArrayHasKey('noWhitespace', $this->validator->getErrors('identity.name'));
        $this->assertArrayHasKey('contact', $this->validator->getErrors());
        $this->assertArrayHasKey('phone', $this->validator->getErrors('contact'));
        $this->assertArrayHasKey('notBlank', $this->validator->getErrors('contact.phone'));
    }

    /**
     * @test
     */
    public function validatorApplyGlobalGroupToErrorFieldsThatNotHaveSpecificGroupSet()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank()->noWhitespace(),
                'group' => 'identity',
            ],
            'contact.phone' => [
                'rules' => v::notBlank(),
            ],
        ];

        $this->validator->validate($this->getData(), $rules, 'global');

        $this->assertArrayHasKey('identity', $this->validator->getErrors());
        $this->assertArrayHasKey('noWhitespace', $this->validator->getErrors('identity.name'));
        $this->assertArrayHasKey('global', $this->validator->getErrors());
        $this->assertArrayHasKey('notBlank', $this->validator->getErrors('global.contact.phone'));
    }

    /**
     * @test
     */
    public function validatorApplyGlobalGroupToErrorFieldsThatNotHaveSpecificGroup()
    {
        $rules = [
            'name' => [
                'rules' => v::notBlank()->noWhitespace(),
                'group' => 'identity',
            ],
            'contact.phone' => [
                'rules' => v::notBlank()->numeric(),
            ],
            'contact.address.number' => [
                'rules' => v::notBlank()->numeric()->length(4),
            ],
        ];

        $this->validator->useDotErrorKeys();
        $this->validator->validate($this->getData(), $rules, 'global');
        $this->assertArrayHasKey('identity.name', $this->validator->getErrors());
        $this->assertArrayHasKey('noWhitespace', $this->validator->getErrors('identity.name'));
        $this->assertArrayHasKey('global.contact.phone', $this->validator->getErrors());
        $this->assertArrayHasKey('notBlank', $this->validator->getErrors('global.contact.phone'));
    }

    /**
     * Tests the case when abstract composite rule is used,
     * for example: "OneOf" which is using a group of rules
     *
     * @test
     */
    public function validatorUseAbstractComposite()
    {
        $rules = [
            'name' => [
                'rules' => v::oneOf(v::numeric(), v::length(0, 2)),
                'messages' => [
                    'numeric' => 'Test message',
                    'length' => 'Test message2',
                ],
            ],
            'contact.phone' => [
                'rules' => v::oneOf(v::numeric(), v::length(4, 9)),
                'messages' => [
                    'numeric' => 'Numeric message',
                ],
            ],
        ];

        $this->validator->validate($this->getData(), $rules);

        $this->assertEquals('Numeric message', $this->validator->getError('contact.phone.numeric'));
        $this->assertEquals('Test message', $this->validator->getError('name.numeric'));
        $this->assertEquals('Test message2', $this->validator->getError('name.length'));
    }

    /**
     * Tests the case when AbstractWrapper validator is used,
     * for example the "Optional" one
     *
     * @test
     */
    public function validatorUseAbstractWrapper()
    {
        $rules = [
            'name' => [
                'rules' => v::optional(v::numeric()),
                'messages' => [
                    'numeric' => 'Test overwrite abstract wrapper message',
                ],
            ],
        ];

        $this->validator->validate($this->getData(), $rules);
        $this->assertEquals(
            'Test overwrite abstract wrapper message',
            $this->validator->getError('name.numeric')
        );
    }

    /**
     * @test
     */
    public function validateWithSameRuleMultipleTimes()
    {
        $data = [
            'username' => 'iulian.popa',
        ];

        $rules = [
            'username' => [
                'rules' => v::allOf(v::alpha('_'), v::alpha(',')),
            ],
        ];

        $this->validator->validate($data, $rules);

        $this->assertEquals(
            '"iulian.popa" must contain only letters (a-z) and ""_""',
            $this->validator->getError('username.alpha')
        );
    }

    /**
     *
     * @return array
     */
    public function getData()
    {
        return [
            'name' => 'Iulian Popa',
            'contact' => [
                'phone' => '',
                'address' => [
                    'number' => 192,
                    'street' => 'Parks Road',
                ],
            ],
        ];
    }
}
