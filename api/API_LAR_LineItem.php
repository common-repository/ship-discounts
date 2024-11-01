<?php
namespace API_SD_LAR;

if (!class_exists('\API_SD_LAR\LineItem')) {
    class LineItem {
        /**
         * @var float Product id.
         */
        public float $id;
        /**
         * @var string Product name.
         */
        public string $name;
        /**
         * @var float Product price.
         */
        public float $price;
        /**
         * @var int Product quantity.
         */
        public int $quantity;
        /**
         * @var string Product SKU.
         */
        public string $sku;
        /**
         * @var float|null Product variation id.
         */
        public ?float $variantId;
        /**
         * @var string Product variation name.
         */
        public string $variantName;

        /**
         * @param float $id Product id.
         * @param string $name Product name.
         * @param float $price Product price.
         * @param int $quantity Product quantity.
         * @param string $sku Product SKU.
         * @param float|null $variantId Product variation id.
         * @param string $variantName Product variation name.
         */
        public function __construct(float $id = 0, string $name = '', float $price = 0, int $quantity = 0,
                                    string $sku = '', ?float $variantId = 0, string $variantName = '') {
            $this->id = esc_attr($id);
            $this->name = esc_attr($name);
            $this->price = esc_attr($price);
            $this->quantity = esc_attr($quantity);
            $this->sku = esc_attr($sku);
            $this->variantId = esc_attr($variantId) ?: 0;
            $this->variantName = esc_attr($variantName);
        }
    }
}