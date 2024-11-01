<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('Ship_Discounts_Package')) {
    /**
     * Class for the packages created with Ship Discounts boxes.
     */
    class Ship_Discounts_Package {
        /**
         * @var float Package length.
         */
        public float $length;
        /**
         * @var float Package width.
         */
        public float $width;
        /**
         * @var float Package height.
         */
        public float $height;
        /**
         * @var float Package weight.
         */
        public float $weight;
        /**
         * @var float Package additional cost.
         */
        public float $price;
        /**
         * @var float Packaging success rate (%).
         */
        public float $percent;
        /**
         * @var array Items in the package.
         */
        public array $packed;
        /**
         * @var array Items not in the package.
         */
        public array $unpacked;

        /**
         * Constructor.
         * @param $length float|int|string Package length.
         * @param $width float|int|string Package width.
         * @param $height int|float|string Package height.
         * @param $weight int|float|string Package weight.
         * @param $price int|float|string Package additional cost.
         * @param $percent int|float|string Packaging success rate.
         * @param $packed array Items in the package (%).
         * @param $unpacked array Items not in the package.
         */
        public function __construct($length, $width, $height, $weight, $price = 0, $percent = 100, array $packed = [], array $unpacked = []) {
            $this->length = floatval($length);
            $this->width = floatval($width);
            $this->height = floatval($height);
            $this->weight = floatval($weight);
            $this->price = floatval($price);
            $this->percent = $percent;
            $this->packed = $packed;
            $this->unpacked = $unpacked;
        }
    }
}