<?php
namespace API_SD_LAR;

if (!class_exists('\API_SD_LAR\Customer')) {
    class Customer {
        /**
         * @var string Customer's first name.
         */
        public string $firstName;
        /**
         * @var string Customer's last name.
         */
        public string $lastName;
        /**
         * @var string Customer's full name.
         */
        public string $name;
        /**
         * @var string Customer's phone number.
         */
        public string $phone;
        /**
         * @var string Customer's email.
         */
        public string $email;

        /**
         * @param string $firstName Customer's first name.
         * @param string $lastName Customer's last name.
         * @param string $phone Customer's phone number.
         * @param string $email Customer's email.
         */
        public function __construct(string $firstName = '', string $lastName = '',
                                    string $phone = '', string $email = '') {
            $this->firstName = esc_attr($firstName);
            $this->lastName = esc_attr($lastName);
            $this->name = esc_attr($firstName . ' ' . $lastName);
            $this->phone = esc_attr($phone);
            $this->email = esc_attr($email);
        }
    }
}