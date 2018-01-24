Flex Validator
----

A simple validator based on [Respect Validation](https://github.com/Respect/Validation) library, that provides an
easy way to customize the error messages.

### Installation

If you have composer installed globally you can run:

```
$ composer require iulyanp/flex-validator
```

or you can go with:

```
$ php composer.phar require iulyanp/flex-validator
```

### Usage

FlexValidator can validate simple input:

```
$validator = new FlexValidator();
$validator->validate('Flex Validator', ['rules' => v::notBlank()->alnum('_')->noWhitespace()]);

if (!$validator->isValid()) {
    var_dump($validator->getErrors());
}

// do something with the valid data
```

For our little example the `getErrors()` will return something like this:

```
array:2 [
  "notBlank" => """ must not be blank"
  "alnum" => """ must contain only letters (a-z), digits (0-9) and "_""
]
```

But you can also validate arrays like this:

````
// use Respect Validator
use Respect\Validation\Validator;

$array = [
    'name' => 'Iulian Popa',
    'contact' => [
        'phone' => '07579940094',
        'address' => 'str, Ion Creanga nr.14 A bl. N2'
    ]
];

$rules = [
    'name' => Validator::notBlank(),
    'contact.phone' => Validator::notBlank()->numeric()->length(0, 10),
    'contact.address' => Validator::notBlank()->alnum('.')->length(6, 30),
];

$validator = new FlexValidator();
$validator->validate($array, $rules);

if (!$validator->isValid()) {
    var_dump($validator->getErrors());
}
````

The `getErrors` method will return an array with all breaking validation rules:

```
array:1 [
  "contact" => array:2 [
    "phone" => array:1 [
      "length" => ""07579940094" must have a length lower than 10"
    ]
    "address" => array:2 [
      "alnum" => ""str, Ion Creanga nr.14 A bl. N2" must contain only letters (a-z), digits (0-9) and ".""
      "length" => ""str, Ion Creanga nr.14 A bl. N2" must have a length between 6 and 30"
    ]
  ]
]
```

#### Validation methods

Currently you can validate simple values and multidimensional arrays.

```
$rules = [
    'rules' => Validator::notBlank(),
];

$validator->validate('value', $rules);
```

The `validate` method is a shortcut also for the `array` method which will validate multidimensional arrays.

```
$array = [
    'name' => 'Iulian Popa',
    'contact' => [
        'phone' => '07579940094',
        'address' => 'str, Ion Creanga nr.14 A bl. N2'
    ]
];

$rules = [
    'name' => Validator::notBlank(),
    'contact.phone' => Validator::notBlank()->numeric()->length(0, 10),
    'contact.address' => Validator::notBlank()->alnum('.')->length(6, 30),
];

$validator->array($array, $rules);
```

#### Disable named rules

As we saw in above the examples the error messages had the key of the errors array as the name of the broken rule: 
`alnum`, `length`.

```
array:1 [
  "contact" => array:2 [
    "address" => array:2 [
      "alnum" => ""str, Ion Creanga nr.14 A bl. N2" must contain only letters (a-z), digits (0-9) and ".""
      "length" => ""str, Ion Creanga nr.14 A bl. N2" must have a length between 6 and 30"
    ]
  ]
]
```

We can disable this with a simple call of the `disableRulesName()` method on the FlexValidator.

```
$validator->disableRulesName();

$validator->validate($array, $rules);

if (!$validator->isValid()) {
    var_dump($validator->getErrors());
}

//    array:1 [
//      "contact" => array:2 [
//        "phone" => array:1 [
//          0 => ""07579940094" must have a length lower than 10"
//        ]
//        "address" => array:2 [
//          0 => ""str, Ion Creanga nr.14 A bl. N2" must contain only letters (a-z), digits (0-9) and ".""
//          1 => ""str, Ion Creanga nr.14 A bl. N2" must have a length between 6 and 30"
//        ]
//      ]
//    ]
```

#### Use dot error keys

Also the errors array can be returned with dot keys. 

```
$validator->useDotErrorKeys();
$validator->validate($array, $rules);

if (!$validator->isValid()) {
    var_dump($validator->getErrors());
}

//    array:2 [
//        "contact.phone" => array:1 [
//        "length" => ""07579940094" must have a length lower than 10"
//      ]
//      "contact.address" => array:2 [
//        "alnum" => ""str, Ion Creanga nr.14 A bl. N2" must contain only letters (a-z), digits (0-9) and ".""
//        "length" => ""str, Ion Creanga nr.14 A bl. N2" must have a length between 6 and 30"
//      ]
//    ]
```

You can also use the `dot keys` with `disabled rule names` together.

#### Error groups

Sometime when we validate an array of data maybe we want to wrap the error messages in a group. With FlexValidator we
can do this in two ways: global group and specific group.

##### Specific group

First let's take a look on how we can specify a specific group on a specific array key.
```
use Respect\Validation\Validator;

$array = [
    'username' => '',
    'password' => '',
];
$rules = [
    'username' => [
        'rules' => Validator::notBlank(),
        'group' => 'login'
    ],
    'password' => [
        'rules' => Validator::notBlank(),
    ]
];

$validator = new FlexValidator();
$validator->validate($array, $rules);

// will return
array:2 [
  "login" => array:1 [
    "username" => array:1 [
      "notBlank" => "null must not be blank"
    ]
  ]
  "password" => array:1 [
    "notBlank" => "null must not be blank"
  ]
]
```
As you can see the field `username` was wrapped in a group `login`.

##### Global group

The global group will be applied only on the fields that don't have a specific group set. To specify a global group 
we can pass the third argument to the `validate` method.

```
use Respect\Validation\Validator;

$array = [
    'username' => '',
    'password' => '',
];
$rules = [
    'username' => [
        'rules' => Validator::notBlank(),
        'group' => 'login'
    ],
    'password' => [
        'rules' => Validator::notBlank(),
    ]
];

$validator = new FlexValidator();
$validator->validate($array, $rules, 'auth');

// will return
array:2 [
  "login" => array:1 [
    "username" => array:1 [
      "notBlank" => "null must not be blank"
    ]
  ]
  "auth" => array:1 [
    "password" => array:1 [
      "notBlank" => "null must not be blank"
    ]
  ]
]
```

#### Custom error messages

Almost all the time when we use a validation library we want to overwrite the default error messages. In the next 
steps we can see how FlexValidator made this objective very clean and easy to use.

##### Overwrite default error messages from RespectValidation

We can set the default error messages passing an array with the messages for each respect validation rule.

```
use Respect\Validation\Validator;

$array = [
    'name' => '',
];
$rules = [
    'name' => Validator::notBlank(),
];

$validator = new FlexValidator([
    'notBlank' => 'The value should not be blank'
]);
$validator->validate($array, $rules);

array:2 [
  "name" => array:1 [
    "notBlank" => "The value should not be blank"
  ]
]
```

##### Custom error messages globally for an entire set of data

We can go further and use custom error messages globally when we validate a set of data by passing the array with 
error messages as the fourth argument to the `validate` method.


```
use Respect\Validation\Validator;

$array = [
    'name' => '',
    'contact' => [
        'phone' => ''
    ]
];
$rules = [
    'name' => Validator::notBlank(),
    'contact.phone' => Validator::notBlank(),
];

$validator = new FlexValidator([
    'notBlank' => 'The value should not be blank'
]);
$validator->validate($array, $rules, '', [
    'notBlank' => 'The value should not be empty'
]);

// will return
array:2 [
  "name" => array:1 [
    "notBlank" => "The value should not be empty"
  ]
  "contact" => array:1 [
    "phone" => array:1 [
      "notBlank" => "The value should not be empty"
    ]
  ]
]
```


##### Custom error messages on specific value of an array

If we want to use a custom error message only for a specific field we can do so by specifying the `messages` key as 
an array with all the custom error messages. Here is an example.

```
use Respect\Validation\Validator;

$array = [
    'name' => '',
    'contact' => [
        'phone' => '',
        'address' => 'str, Ion Creanga nr.14 A bl. N2',
    ],
];

$rules = [
    'name' => Validator::notBlank(),
    'contact.phone' => [
        'rules' => Validator::notBlank()->numeric()->length(0, 10),
        'messages' => [
            'notBlank' => 'Please provide the phone number.',
            'numeric' => 'Your phone number shounld contain only numbers.',
            'length' => 'Your phone number should not be over 10 characters.'
        ],
    ],
];

$validator = new FlexValidator([
    'notBlank' => 'The value should not be blank'
]);
$validator->validate($array, $rules, '', [
    'notBlank' => 'The value should not be empty'
]);

// will return 
array:2 [
  "name" => array:1 [
    "notBlank" => "The value should not be empty"
  ]
  "contact" => array:1 [
    "phone" => array:2 [
      "notBlank" => "Please provide the phone number."
      "numeric" => "Your phone number shounld contain only numbers."
    ]
  ]
]
```

As you can see the error message from the `name` field is taken from the fourth argument of the `validate` method and
the error messages for the `phone` number are taken from the `messages` array.
