<?php

declare(strict_types=1);

namespace Zaphyr\ValidateTests\Unit;

use Countable;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zaphyr\Validate\Exceptions\ValidatorException;
use Zaphyr\Validate\Rules\RequiredRule;
use Zaphyr\Validate\Validator;
use Zaphyr\ValidateTests\TestAssets\Rules\Banana;

class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    protected Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator(
            'en',
            dirname(__DIR__) . '/TestAssets/translations'
        );
    }

    protected function tearDown(): void
    {
        unset($this->validator);
    }

    /* -------------------------------------------------
     * VALIDATE
     * -------------------------------------------------
     */

    public function testValidatePassed(): void
    {
        $inputs = [
            'name' => 'merloxx',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertTrue($this->validator->isValid());
    }

    public function testValidateFails(): void
    {
        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertFalse($this->validator->isValid());
    }

    public function testValidateContinuesOnDataWithoutValidationRule(): void
    {
        $inputs = [
            'name' => 'merloxx',
            '_token' => 'foobar',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertTrue($this->validator->isValid());
    }

    public function testValidateWithAllowExtraInputsIsFalse(): void
    {
        $inputs = [
            'name' => 'merloxx',
            'position' => 'head of bullshit',
            'surname' => 'xxolrem',
        ];

        $rules = [
            'name' => 'required',
            'surname' => 'required',
        ];

        $this->validator->validate($inputs, $rules, allowExtraInputs: false);

        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'Position was not expected',
            $this->validator->errors()->first('position')
        );
    }

    public function testValidateRequiredOnMissingInputFields(): void
    {
        $inputs = [];
        $rules = ['name' => 'required'];

        $this->validator->validate($inputs, $rules);

        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name field is required',
            $this->validator->errors()->first('name')
        );
    }

    public function testValidateReturnsTrueOnNullableRule(): void
    {
        $inputs = [
            'value' => null,
        ];

        $rules = [
            'value' => 'nullable|min_number:20',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertTrue($this->validator->isValid());
    }

    public function testValidateWithNullRuleDoesNotIgnoreFollowingValidationFields(): void
    {
        $inputs = [
            'value' => null,
            'another_value' => null
        ];

        $rules = [
            'value' => 'nullable|min_number:20',
            'another_value' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals(
            'The another value field is required',
            $this->validator->errors()->first('another_value')
        );
        self::assertFalse($this->validator->isValid());
    }

    public function testValidateWithMultipleNullableRules(): void
    {
        $inputs = [
            'value' => null,
            'another_value' => null,
            'foo' => '',
        ];

        $rules = [
            'value' => 'nullable|min_number:20',
            'another_value' => 'required',
            'foo' => 'nullable|required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals(
            'The another value field is required',
            $this->validator->errors()->first('another_value')
        );
        self::assertEquals(
            'The foo field is required',
            $this->validator->errors()->first('foo')
        );
        self::assertFalse($this->validator->isValid());
    }

    public function testValidatorCachesResolvedRuleInstances(): void
    {
        $validator = new Validator();

        $reflection = new ReflectionClass($validator);
        $property = $reflection->getProperty('cachedRules');
        $property->setAccessible(true);
        self::assertSame([], $property->getValue($validator));

        $inputs = [
            'name' => 'merloxx',
            'surname' => 'xxolrem',
        ];

        $rules = [
            'name' => 'required',
            'surname' => 'required',

        ];

        $validator->validate($inputs, $rules);

        self::assertArrayHasKey('required', $property->getValue($validator));
        self::assertInstanceOf(RequiredRule::class, $property->getValue($validator)['required']);
    }

    /* -------------------------------------------------
     * ERRORS
     * -------------------------------------------------
     */

    public function testErrorsFirst(): void
    {
        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required|alpha_chars',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals('The name field is required', $this->validator->errors()->first('name'));
        self::assertEquals('The name field is required', $this->validator->errors()->first());
    }

    public function testErrorsGet(): void
    {
        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required|alpha_chars',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertNull($this->validator->errors()->get());
        self::assertEquals(
            ['The name field is required', 'The name must only contain letters'],
            $this->validator->errors()->get('name')
        );
    }

    public function testErrorsAll(): void
    {
        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required|alpha_chars',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals(
            ['name' => ['The name field is required', 'The name must only contain letters']],
            $this->validator->errors()->all()
        );
    }

    public function testErrorsHas(): void
    {
        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertTrue($this->validator->errors()->has('name'));
    }

    public function testErrorsIsEmpty(): void
    {
        $inputs = [
            'name' => 'merloxx',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertTrue($this->validator->errors()->isEmpty());
    }

    public function testErrorWithBailRuleStopsAfterFirstValidationFail(): void
    {
        $inputs = [
            'name' => '',
            'surname' => '',
        ];

        $rules = [
            'name' => 'bail|required|alpha_chars',
            'surname' => 'required|alpha_chars',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertNull($this->validator->errors()->get());
        self::assertEquals('The name field is required', $this->validator->errors()->first());
        self::assertEquals('The surname field is required', $this->validator->errors()->first('surname'));
    }

    /* -------------------------------------------------
     * ADD RULE
     * -------------------------------------------------
     */

    public function testAddRule(): void
    {
        $this->validator->addRule('banana', new Banana());

        $fields = [
            'name' => 'isBanana',
        ];

        $rules = [
            'name' => 'banana',
        ];

        $this->validator->validate($fields, $rules);

        self::assertFalse($this->validator->isValid());
        self::assertEquals('banana', $this->validator->errors()->first());
    }

    public function testAddRuleThrowsExceptionWhenRuleAlreadyInUse(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator
            ->addRule('banana', new Banana())
            ->addRule('banana', new Banana());
    }

    /* -------------------------------------------------
     * BEFORE VALIDATION HOOK
     * -------------------------------------------------
     */

    public function testBeforeValidationHook(): void
    {
        $this->validator->addBeforeValidationHook(
            function () {
                self::assertTrue($this->validator->isValid());
            }
        );

        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * AFTER VALIDATION HOOK
     * -------------------------------------------------
     */

    public function testAfterValidationHook(): void
    {
        $this->validator->addAfterValidationHook(
            function () {
                self::assertNotEmpty($this->validator->errors());
            }
        );

        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * CUSTOM MESSAGES
     * -------------------------------------------------
     */

    public function testValidateWithCustomMessages(): void
    {
        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required',
        ];

        $customMessages = [
            'required' => $requiredMessage = 'This field is required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals('The name field is required', $this->validator->errors()->first('name'));

        $this->validator->validate($inputs, $rules, $customMessages);

        self::assertEquals($requiredMessage, $this->validator->errors()->first('name'));

        $this->validator->validate($inputs, $rules);

        self::assertEquals('The name field is required', $this->validator->errors()->first('name'));
    }

    public function testValidateWithCustomTranslationFileMessage(): void
    {
        $inputs = [
            'phone_number' => '',
        ];

        $rules = [
            'phone_number' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals(
            'Can I get your number?',
            $this->validator->errors()->first('phone_number')
        );
    }

    /* -------------------------------------------------
     * CUSTOM FIELD NAMES
     * -------------------------------------------------
     */

    public function testWithCustomFieldReplacers(): void
    {
        $inputs = [
            'credit_card' => '',
        ];

        $rules = [
            'credit_card' => 'required',
        ];

        $customFieldNames = [
            'credit_card' => 'credit card number',
        ];

        $this->validator->validate($inputs, $rules, [], $customFieldNames);

        self::assertEquals(
            'The credit card number field is required',
            $this->validator->errors()->first('credit_card')
        );
    }

    public function testWithCustomTranslationFileFieldReplacers(): void
    {
        $inputs = [
            'password_repeat' => '',
        ];

        $rules = [
            'password_repeat' => 'required',
        ];

        $this->validator->validate($inputs, $rules);

        self::assertEquals(
            'The password confirmation field is required',
            $this->validator->errors()->first('password_repeat')
        );
    }

    /* -------------------------------------------------
     * CUSTOM TRANSLATION MESSAGES
     * -------------------------------------------------
     */

    public function testOverwriteExistingWithCustomMessagesFile(): void
    {
        $validator = new Validator(
            'de',
            dirname(__DIR__) . '/TestAssets/translations'
        );

        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'required|min_string:2'
        ];

        $validator->validate($inputs, $rules);
        $messages = $validator->errors()->all();

        self::assertEquals('Dies ist ein Pflichtfeld', $messages['name'][0]);
        self::assertEquals('The name must be at least 2 characters', $messages['name'][1]);
    }

    public function testOverwriteExistingWithCustomMessagesFileAndNamespace(): void
    {
        $validator = new Validator('de', __DIR__ . '/TestAssets/translations', 'fields');

        $inputs = [
            'name' => '',
        ];

        $rules = [
            'name' => 'min_string:2'
        ];

        $validator->validate($inputs, $rules);

        self::assertEquals('Name muss mindestens 2 Zeichen lang sein', $validator->errors()->first('name'));
    }


    /* -------------------------------------------------
     * REQUIRED
     * -------------------------------------------------
     */

    public function testRequired(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'required']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => ['merloxx']], ['name' => 'required']);
        self::assertTrue($this->validator->isValid());

        $count = new class implements Countable {
            public function count(): int
            {
                return 4;
            }
        };

        $this->validator->validate(['name' => $count], ['name' => 'required']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => null], ['name' => 'required']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => ' '], ['name' => 'required']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => []], ['name' => 'required']);
        self::assertFalse($this->validator->isValid());

        $count = new class implements Countable {
            public function count(): int
            {
                return 0;
            }
        };

        $this->validator->validate(['name' => $count], ['name' => 'required']);

        self::assertFalse($this->validator->isValid());
        self::assertEquals('The name field is required', $this->validator->errors()->first('name'));
    }

    /* -------------------------------------------------
     * ACTIVE URL
     * -------------------------------------------------
     */

    public function testActiveUrl(): void
    {
        $this->validator->validate(['url' => 'http://google.com'], ['url' => 'active_url']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['url' => 'https://google.com'], ['url' => 'active_url']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['url' => 'https://google.com/about'], ['url' => 'active_url']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['url' => 'http://nope.niet'], ['url' => 'active_url']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['url' => 'aslsdlks'], ['url' => 'active_url']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['url' => ['aslsdlks']], ['url' => 'active_url']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The url is not a valid URL',
            $this->validator->errors()->first()
        );
    }

    /* -------------------------------------------------
     * ALPHA CHARS
     * -------------------------------------------------
     */

    public function testAlphaChars(): void
    {
        $this->validator->validate(['name' => 'foo'], ['name' => 'alpha_chars']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo123'], ['name' => 'alpha_chars']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => null], ['name' => 'alpha_chars']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * ALPHA DASH
     * -------------------------------------------------
     */

    public function testAlphaDash(): void
    {
        $this->validator->validate(['name' => 'foo_12'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo_bar'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo_12'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo-bar'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo__bar'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo--bar'], ['name' => 'alpha_dash']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo!bar'], ['name' => 'alpha_dash']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => 'foo bar'], ['name' => 'alpha_dash']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must only contain letters, numbers, dashes and underscores',
            $this->validator->errors()->first('name')
        );

        $this->validator->validate(['name' => null], ['name' => 'alpha_dash']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * ALPHA NUM
     * -------------------------------------------------
     */

    public function testAlphaNum(): void
    {
        $this->validator->validate(['name' => 'foo'], ['name' => 'alpha_num']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo123'], ['name' => 'alpha_num']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo-123'], ['name' => 'alpha_num']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => 'foo!123'], ['name' => 'alpha_num']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => 'foo 123'], ['name' => 'alpha_num']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must only contain letters and numbers',
            $this->validator->errors()->first('name')
        );

        $this->validator->validate(['name' => null], ['name' => 'alpha_num']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * ARRAY
     * -------------------------------------------------
     */

    public function testArray(): void
    {
        $this->validator->validate(['name' => ['array']], ['name' => 'array']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => []], ['name' => 'array']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'array'], ['name' => 'array']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => "['array']"], ['name' => 'array']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The name must be an array', $this->validator->errors()->first('name'));

        $this->validator->validate(['name' => null], ['name' => 'array']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * ASCII
     * -------------------------------------------------
     */

    public function testAscii(): void
    {
        $this->validator->validate(['name' => 'foo 123'], ['name' => 'ascii']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'fóó 123'], ['name' => 'ascii']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must only contain ASCII letters',
            $this->validator->errors()->first('name')
        );

        $this->validator->validate(['name' => null], ['name' => 'ascii']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * BETWEEN ARRAY
     * -------------------------------------------------
     */

    public function testBetweenArray(): void
    {
        $this->validator->validate(['items' => [1, 2, 3]], ['items' => 'between_array:1,3']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['items' => [1, 2]], ['items' => 'between_array:1,3']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['items' => []], ['items' => 'between_array:1,3']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['items' => [1]], ['items' => 'between_array:2,3']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The items must have between 2 and 3 items',
            $this->validator->errors()->first('items')
        );

        $this->validator->validate(['items' => null], ['items' => 'between_array:1,3']);
        self::assertFalse($this->validator->isValid());
    }

    public function testBetweenArrayThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['items' => [1]], ['items' => 'between_array']);
    }

    /* -------------------------------------------------
     * BETWEEN NUMBER
     * -------------------------------------------------
     */

    public function testBetweenNumber(): void
    {
        $this->validator->validate(['number' => '100'], ['number' => 'between_number:100,500']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['number' => 500], ['number' => 'between_number:100,500']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['number' => '99'], ['number' => 'between_number:100,500']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['number' => '5'], ['number' => 'between_number:100,500']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The number must be between 100 and 500', $this->validator->errors()->first('number'));

        $this->validator->validate(['number' => null], ['number' => 'between_number:100,500']);
        self::assertFalse($this->validator->isValid());
    }

    public function testBetweenNumberThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['number' => 4], ['number' => 'between_number']);
    }

    /* -------------------------------------------------
     * BETWEEN STRING
     * -------------------------------------------------
     */

    public function testBetweenString(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'between_string:1,7']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'between_string:1,7']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'between_string:10,20']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must be between 10 and 20 characters',
            $this->validator->errors()->first('name')
        );

        $this->validator->validate(['name' => null], ['name' => 'between_string:1,7']);
        self::assertFalse($this->validator->isValid());
    }

    public function testBetweenStringThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'between_string']);
    }

    /* -------------------------------------------------
     * BOOL
     * -------------------------------------------------
     */

    public function testBool(): void
    {
        $this->validator->validate(['check' => true], ['check' => 'bool']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => false], ['check' => 'bool']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => 1], ['check' => 'bool']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => 0], ['check' => 'bool']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => '1'], ['check' => 'bool']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => '0'], ['check' => 'bool']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => 'true'], ['check' => 'bool']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => 'false'], ['check' => 'bool']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => 'yes'], ['check' => 'bool']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => 'no'], ['check' => 'bool']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The check field must be true or false',
            $this->validator->errors()->first('check')
        );

        $this->validator->validate(['check' => null], ['check' => 'bool']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * CHECKED
     * -------------------------------------------------
     */

    public function testCheck(): void
    {
        $this->validator->validate(['check' => 'on'], ['check' => 'checked']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => 'yes'], ['check' => 'checked']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => 1], ['check' => 'checked']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => '1'], ['check' => 'checked']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => true], ['check' => 'checked']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => 'true'], ['check' => 'checked']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['check' => false], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => 'false'], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => 0], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => '0'], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => ''], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['check' => '    '], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The check must be accepted',
            $this->validator->errors()->first('check')
        );

        $this->validator->validate(['check' => null], ['check' => 'checked']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * DATE AFTER
     * -------------------------------------------------
     */

    public function testDateAfter(): void
    {
        $this->validator->validate(['date' => date('d.m.Y')], ['date' => 'date_after:yesterday']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_after:11.09.2023']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTime()], ['date' => 'date_after:yesterday']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTimeImmutable()], ['date' => 'date_after:yesterday']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['from' => '11.09.2023', 'to' => '12.09.2023'], ['to' => 'date_after:from']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '16:00'], ['date' => 'date_after:15:00']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '15:00'], ['date' => 'date_after:15:00']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['from' => '11.09.2023', 'to' => '12.09.2023'], ['to' => 'date_after:to']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_after:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => 'foo'], ['date' => 'date_after:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => ['2023-09-12']], ['date' => 'date_after:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        self::assertEquals(
            'The date must be a date after 2023-09-12',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['date' => null], ['date' => 'date_after:2023-09-12']);
        self::assertFalse($this->validator->isValid());
    }

    public function testDateAfterThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_after']);
    }

    /* -------------------------------------------------
     * DATE BEFORE
     * -------------------------------------------------
     */

    public function testDateBefore(): void
    {
        $this->validator->validate(['date' => date('d.m.Y')], ['date' => 'date_before:tomorrow']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_before:13.09.2023']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTime()], ['date' => 'date_before:tomorrow']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTimeImmutable()], ['date' => 'date_before:tomorrow']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['from' => '11.09.2023', 'to' => '12.09.2023'], ['from' => 'date_before:to']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '14:00'], ['date' => 'date_before:15:00']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '14:00'], ['date' => 'date_after:15:00']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['from' => '11.09.2023', 'to' => '12.09.2023'], ['from' => 'date_before:from']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_before:2023-09-11']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => 'foo'], ['date' => 'date_before:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => ['2023-09-12']], ['date' => 'date_before:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        self::assertEquals(
            'The date must be a date before 2023-09-12',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['date' => null], ['date' => 'date_before:2023-09-12']);
        self::assertFalse($this->validator->isValid());
    }

    public function testDateBeforeThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_before']);
    }

    /* -------------------------------------------------
     * DATE EQUALS
     * -------------------------------------------------
     */

    public function testDateEquals(): void
    {
        $this->validator->validate(['date' => date('d.m.Y')], ['date' => 'date_equals:today']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_equals:12.09.2023']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTime('today')], ['date' => 'date_equals:today']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTimeImmutable('today')], ['date' => 'date_equals:today']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['from' => '12.09.2023', 'to' => '12.09.2023'], ['from' => 'date_equals:to']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '16:00'], ['date' => 'date_equals:16:00']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '14:00'], ['date' => 'date_equals:15:00']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_equals:2023-09-11']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => 'foo'], ['date' => 'date_equals:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => ['2023-09-12']], ['date' => 'date_equals:2023-09-12']);
        self::assertFalse($this->validator->isValid());

        self::assertEquals(
            'The date must be equal to 2023-09-12',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['date' => null], ['date' => 'date_equals:2023-09-12']);
        self::assertFalse($this->validator->isValid());
    }

    public function testDateEqualsThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['date' => '2023-09-12'], ['date' => 'date_equals']);
    }

    /* -------------------------------------------------
     * DATE FORMAT
     * -------------------------------------------------
     */

    public function testDateFormat(): void
    {
        $this->validator->validate(['date' => '13.09.2022'], ['date' => 'date_format:d.m.Y']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '2023-08'], ['date' => 'date_format:Y-m']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '2023-08-31T13:02:50Atlantic/Azores'],
            ['date' => 'date_format:Y-m-d\TH:i:se']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '2023-08-31T13:02:50Z'],
            ['date' => 'date_format:Y-m-d\TH:i:sT']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '2023-08-31T13:02:50+0000'],
            ['date' => 'date_format:Y-m-d\TH:i:sO']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '2023-08-31T13:02:50+00:30'],
            ['date' => 'date_format:Y-m-d\TH:i:sP']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '13.09.2022 09:41:00'],
            ['date' => 'date_format:d.m.Y H:i:s']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '09:41:00'],
            ['date' => 'date_format:H:i:s']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '09:41'],
            ['date' => 'date_format:H:i']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['date' => '13.09.2022'],
            ['date' => 'date_format:Y-m-d']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['date' => '32022-09-13'],
            ['date' => 'date_format:Y-m-d']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['date' => '13.09.22'],
            ['date' => 'date_format:d.m.Y']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['date' => 'foo'],
            ['date' => 'date_format:Y-m-d']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['date' => '13.09.2022 09:41:00'],
            ['date' => 'date_format:H:i:s']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['date' => ['13.09.2022']],
            ['date' => 'date_format:Y-m-d']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['date' => '09:41:00'],
            ['date' => 'date_format:H:i']
        );
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The date does not match the format H:i',
            $this->validator->errors()->first('date')
        );

        $this->validator->validate(
            ['date' => null],
            ['date' => 'date_format:Y-m-d']
        );
        self::assertFalse($this->validator->isValid());
    }

    public function testDateFormatThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['date' => '2022-09-13'], ['date' => 'date_format']);
    }

    /* -------------------------------------------------
     * DATE TIME
     * -------------------------------------------------
     */

    public function testDateTime(): void
    {
        $this->validator->validate(['date' => '13.09.2022'], ['date' => 'date_time']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => '2023-08-31 10:54:30'], ['date' => 'date_time']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTime()], ['date' => 'date_time']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => new DateTimeImmutable()], ['date' => 'date_time']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['date' => ['13.09.2022']], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => ''], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => 'foo'], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => '2023-08-32'], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => '2023-08-31 99:99:99'], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['date' => '1325376000'], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The date is not a valid date', $this->validator->errors()->first('date'));

        $this->validator->validate(['date' => null], ['date' => 'date_time']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * DIFFERENT
     * -------------------------------------------------
     */

    public function testDifferent(): void
    {
        $this->validator->validate(
            [
                'answer1' => 'foo',
                'answer2' => 'bar',
            ],
            [
                'answer2' => 'different:answer1',
            ]
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            [
                'answer1' => 'foo',
            ],
            [
                'answer1' => 'different:answer2',
            ]
        );
        self::assertFalse($this->validator->isValid());


        $this->validator->validate(
            [
                'answer1' => 'foo',
                'answer2' => 'foo',
            ],
            [
                'answer2' => 'different:answer1',
            ]
        );
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The answer2 and answer1 must be different',
            $this->validator->errors()->first('answer2')
        );

        $this->validator->validate(
            [
                'answer1' => null,
            ],
            [
                'answer1' => 'different:answer2',
            ]
        );
        self::assertFalse($this->validator->isValid());
    }

    public function testDifferentThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['answer1' => 'foo'], ['answer1' => 'different']);
    }

    /* -------------------------------------------------
     * DIGITS
     * -------------------------------------------------
     */

    public function testDigits(): void
    {
        $this->validator->validate(['pin' => '1234'], ['pin' => 'digits:4']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['pin' => 'foo'], ['pin' => 'digits:4']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The pin must be 4 digits',
            $this->validator->errors()->first('pin')
        );

        $this->validator->validate(['pin' => null], ['pin' => 'digits:4']);
        self::assertFalse($this->validator->isValid());
    }

    public function testDigitsThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['pin' => '1234'], ['pin' => 'digits']);
    }

    /* -------------------------------------------------
     * EMAIL
     * -------------------------------------------------
     */

    public function testEmail(): void
    {
        $this->validator->validate(['mail' => 'merloxx@zaphyr.org'], ['mail' => 'email']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            [
                'mail' => new class {
                    public function __toString(): string
                    {
                        return 'merloxx@zaphyr.org';
                    }
                },
            ],
            [
                'mail' => 'email',
            ]
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'merlöxx@zaphyr.org'], ['mail' => 'email']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'foo'], ['mail' => 'email']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['mail' => ['foo']], ['mail' => 'email']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            [
                'mail' => new class {
                },
            ],
            [
                'mail' => 'email',
            ]
        );
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The mail must be a valid email address',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['mail' => null], ['mail' => 'email']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEmailDns(): void
    {
        $this->validator->validate(['mail' => 'merloxx@zaphyr.org'], ['mail' => 'email:dns']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'foo@bar'], ['mail' => 'email:dns']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEmailFilter(): void
    {
        $this->validator->validate(['mail' => 'merloxx@zaphyr.org'], ['mail' => 'email:filter']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'foo@bar'], ['mail' => 'email:filter']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['mail' => 'merlöxx@zäphyr.org'], ['mail' => 'email:filter']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEmailFilterWithUnicode(): void
    {
        $this->validator->validate(['mail' => 'merlöxx@zaphyr.org'], ['mail' => 'email:filter_unicode']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'merlöxx@zäphyr.org'], ['mail' => 'email:filter_unicode']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEmailSpoof(): void
    {
        $this->validator->validate(['mail' => 'merloxx@zaphyr.org'], ['mail' => 'email:spoof']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'Кириллицаlatin漢字ひらがな"."カタカナ'], ['mail' => 'email:spoof']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEmailStrict(): void
    {
        $this->validator->validate(['mail' => 'merloxx@zaphyr.com'], ['mail' => 'email:strict']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mail' => 'merloxx@zaphyr'], ['mail' => 'email:strict']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEmailWithAllParameters(): void
    {
        $this->validator->validate(['mail' => 'merloxx@zaphyr.org'], ['mail' => 'email:dns,filter,spoof,check']);
        self::assertTrue($this->validator->isValid());
    }

    /* -------------------------------------------------
     * ENDS WITH
     * -------------------------------------------------
     */

    public function testEndsWith(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_with:x']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_with:e,x']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_with:X']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must end with one of the following: X',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['name' => null], ['name' => 'ends_with:e,x']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEndsWithThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_with']);
    }

    /* -------------------------------------------------
     * ENDS WITHOUT
     * -------------------------------------------------
     */

    public function testEndsWithout(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_without:m']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_without:e,X']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_without:x,e']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must not end with one of the following: x, e',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['name' => null], ['name' => 'ends_without:x,e']);
        self::assertFalse($this->validator->isValid());
    }

    public function testEndsWithoutThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'ends_without']);
    }

    /* -------------------------------------------------
     * INTEGER
     * -------------------------------------------------
     */

    public function testInteger(): void
    {
        $this->validator->validate(['pin' => 1234], ['pin' => 'integer']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['pin' => '1234'], ['pin' => 'integer']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['pin' => '123c4f'], ['pin' => 'integer']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The pin must be an integer',
            $this->validator->errors()->first('pin')
        );

        $this->validator->validate(['pin' => null], ['pin' => 'integer']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * IP
     * -------------------------------------------------
     */

    public function testIp(): void
    {
        $this->validator->validate(['server' => '1.160.10.240'], ['server' => 'ip']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['server' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], ['server' => 'ip']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['server' => '123c4f'], ['server' => 'ip']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The server must be a valid IP address',
            $this->validator->errors()->first('server')
        );

        $this->validator->validate(['server' => null], ['server' => 'ip']);
        self::assertFalse($this->validator->isValid());
    }


    /* -------------------------------------------------
     * IPv4
     * -------------------------------------------------
     */

    public function testIpv4(): void
    {
        $this->validator->validate(['server' => '1.160.10.240'], ['server' => 'ipv4']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['server' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], ['server' => 'ipv4']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The server must be a valid IPv4 address',
            $this->validator->errors()->first('server')
        );

        $this->validator->validate(['server' => null], ['server' => 'ipv4']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * IPv6
     * -------------------------------------------------
     */

    public function testIpv6(): void
    {
        $this->validator->validate(['server' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], ['server' => 'ipv6']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['server' => '1.160.10.240'], ['server' => 'ipv6']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The server must be a valid IPv6 address',
            $this->validator->errors()->first('server')
        );

        $this->validator->validate(['server' => null], ['server' => 'ipv6']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * JSON
     * -------------------------------------------------
     */

    public function testJson(): void
    {
        $this->validator->validate(['data' => '{"foo": "bar"}'], ['data' => 'json']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['data' => '[]'], ['data' => 'json']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['data' => '{"foo": "bar}'], ['data' => 'json']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate([
            'data' => new class {
            }
        ], ['data' => 'json']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['data' => 'foo'], ['data' => 'json']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The data must be a valid JSON string',
            $this->validator->errors()->first('data')
        );

        $this->validator->validate(['data' => null], ['data' => 'json']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * MAC
     * -------------------------------------------------
     */

    public function testMac(): void
    {
        $this->validator->validate(['mac' => '00:00:00:00:00:00'], ['mac' => 'mac']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mac' => '00-00-00-00-00-00'], ['mac' => 'mac']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['mac' => ''], ['mac' => 'mac']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * MAX ARRAY
     * -------------------------------------------------
     */

    public function testMaxArray(): void
    {
        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'max_array:2']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'max_array:1']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['names' => ''], ['names' => 'max_array:2']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The names must not have more than 2 items',
            $this->validator->errors()->first('names')
        );

        $this->validator->validate(['names' => null], ['names' => 'max_array:1']);
        self::assertFalse($this->validator->isValid());
    }

    public function testMaxArrayThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'max_array']);
    }

    /* -------------------------------------------------
     * MAX NUMBER
     * -------------------------------------------------
     */

    public function testMaxNumber(): void
    {
        $this->validator->validate(['one' => 5, 'two' => '50'], ['one' => 'max_number:5', 'two' => 'max_number:100']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['number' => 6], ['number' => 'max_number:5']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The number must not be greater than 5', $this->validator->errors()->first());

        $this->validator->validate(['number' => null], ['number' => 'max_number:5']);
        self::assertFalse($this->validator->isValid());
    }

    public function testMaxNumberThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['number' => 4], ['number' => 'max_number']);
    }

    /* -------------------------------------------------
    * MAX STRING
    * -------------------------------------------------
    */

    public function testMaxString(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'max_string:7']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'max_string:6']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The name must not be greater than 6 characters', $this->validator->errors()->first());

        $this->validator->validate(['name' => null], ['name' => 'max_string:6']);
        self::assertFalse($this->validator->isValid());
    }

    public function testMaxStringThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'max_string']);
    }

    /* -------------------------------------------------
     * MIN ARRAY
     * -------------------------------------------------
     */

    public function testMinArray(): void
    {
        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'min_array:2']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'min_array:3']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['names' => []], ['names' => 'min_array:2']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['names' => ''], ['names' => 'min_array:2']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The names must have at least 2 items',
            $this->validator->errors()->first('names')
        );

        $this->validator->validate(['names' => null], ['names' => 'min_array:2']);
        self::assertFalse($this->validator->isValid());
    }

    public function testMinArrayThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'min_array']);
    }

    /* -------------------------------------------------
     * MIN NUMBER
     * -------------------------------------------------
     */

    public function testMinNumber(): void
    {
        $this->validator->validate(['one' => 5, 'two' => '50'], ['one' => 'min_number:5', 'two' => 'min_number:5']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['number' => 4], ['number' => 'min_number:5']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The number must be at least 5', $this->validator->errors()->first());

        $this->validator->validate(['number' => null], ['number' => 'min_number:5']);
        self::assertFalse($this->validator->isValid());
    }

    public function testMinNumberThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['number' => 4], ['number' => 'min_number']);
    }

    /* -------------------------------------------------
     * MIN STRING
     * -------------------------------------------------
     */

    public function testMinString(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'min_string:7']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'min_string:8']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The name must be at least 8 characters', $this->validator->errors()->first());

        $this->validator->validate(['name' => null], ['name' => 'min_string:8']);
        self::assertFalse($this->validator->isValid());
    }

    public function testMinStringThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'min_string']);
    }

    /* -------------------------------------------------
     * NOT REGEX
     * -------------------------------------------------
     */

    public function testNotRegex(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'not_regex:/[yz]/i']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'foo bar'], ['name' => 'not_regex:/x{3,}/i']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => ['merloxx']], ['name' => 'not_regex:/[xyz]/i']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'not_regex:/[xyz]/i']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name format is invalid',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['name' => null], ['name' => 'not_regex:/[xyz]/i']);
        self::assertFalse($this->validator->isValid());
    }

    public function testNotRegexThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'not_regex']);
    }

    /* -------------------------------------------------
     * NUMBER
     * -------------------------------------------------
     */

    public function testNumber(): void
    {
        $this->validator->validate(['phone' => '1234'], ['phone' => 'number']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['phone' => '12.34'], ['phone' => 'number']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['phone' => '-1234'], ['phone' => 'number']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['phone' => '123abc'], ['phone' => 'number']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['phone' => 'abc'], ['phone' => 'number']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The phone must be a number',
            $this->validator->errors()->first('phone')
        );

        $this->validator->validate(['phone' => null], ['phone' => 'number']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * REGEX
     * -------------------------------------------------
     */

    public function testRegex(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'regex:/^[a-z]+$/i']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'a,b'], ['name' => 'regex:/^a,b$/i']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => '12'], ['name' => 'regex:/^12$/i']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 12], ['name' => 'regex:/^12$/i']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx123'], ['name' => 'regex:/^[a-z]+$/i']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name format is invalid',
            $this->validator->errors()->first('name')
        );

        $this->validator->validate(['name' => null], ['name' => 'regex:/^12$/i']);
        self::assertFalse($this->validator->isValid());
    }

    public function testRegexThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'regex']);
    }

    /* -------------------------------------------------
     * SAME
     * -------------------------------------------------
     */

    public function testSame(): void
    {
        $this->validator->validate(
            ['password' => 'secret', 'password_confirm' => 'secret'],
            ['password_confirm' => 'same:password']
        );
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(
            ['password' => 'secret', 'password_confirm' => 'secret2'],
            ['password_confirm' => 'same:password']
        );
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The password confirm and password must match',
            $this->validator->errors()->first()
        );

        $this->validator->validate(
            ['password' => null, 'password_confirm' => 'secret2'],
            ['password_confirm' => 'same:password']
        );
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(
            ['password' => 'secret', 'password_confirm' => null],
            ['password_confirm' => 'same:password']
        );
        self::assertFalse($this->validator->isValid());
    }

    public function testSameThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['password_confirm' => 'secret'], ['password_confirm' => 'same']);
    }

    /* -------------------------------------------------
    * SIZE ARRAY
    * -------------------------------------------------
    */

    public function testSizeArray(): void
    {
        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'size_array:2']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'size_array:1']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['names' => ''], ['names' => 'size_array:2']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The names must contain 2 items',
            $this->validator->errors()->first('names')
        );

        $this->validator->validate(['names' => null], ['names' => 'size_array:2']);
        self::assertFalse($this->validator->isValid());
    }

    public function testSizeArrayThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['names' => ['merloxx', 'john']], ['names' => 'size_array']);
    }

    /* -------------------------------------------------
     * SIZE NUMBER
     * -------------------------------------------------
     */

    public function testSizeNumber(): void
    {
        $this->validator->validate(['one' => 5], ['one' => 'size_number:5']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['number' => 6], ['number' => 'size_number:5']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The number must be 5', $this->validator->errors()->first());

        $this->validator->validate(['number' => null], ['number' => 'size_number:5']);
        self::assertFalse($this->validator->isValid());
    }

    public function testSizeNumberThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['number' => 4], ['number' => 'size_number']);
    }

    /* -------------------------------------------------
    * SIZE STRING
    * -------------------------------------------------
    */

    public function testSizeString(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'size_string:7']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'size_string:6']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals('The name must be 6 characters', $this->validator->errors()->first());

        $this->validator->validate(['name' => null], ['name' => 'size_string:6']);
        self::assertFalse($this->validator->isValid());
    }

    public function testSizeStringThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'size_string']);
    }

    /* -------------------------------------------------
     * STARTS WITH
     * -------------------------------------------------
     */

    public function testStartsWith(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_with:m']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_with:e,m']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_with:M']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must start with one of the following: M',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['name' => null], ['name' => 'starts_with:M']);
        self::assertFalse($this->validator->isValid());
    }

    public function testStartWithThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_with']);
    }

    /* -------------------------------------------------
     * STARTS WITHOUT
     * -------------------------------------------------
     */

    public function testStartsWithout(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_without:x']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_without:e,M']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_without:m,M']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must not start with one of the following: m, M',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['name' => null], ['name' => 'starts_without:m,M']);
        self::assertFalse($this->validator->isValid());
    }

    public function testStartsWithoutThrowsExceptionOnInvalidRequiredParameterCount(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'merloxx'], ['name' => 'starts_without']);
    }

    /* -------------------------------------------------
     * STRING
     * -------------------------------------------------
     */

    public function testString(): void
    {
        $this->validator->validate(['name' => 'merloxx'], ['name' => 'string']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['name' => ['merloxx']], ['name' => 'string']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The name must be a string',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['name' => null], ['name' => 'string']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * TIME ZONE
     * -------------------------------------------------
     */

    public function testTimeZone(): void
    {
        $this->validator->validate(['zone' => 'UTC'], ['zone' => 'timezone']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['zone' => 'GMT'], ['zone' => 'timezone']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['zone' => 'Europe/Berlin'], ['zone' => 'timezone']);
        self::assertTrue($this->validator->isValid());

        $this->validator->validate(['zone' => 'Germany'], ['zone' => 'timezone']);
        self::assertFalse($this->validator->isValid());

        $this->validator->validate(['zone' => 'not a timezone'], ['zone' => 'timezone']);
        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The zone must be a valid time zone',
            $this->validator->errors()->first()
        );

        $this->validator->validate(['zone' => null], ['zone' => 'timezone']);
        self::assertFalse($this->validator->isValid());
    }

    /* -------------------------------------------------
     * URL
     * -------------------------------------------------
     */

    /**
     * @dataProvider validUrlsDataProvider
     *
     * @param mixed $url
     */
    public function testValidateUrlWithValidUrls(mixed $url): void
    {
        $this->validator->validate(['url' => $url], ['url' => 'url']);
        self::assertTrue($this->validator->isValid());
    }

    /**
     * @dataProvider invalidUrlsDataProvider
     *
     * @param mixed $url
     */
    public function testValidateUrlWithInvalidUrls(mixed $url): void
    {
        $this->validator->validate(['url' => $url], ['url' => 'url']);

        self::assertFalse($this->validator->isValid());
        self::assertEquals(
            'The url is not a valid URL',
            $this->validator->errors()->first()
        );
    }

    /**
     * @return array<string[]>
     */
    public static function validUrlsDataProvider(): array
    {
        return [
            ['aaa://fully.qualified.domain/path'],
            ['aaas://fully.qualified.domain/path'],
            ['about://fully.qualified.domain/path'],
            ['acap://fully.qualified.domain/path'],
            ['acct://fully.qualified.domain/path'],
            ['acr://fully.qualified.domain/path'],
            ['adiumxtra://fully.qualified.domain/path'],
            ['afp://fully.qualified.domain/path'],
            ['afs://fully.qualified.domain/path'],
            ['aim://fully.qualified.domain/path'],
            ['apt://fully.qualified.domain/path'],
            ['attachment://fully.qualified.domain/path'],
            ['aw://fully.qualified.domain/path'],
            ['barion://fully.qualified.domain/path'],
            ['beshare://fully.qualified.domain/path'],
            ['bitcoin://fully.qualified.domain/path'],
            ['blob://fully.qualified.domain/path'],
            ['bolo://fully.qualified.domain/path'],
            ['callto://fully.qualified.domain/path'],
            ['cap://fully.qualified.domain/path'],
            ['chrome://fully.qualified.domain/path'],
            ['chrome-extension://fully.qualified.domain/path'],
            ['cid://fully.qualified.domain/path'],
            ['coap://fully.qualified.domain/path'],
            ['coaps://fully.qualified.domain/path'],
            ['com-eventbrite-attendee://fully.qualified.domain/path'],
            ['content://fully.qualified.domain/path'],
            ['crid://fully.qualified.domain/path'],
            ['cvs://fully.qualified.domain/path'],
            ['data://fully.qualified.domain/path'],
            ['dav://fully.qualified.domain/path'],
            ['dict://fully.qualified.domain/path'],
            ['dlna-playcontainer://fully.qualified.domain/path'],
            ['dlna-playsingle://fully.qualified.domain/path'],
            ['dns://fully.qualified.domain/path'],
            ['dntp://fully.qualified.domain/path'],
            ['dtn://fully.qualified.domain/path'],
            ['dvb://fully.qualified.domain/path'],
            ['ed2k://fully.qualified.domain/path'],
            ['example://fully.qualified.domain/path'],
            ['facetime://fully.qualified.domain/path'],
            ['fax://fully.qualified.domain/path'],
            ['feed://fully.qualified.domain/path'],
            ['feedready://fully.qualified.domain/path'],
            ['file://fully.qualified.domain/path'],
            ['filesystem://fully.qualified.domain/path'],
            ['finger://fully.qualified.domain/path'],
            ['fish://fully.qualified.domain/path'],
            ['ftp://fully.qualified.domain/path'],
            ['geo://fully.qualified.domain/path'],
            ['gg://fully.qualified.domain/path'],
            ['git://fully.qualified.domain/path'],
            ['gizmoproject://fully.qualified.domain/path'],
            ['go://fully.qualified.domain/path'],
            ['gopher://fully.qualified.domain/path'],
            ['gtalk://fully.qualified.domain/path'],
            ['h323://fully.qualified.domain/path'],
            ['ham://fully.qualified.domain/path'],
            ['hcp://fully.qualified.domain/path'],
            ['http://fully.qualified.domain/path'],
            ['https://fully.qualified.domain/path'],
            ['iax://fully.qualified.domain/path'],
            ['icap://fully.qualified.domain/path'],
            ['icon://fully.qualified.domain/path'],
            ['im://fully.qualified.domain/path'],
            ['imap://fully.qualified.domain/path'],
            ['info://fully.qualified.domain/path'],
            ['iotdisco://fully.qualified.domain/path'],
            ['ipn://fully.qualified.domain/path'],
            ['ipp://fully.qualified.domain/path'],
            ['ipps://fully.qualified.domain/path'],
            ['irc://fully.qualified.domain/path'],
            ['irc6://fully.qualified.domain/path'],
            ['ircs://fully.qualified.domain/path'],
            ['iris://fully.qualified.domain/path'],
            ['iris.beep://fully.qualified.domain/path'],
            ['iris.lwz://fully.qualified.domain/path'],
            ['iris.xpc://fully.qualified.domain/path'],
            ['iris.xpcs://fully.qualified.domain/path'],
            ['itms://fully.qualified.domain/path'],
            ['jabber://fully.qualified.domain/path'],
            ['jar://fully.qualified.domain/path'],
            ['jms://fully.qualified.domain/path'],
            ['keyparc://fully.qualified.domain/path'],
            ['lastfm://fully.qualified.domain/path'],
            ['ldap://fully.qualified.domain/path'],
            ['ldaps://fully.qualified.domain/path'],
            ['magnet://fully.qualified.domain/path'],
            ['mailserver://fully.qualified.domain/path'],
            ['mailto://fully.qualified.domain/path'],
            ['maps://fully.qualified.domain/path'],
            ['market://fully.qualified.domain/path'],
            ['message://fully.qualified.domain/path'],
            ['mid://fully.qualified.domain/path'],
            ['mms://fully.qualified.domain/path'],
            ['modem://fully.qualified.domain/path'],
            ['ms-help://fully.qualified.domain/path'],
            ['ms-settings://fully.qualified.domain/path'],
            ['ms-settings-airplanemode://fully.qualified.domain/path'],
            ['ms-settings-bluetooth://fully.qualified.domain/path'],
            ['ms-settings-camera://fully.qualified.domain/path'],
            ['ms-settings-cellular://fully.qualified.domain/path'],
            ['ms-settings-cloudstorage://fully.qualified.domain/path'],
            ['ms-settings-emailandaccounts://fully.qualified.domain/path'],
            ['ms-settings-language://fully.qualified.domain/path'],
            ['ms-settings-location://fully.qualified.domain/path'],
            ['ms-settings-lock://fully.qualified.domain/path'],
            ['ms-settings-nfctransactions://fully.qualified.domain/path'],
            ['ms-settings-notifications://fully.qualified.domain/path'],
            ['ms-settings-power://fully.qualified.domain/path'],
            ['ms-settings-privacy://fully.qualified.domain/path'],
            ['ms-settings-proximity://fully.qualified.domain/path'],
            ['ms-settings-screenrotation://fully.qualified.domain/path'],
            ['ms-settings-wifi://fully.qualified.domain/path'],
            ['ms-settings-workplace://fully.qualified.domain/path'],
            ['msnim://fully.qualified.domain/path'],
            ['msrp://fully.qualified.domain/path'],
            ['msrps://fully.qualified.domain/path'],
            ['mtqp://fully.qualified.domain/path'],
            ['mumble://fully.qualified.domain/path'],
            ['mupdate://fully.qualified.domain/path'],
            ['mvn://fully.qualified.domain/path'],
            ['news://fully.qualified.domain/path'],
            ['nfs://fully.qualified.domain/path'],
            ['ni://fully.qualified.domain/path'],
            ['nih://fully.qualified.domain/path'],
            ['nntp://fully.qualified.domain/path'],
            ['notes://fully.qualified.domain/path'],
            ['oid://fully.qualified.domain/path'],
            ['opaquelocktoken://fully.qualified.domain/path'],
            ['pack://fully.qualified.domain/path'],
            ['palm://fully.qualified.domain/path'],
            ['paparazzi://fully.qualified.domain/path'],
            ['pkcs11://fully.qualified.domain/path'],
            ['platform://fully.qualified.domain/path'],
            ['pop://fully.qualified.domain/path'],
            ['pres://fully.qualified.domain/path'],
            ['prospero://fully.qualified.domain/path'],
            ['proxy://fully.qualified.domain/path'],
            ['psyc://fully.qualified.domain/path'],
            ['query://fully.qualified.domain/path'],
            ['redis://fully.qualified.domain/path'],
            ['rediss://fully.qualified.domain/path'],
            ['reload://fully.qualified.domain/path'],
            ['res://fully.qualified.domain/path'],
            ['resource://fully.qualified.domain/path'],
            ['rmi://fully.qualified.domain/path'],
            ['rsync://fully.qualified.domain/path'],
            ['rtmfp://fully.qualified.domain/path'],
            ['rtmp://fully.qualified.domain/path'],
            ['rtsp://fully.qualified.domain/path'],
            ['rtsps://fully.qualified.domain/path'],
            ['rtspu://fully.qualified.domain/path'],
            ['s3://fully.qualified.domain/path'],
            ['secondlife://fully.qualified.domain/path'],
            ['service://fully.qualified.domain/path'],
            ['session://fully.qualified.domain/path'],
            ['sftp://fully.qualified.domain/path'],
            ['sgn://fully.qualified.domain/path'],
            ['shttp://fully.qualified.domain/path'],
            ['sieve://fully.qualified.domain/path'],
            ['sip://fully.qualified.domain/path'],
            ['sips://fully.qualified.domain/path'],
            ['skype://fully.qualified.domain/path'],
            ['smb://fully.qualified.domain/path'],
            ['sms://fully.qualified.domain/path'],
            ['smtp://fully.qualified.domain/path'],
            ['snews://fully.qualified.domain/path'],
            ['snmp://fully.qualified.domain/path'],
            ['soap.beep://fully.qualified.domain/path'],
            ['soap.beeps://fully.qualified.domain/path'],
            ['soldat://fully.qualified.domain/path'],
            ['spotify://fully.qualified.domain/path'],
            ['ssh://fully.qualified.domain/path'],
            ['steam://fully.qualified.domain/path'],
            ['stun://fully.qualified.domain/path'],
            ['stuns://fully.qualified.domain/path'],
            ['submit://fully.qualified.domain/path'],
            ['svn://fully.qualified.domain/path'],
            ['tag://fully.qualified.domain/path'],
            ['teamspeak://fully.qualified.domain/path'],
            ['tel://fully.qualified.domain/path'],
            ['teliaeid://fully.qualified.domain/path'],
            ['telnet://fully.qualified.domain/path'],
            ['tftp://fully.qualified.domain/path'],
            ['things://fully.qualified.domain/path'],
            ['thismessage://fully.qualified.domain/path'],
            ['tip://fully.qualified.domain/path'],
            ['tn3270://fully.qualified.domain/path'],
            ['turn://fully.qualified.domain/path'],
            ['turns://fully.qualified.domain/path'],
            ['tv://fully.qualified.domain/path'],
            ['udp://fully.qualified.domain/path'],
            ['unreal://fully.qualified.domain/path'],
            ['urn://fully.qualified.domain/path'],
            ['ut2004://fully.qualified.domain/path'],
            ['vemmi://fully.qualified.domain/path'],
            ['ventrilo://fully.qualified.domain/path'],
            ['videotex://fully.qualified.domain/path'],
            ['view-source://fully.qualified.domain/path'],
            ['wais://fully.qualified.domain/path'],
            ['webcal://fully.qualified.domain/path'],
            ['ws://fully.qualified.domain/path'],
            ['wss://fully.qualified.domain/path'],
            ['wtai://fully.qualified.domain/path'],
            ['wyciwyg://fully.qualified.domain/path'],
            ['xcon://fully.qualified.domain/path'],
            ['xcon-userid://fully.qualified.domain/path'],
            ['xfire://fully.qualified.domain/path'],
            ['xmlrpc.beep://fully.qualified.domain/path'],
            ['xmlrpc.beeps://fully.qualified.domain/path'],
            ['xmpp://fully.qualified.domain/path'],
            ['xri://fully.qualified.domain/path'],
            ['ymsgr://fully.qualified.domain/path'],
            ['z39.50://fully.qualified.domain/path'],
            ['z39.50r://fully.qualified.domain/path'],
            ['z39.50s://fully.qualified.domain/path'],
            ['http://a.pl'],
            ['http://localhost/url.php'],
            ['http://local.dev'],
            ['http://google.com'],
            ['http://www.google.com'],
            ['https://google.com'],
            ['http://illuminate.dev'],
            ['http://localhost'],
            ['https://zaphyr.org/?'],
            ['http://президент.рф/'],
            ['http://스타벅스코리아.com'],
            ['http://xn--d1abbgf6aiiy.xn--p1ai/'],
            ['https://zaphyr.org?'],
            ['https://zaphyr.org?q=1'],
            ['https://zaphyr.org/?q=1'],
            ['https://zaphyr.org#'],
            ['https://zaphyr.org#fragment'],
            ['https://zaphyr.org/#fragment'],
        ];
    }

    /**
     * @return array<array<mixed>>
     */
    public static function invalidUrlsDataProvider(): array
    {
        return [
            [null],
            [[]],
            ['aslsdlks'],
            ['google.com'],
            ['://google.com'],
            ['http ://google.com'],
            ['http:/google.com'],
            ['http://goog_le.com'],
            ['http://google.com::aa'],
            ['http://google.com:aa'],
            ['http://127.0.0.1:aa'],
            ['http://[::1'],
            ['foo://bar'],
            ['javascript://test%0Alert(321)'],
        ];
    }

    public function testValidatorThrowsExceptionOnInvalidRuleName(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'John Doe'], ['name' => 'invalidRule']);
    }

    public function testValidatorThrowsExceptionOnEmptyRules(): void
    {
        $this->expectException(ValidatorException::class);

        $this->validator->validate(['name' => 'John Doe'], ['name' => '']);
    }
}
