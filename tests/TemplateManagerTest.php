<?php

require_once __DIR__ . '/../src/Entity/Destination.php';
require_once __DIR__ . '/../src/Entity/Quote.php';
require_once __DIR__ . '/../src/Entity/Site.php';
require_once __DIR__ . '/../src/Entity/Template.php';
require_once __DIR__ . '/../src/Entity/User.php';
require_once __DIR__ . '/../src/Helper/SingletonTrait.php';
require_once __DIR__ . '/../src/Context/ApplicationContext.php';
require_once __DIR__ . '/../src/Repository/Repository.php';
require_once __DIR__ . '/../src/Repository/DestinationRepository.php';
require_once __DIR__ . '/../src/Repository/QuoteRepository.php';
require_once __DIR__ . '/../src/Repository/SiteRepository.php';
require_once __DIR__ . '/../src/TemplateManager.php';

class TemplateManagerTest extends PHPUnit_Framework_TestCase
{
    private $templateManager;

    /**
     * Init the mocks
     */
    public function setUp()
    {
        $this->templateManager = new TemplateManager();
    }

    /**
     * Closes the mocks
     */
    public function tearDown()
    {
    }

    /**
     * @param ?Quote $quote
     * @param ?User $user
     * @param string $expectedSubject
     * @param string $expectedContent
     *
     * @return void
     *
     * @dataProvider provideDatas
     */
    public function testTemplateManager($quote, $user, $expectedSubject, $expectedContent)
    {
        $template = new Template(
            1,
            '[user:first_name] [quote:destination_name] [quote:destination_link] [quote:summary] [quote:summary_html]',
            '[user:first_name] [quote:destination_name] [quote:destination_link] [quote:summary] [quote:summary_html]'
        );

        $message = $this->templateManager->getTemplateComputed($template, [
            'quote' => $quote,
            'user' => $user,
        ]);

        $this->assertEquals($expectedSubject, $message->subject);
        $this->assertEquals($expectedContent, $message->content);

    }

    public function provideDatas()
    {
        $factory = Faker\Factory::create();

        $user = new User($factory->randomNumber(), $factory->firstName, $factory->lastName, $factory->email);
        $quote = new Quote($factory->randomNumber(), $factory->randomNumber(), $factory->randomNumber(), $factory->date());
        $destination = DestinationRepository::getInstance()->getById($quote->destinationId);

        yield "With quote and user" => [
            "quote" => $quote,
            "user" => $user,
            "expectedSubject" => implode(' ', [
                $user->getCapitalizedFirstName(),
                $destination->countryName,
                SiteRepository::getInstance()->getById($quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $quote->id,
                $quote->renderText(),
                $quote->renderHtml()
            ]),
            "expectedContent" => implode(' ', [
                $user->getCapitalizedFirstName(),
                $destination->countryName,
                SiteRepository::getInstance()->getById($quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $quote->id,
                $quote->renderText(),
                $quote->renderHtml()
            ]),
        ];

        yield "With quote and no user" => [
            "quote" => $quote,
            "user" => null,
            "expectedSubject" => implode(' ', [
                ApplicationContext::getInstance()->getCurrentUser()->getCapitalizedFirstName(),
                $destination->countryName,
                SiteRepository::getInstance()->getById($quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $quote->id,
                $quote->renderText(),
                $quote->renderHtml()
            ]),
            "expectedContent" => implode(' ', [
                ApplicationContext::getInstance()->getCurrentUser()->getCapitalizedFirstName(),
                $destination->countryName,
                SiteRepository::getInstance()->getById($quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $quote->id,
                $quote->renderText(),
                $quote->renderHtml()
            ]),
        ];

        yield "With no quote and user" => [
            "quote" => null,
            "user" => $user,
            "expectedSubject" => $user->getCapitalizedFirstName() . ' [quote:destination_name] [quote:destination_link] [quote:summary] [quote:summary_html]',
            "expectedContent" => $user->getCapitalizedFirstName() . ' [quote:destination_name] [quote:destination_link] [quote:summary] [quote:summary_html]',
        ];

        yield "With no quote and no user" => [
            "quote" => null,
            "user" => null,
            "expectedSubject" => ApplicationContext::getInstance()->getCurrentUser()->getCapitalizedFirstName() . ' [quote:destination_name] [quote:destination_link] [quote:summary] [quote:summary_html]',
            "expectedContent" => ApplicationContext::getInstance()->getCurrentUser()->getCapitalizedFirstName() . ' [quote:destination_name] [quote:destination_link] [quote:summary] [quote:summary_html]',
        ];
    }
}
