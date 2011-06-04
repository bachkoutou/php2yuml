<?php
/**
 * Car Class 
 */
class Car extends FourWheels implements Engine
{
    /**
     * Color 
     * @var string 
     */
    private $color;
    /**
     * TODO: description.
     * 
     * @var mixed
     */
    protected $engine;

    /**
     * TODO: description.
     * 
     * @var mixed
     */
    public $price;
    /**
     * Constructor
     *
     * @param  string $color the color 
     */
    public function __construct($color)
    {
        $this->color = $color;
    }

    /**
     * Color Getter
     * 
     * @return string the color
     */
    public function getColor() 
    {
        return $this->color;
    }

    /**
     * Run Method 
     */
    public function run()
    {
    }

    /**
     * Stop Method 
     */
    public function stop()
    {
    }
}
