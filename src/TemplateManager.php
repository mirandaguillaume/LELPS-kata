<?php

class TemplateManager
{
    private $applicationContext;
    private $quoteRepository;
    private $siteRepository;
    private $destinationRepository;

    public function __construct()
    {
        $this->applicationContext = ApplicationContext::getInstance();
        $this->quoteRepository = QuoteRepository::getInstance();
        $this->siteRepository = SiteRepository::getInstance();
        $this->destinationRepository = DestinationRepository::getInstance();
    }

    public function getTemplateComputed(Template $tpl, array $data)
    {
        $quote = (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;
        $user  = (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $this->applicationContext->getCurrentUser();

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $quote, $user);
        $replaced->content = $this->computeText($replaced->content, $quote, $user);

        return $replaced;
    }

    /**
     * @param string $text
     * @param Quote|null $quote
     * @param User|null $user
     *
     * @return string
     */
    private function computeText($text, $quote, $user)
    {

        if ($quote)
        {
            $_quoteFromRepository = $this->quoteRepository->getById($quote->id);
            $destination = DestinationRepository::getInstance()->getById($quote->destinationId);

            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[quote:summary_html]',
                        Quote::renderHtml($_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[quote:summary]',
                        Quote::renderText($_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$destination->countryName,$text);
        }

        if (strpos($text, '[quote:destination_link]') !== false) {
            $usefulObject = $this->siteRepository->getById($quote->siteId);
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        } else {
            $text = str_replace('[quote:destination_link]', '', $text);
        }

        /*
         * USER
         * [user:*]
         */
        if($user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($user->firstname)), $text);
        }

        return $text;
    }
}
