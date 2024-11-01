<?php
namespace API_SD_LAR;

if (!class_exists('\API_SD_LAR\Address')) {
    class Address {
        /**
         * @var string Address.
         */
        public string $address1;
        /**
         * @var string Complementary address.
         */
        public string $address2;
        /**
         * @var string City.
         */
        public string $city;
        /**
         * @var string Company name.
         */
        public string $company;
        /**
         * @var string Country.
         */
        public string $country;
        /**
         * @var string Province.
         */
        public string $province;
        /**
         * @var string ZIP or postal code.
         */
        public string $zip;

        /**
         * @param string $address1 Address.
         * @param string $address2 Complementary address.
         * @param string $city City.
         * @param string $company Company name.
         * @param string $country Country.
         * @param string $province Province or state.
         * @param string $zip ZIP or postal code.
         */
        public function __construct(string $address1 = '', string $address2 = '', string $city = '',
                                    string $company = '', string $country = '',
                                    string $province = '', string $zip = '') {
            $this->address1 = esc_attr($address1);
            $this->address2 = esc_attr($address2);
            $this->city = esc_attr($city);
            $this->company = esc_attr($company);
            $this->country = esc_attr($country);
            $this->province = esc_attr($province);
            $this->zip = esc_attr($zip);
        }
    }
}
