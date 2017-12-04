<?php

namespace FlashModalBundle\Service;

use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Symfony\Bundle\TwigBundle\TwigEngine;


class FlashModalMessageService
{
    /** @var FlashMessage */
    private $flashService;

    /** @var TwigEngine */
    private $templating;

    /**
     * Constructor.
     *
     * @param $flashService
     * @param $templating
     */
    public function __construct($flashService, $templating)
    {
        $this->flashService = $flashService;
        $this->templating = $templating;
    }

    /**
     * Sets an alert message.
     *
     * @param $title
     * @param string $message The message
     * @param bool $smallModal
     */
    public function alert($title, $message = '', $smallModal = true)
    {
        $this->flashService->alert($this->renderMessage($title, $message, $smallModal));
    }

    /**
     * Alias for `danger()`.
     *
     * @param $title
     * @param string $message The message
     * @param bool $smallModal
     */
    public function error($title, $message = '', $smallModal = true)
    {
        $this->flashService->danger($this->renderMessage($title, $message, $smallModal));
    }

    /**
     * Sets a danger message.
     *
     * @param $title
     * @param string $message
     * @param bool $smallModal
     */
    public function danger($title, $message = '', $smallModal = true)
    {
        $this->flashService->danger($this->renderMessage($title, $message, $smallModal));
    }

    /**
     * Sets an info message.
     *
     * @param $title
     * @param string $message The message
     * @param bool $smallModal
     */
    public function info($title, $message = '', $smallModal = true)
    {
        $this->flashService->info($this->renderMessage($title, $message, $smallModal));
    }

    /**
     * Sets a success message.
     *
     * @param $title
     * @param string $message The message
     * @param bool $smallModal
     */
    public function success($title, $message = '', $smallModal = true)
    {
        $this->flashService->success($this->renderMessage($title, $message, $smallModal));
    }

    private function renderMessage($title, $message, $smallModal)
    {
        return $this->templating->render('@Website/Modal/simple_modal.html.twig', [
            'title' => $title,
            'description' => $message,
            'smallModal' => $smallModal,
        ]);
    }
}
