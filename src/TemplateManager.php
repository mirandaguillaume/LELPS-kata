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
     * @param User  $user
     *
     * @return string
     */
    private function computeText($text, $quote, User $user)
    {
        if ($quote) {
            $destination = $this->destinationRepository->getById($quote->destinationId);

            $replaceDatas = [
                '[quote:summary]' => $quote->renderText(),
                '[quote:summary_html]' => $quote->renderHtml(),
                '[quote:destination_name]' => $destination->countryName,
                '[quote:destination_link]' => $this->siteRepository->getById($quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $quote->id,
            ];

            $text = str_replace(
                array_keys($replaceDatas),
                array_values($replaceDatas),
                $text
            );
        }

        return str_replace('[user:first_name]', $user->getCapitalizedFirstName(), $text);
    }
}
