<?php
namespace API_SD_LAR;

if (!class_exists('\API_SD_LAR\ShippingLines')) {
    class ShippingLines {
        /**
         * @var string Carrier code.
         */
        public string $carrierCode;
        /**
         * @var string Service code.
         */
        public string $serviceCode;
        /**
         * @var float Shipping price.
         */
        public float $price;
        /**
         * @var float Shipping displayed price.
         */
        public float $displayPrice;
        /**
         * @var string Tracking number.
         */
        public string $trackingNumber;
        /**
         * @var string Tracking URL.
         */
        public string $trackingUrl;
        /**
         * @var float Price of the boxes used.
         */
        public float $boxesPrice;

        /**
         * @param string $carrierCode Carrier code.
         * @param string $serviceCode Service code.
         * @param float $price Shipping price.
         * @param float $displayPrice Shipping displayed price.
         * @param string $trackingNumber Tracking number.
         * @param string $trackingUrl Tracking URL.
         * @param float $boxesPrice Price of the boxes used.
         */
        public function __construct(string $carrierCode = '', string $serviceCode = '',
                                    float $price = 0, float $displayPrice = 0,
                                    string $trackingNumber = '', string $trackingUrl = '',
                                    float $boxesPrice = 0) {
            $this->carrierCode = esc_attr($carrierCode);
            $this->serviceCode = esc_attr($serviceCode);
            $this->price = esc_attr($price);
            $this->displayPrice = esc_attr($displayPrice);
            $this->trackingNumber = esc_attr($trackingNumber);
            $this->trackingUrl = esc_attr($trackingUrl);
            $this->boxesPrice = esc_attr($boxesPrice);
        }
    }
}