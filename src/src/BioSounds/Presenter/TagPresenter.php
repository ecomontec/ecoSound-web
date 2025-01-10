<?php

namespace BioSounds\Presenter;

class TagPresenter
{

    private $position;

    private $id;

    private $soundscape_component;

    private $style;

    private $top;

    private $left;

    private $height;

    private $width;

    private $title;

    private $color;

    private $public;

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     * @return TagPresenter
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return TagPresenter
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSoundscapeComponent()
    {
        return $this->soundscape_component;
    }

    /**
     * @param mixed $soundscape_component
     * @return TagPresenter
     */
    public function setSoundscapeComponent($soundscape_component)
    {
        $this->soundscape_component = $soundscape_component;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSoundType(): ?string
    {
        return $this->sound_type ;
    }

    /**
     * @param string|null $sound_type
     * @return TagPresenter
     */
    public function setSoundType(?string $sound_type)
    {
        $this->sound_type = $sound_type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param mixed $style
     * @return TagPresenter
     */
    public function setStyle($style)
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * @param mixed $top
     * @return TagPresenter
     */
    public function setTop($top)
    {
        $this->top = $top;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param mixed $left
     * @return TagPresenter
     */
    public function setLeft($left)
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $height
     * @return TagPresenter
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param mixed $width
     * @return TagPresenter
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return TagPresenter
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     * @return TagPresenter
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param mixed $public
     * @return TagPresenter
     */
    public function setPublic($public)
    {
        $this->public = $public;
        return $this;
    }
}
