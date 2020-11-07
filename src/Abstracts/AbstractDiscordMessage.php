<?php
namespace CarloNicora\RAWBot\Abstracts;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\RAWBot\RAWBot;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Embed\Footer;
use Discord\Parts\Embed\Image;
use Exception;

abstract class AbstractDiscordMessage
{
    public const MESSAGE_INFO = 0x0000ff;
    public const MESSAGE_ACTION_SUCCESS = 0x00ff00;
    public const MESSAGE_ACTION_FAILURE = 0xff0000;
    public const MESSAGE_ERROR = 0xff0000;

    /** @var ServicesFactory  */
    protected ServicesFactory $services;

    /** @var RAWBot  */
    protected RAWBot $RAWBot;

    /** @var Embed  */
    protected Embed $message;

    /** @var bool  */
    protected bool $isPrivateMessage=false;

    /** @var string  */
    protected string $additionalMessage='';

    /**
     * AbstractDiscordMessage constructor.
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(ServicesFactory $services)
    {
        $this->services = $services;
        $this->RAWBot = $this->services->service(RAWBot::class);

        $this->message = $this->RAWBot->getDiscord()->factory(
            Embed::class,
            [],
            true
        );

        $this->message->author = $this->RAWBot->getDiscord()->factory(
            Author::class,
            [
                'name' => $this->RAWBot->getDiscord()->user->username,
                'icon_url' => $this->RAWBot->getDiscord()->user->avatar
            ],
            true
        );

        $this->message->footer = $this->RAWBot->getDiscord()->factory(
            Footer::class,
            [],
            true
        );
    }

    /**
     * @param string $imageURL
     */
    public function setFooterThumbnail(string $imageURL): void
    {
        $this->message->footer->icon_url = $imageURL;
    }

    /**
     * @param string $additionalMessage
     */
    public function setAdditionalMessage(string $additionalMessage): void
    {
        $this->additionalMessage = $additionalMessage;
    }

    /**
     * @return string
     */
    public function getAdditionalMessage(): string
    {
        return $this->additionalMessage;
    }

    /**
     * @param string $imageURL
     */
    public function setImage(string $imageURL): void
    {
        $this->message->image = $this->RAWBot->getDiscord()->factory(
            Image::class,
            [
                'url' => $imageURL
            ],
            true
        );
    }

    /**
     * @param string $thumbnailURL
     */
    public function setThumbnailUrl(string $thumbnailURL) : void
    {
        $this->message->thumbnail = $this->RAWBot->getDiscord()->factory(
            Image::class,
            [
                'url' => $thumbnailURL
            ],
            true
        );
    }

    /**
     * @param int $messageType
     */
    public function setMessageType(int $messageType): void
    {
        $this->message->color = $messageType;
    }

    /**
     * @return bool
     */
    public function isPrivateMessage(): bool
    {
        return $this->isPrivateMessage;
    }

    /**
     * @param bool $isPrivateMessage
     */
    public function setIsPrivateMessage(bool $isPrivateMessage): void
    {
        $this->isPrivateMessage = $isPrivateMessage;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->message->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->message->title;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->message->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->message->description;
    }

    /**
     * @param string $message
     */
    public function setFooter(string $message): void
    {
        $this->message->footer->text = $message;
    }

    /**
     * @param string $title
     * @param string $content
     * @param bool $inline
     */
    public function addField(string $title, string $content, bool $inline=false): void
    {
        /** @var Field $field */
        $field = $this->RAWBot->getDiscord()->factory(
            Field::class,
            [
                'name' => $title,
                'value' => $content,
                'inline' => $inline
            ],
            true
        );

        $this->message->addField($field);
    }

    /**
     * @return Embed
     */
    public function getMessage(): Embed
    {
        return $this->message;
    }
}