<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use Codeception\Test\Unit;
use craft\elements\User;
use craft\helpers\Cp;
use craft\web\twig\TemplateLoaderException;
use crafttests\fixtures\SitesFixture;
use UnitTester;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the CP Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class CpHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class
            ],
        ];
    }

    /**
     *
     */
    public function testElementHtml()
    {
        /** @var User $user */
        $user = User::findOne(1);
        self::assertInstanceOf(User::class, $user);

        $indexHtml = Cp::elementHtml($user);
        $fieldHtml = Cp::elementHtml($user, 'field', Cp::ELEMENT_SIZE_SMALL, 'myFieldName');

        // field
        self::assertStringContainsString('removable', $fieldHtml);
        self::assertStringContainsString('name="myFieldName[]"', $fieldHtml);

        // status
        self::assertStringContainsString('<span class="status', $indexHtml);
        self::assertStringNotContainsString('<span class="status', Cp::elementHtml($user, 'index', Cp::ELEMENT_SIZE_SMALL, null, false));

        // thumb
        self::assertStringContainsString('elementthumb', $indexHtml);
        self::assertStringNotContainsString('elementthumb', Cp::elementHtml($user, 'index', Cp::ELEMENT_SIZE_SMALL, null, true, false));

        // label
        self::assertStringContainsString('<div class="label">', $indexHtml);
        self::assertStringNotContainsString('<div class="label">', Cp::elementHtml($user, 'index', Cp::ELEMENT_SIZE_SMALL, null, true, true, false));

        // errors
        self::assertStringNotContainsString('error', $indexHtml);
        $user->addError('foo', 'bad error');
        self::assertStringContainsString('error', Cp::elementHtml($user));
        $user->clearErrors();

        // trashed
        self::assertStringNotContainsString('data-trashed', $indexHtml);
        $user->trashed = true;
        self::assertStringContainsString('data-trashed', Cp::elementHtml($user));
        $user->trashed = false;

        $edition = Craft::$app->getEdition();
        Craft::$app->setEdition(Craft::Pro);

        // draft
        self::assertStringNotContainsString('draftId', $indexHtml);
        $user->draftId = 1;
        self::assertStringContainsString('draftId', Cp::elementHtml($user));
        $user->draftId = null;

        // revision
        self::assertStringNotContainsString('revisionId', $indexHtml);
        $user->revisionId = 1;
        self::assertStringContainsString('revisionId', Cp::elementHtml($user));
        $user->revisionId = null;

        Craft::$app->setEdition($edition);
    }

    /**
     *
     */
    public function testFieldHtml()
    {
        self::assertStringContainsString('<div class="input ltr"><input></div>', Cp::fieldHtml('<input>'));
        self::assertStringContainsString('<label id="id-label" for="id">Label</label>', Cp::fieldHtml('<input>', ['label' => 'Label', 'id' => 'id']));
        self::assertStringNotContainsString('<label', Cp::fieldHtml('<input>', ['label' => '__blank__',]));
        // invalid site ID
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            Cp::fieldHtml('<input>', ['siteId' => -1]);
        });
        // fieldset + legend
        $fieldset = Cp::fieldHtml('<input>', ['fieldset' => 'true', 'label' => 'Label']);
        self::assertStringContainsString('<fieldset', $fieldset);
        self::assertStringContainsString('<legend', $fieldset);
        // translatable
        self::assertStringContainsString('class="t9n-indicator"', Cp::fieldHtml('<input>', ['label' => 'Label', 'translatable' => true]));
        // instructions
        $withInstructions = Cp::fieldHtml('<input>', ['instructionsId' => 'inst-id', 'instructions' => '**Test**']);
        self::assertStringContainsString('id="inst-id"', $withInstructions);
        self::assertStringContainsString('<p><strong>Test</strong></p>', $withInstructions);
        // tip
        self::assertStringContainsString('<p id="tip" class="notice has-icon"><span class="icon" aria-hidden="true"></span><span class="visually-hidden">Tip: </span><span><strong>Test</strong></span></p>', Cp::fieldHtml('<input>', [
            'tipId' => 'tip',
            'tip' => '**Test**',
        ]));
        // warning
        self::assertStringContainsString('<p id="warning" class="warning has-icon"><span class="icon" aria-hidden="true"></span><span class="visually-hidden">Warning: </span><span><strong>Test</strong></span></p>', Cp::fieldHtml('<input>', [
            'warningId' => 'warning',
            'warning' => '**Test**',
        ]));
        // errors
        $withErrors = Cp::fieldHtml('<input>', ['errors' => ['Very bad', 'Very, very bad']]);
        self::assertStringContainsString('has-errors', $withErrors);
        self::assertRegExp('/<ul id="[\w\-]+" class="errors">/', $withErrors);
        // invalid template path
        $this->tester->expectThrowable(TemplateLoaderException::class, function() {
            Cp::fieldHtml('template:invalid/template.twig', []);
        });
    }

    /**
     * @dataProvider fieldMethodsDataProvider
     *
     * @param string $needle
     * @param string $method
     * @param array $config
     */
    public function testFieldMethods(string $needle, string $method, array $config = [])
    {
        self::assertStringContainsString($needle, call_user_func([Cp::class, $method], $config));
    }

    /**
     * @return array
     */
    public function fieldMethodsDataProvider(): array
    {
        return [
            ['type="checkbox"', 'checkboxFieldHtml'],
            ['color-input', 'colorFieldHtml'],
            [
                'editable', 'editableTableFieldHtml', [
                'name' => 'test',
            ]
            ],
            ['lightswitch', 'lightswitchFieldHtml'],
            ['<select', 'selectFieldHtml'],
            ['type="text"', 'textFieldHtml'],
            [
                '<div class="label light">Test unit</div>', 'textFieldHtml', [
                'unit' => 'Test unit',
            ]
            ],
            ['<textarea', 'textareaFieldHtml'],
        ];
    }
}
