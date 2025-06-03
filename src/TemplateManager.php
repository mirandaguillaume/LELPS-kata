<?php

class TemplateManager
{
    private $applicationContext;
    private $siteRepository;
    private $destinationRepository;

    public function __construct()
    {
        $this->applicationContext = ApplicationContext::getInstance();
        $this->siteRepository = SiteRepository::getInstance();
        $this->destinationRepository = DestinationRepository::getInstance();
    }

    public function getTemplateComputed(Template $tpl, array $data)
    {
        $quote = (isset($data['quote']) && $data['quote'] instanceof Quote) ? $data['quote'] : null;
        $user = (isset($data['user']) && ($data['user'] instanceof User)) ? $data['user'] : $this->applicationContext->getCurrentUser();

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $quote, $user);
        $replaced->content = $this->computeText($replaced->content, $quote, $user);

        return $replaced;
    }

    /**
     * @param string     $text
     * @param Quote|null $quote
     * @param User|null  $user
     *
     * @return string
     */
    private function computeText($text, $quote, User $user)
    {
        if ($quote) {
            $destination = $this->destinationRepository->getById($quote->destinationId);

            $text = str_replace(
                [
                    '[quote:summary]',
                    '[quote:summary_html]',
                    '[quote:destination_name]',
                    '[quote:destination_link]',
                ],
                [
                    $quote->renderText(),
                    $quote->renderHtml(),
                    $destination->countryName,
                    $this->siteRepository->getById($quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $quote->id,
                ],
                $text
            );
        }

        return str_replace('[user:first_name]', ucfirst(mb_strtolower($user->firstname)), $text);
    }
}
