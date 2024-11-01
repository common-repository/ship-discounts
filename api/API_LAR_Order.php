<?php
namespace API_SD_LAR;

require_once 'API_LAR_Customer.php';
require_once 'API_LAR_Address.php';
require_once 'API_LAR_LineItem.php';
require_once 'API_LAR_ShippingLines.php';

use DateTime;

if (!class_exists('\API_SD_LAR\Order')) {
    class Order {
        /**
         * @var int Order id.
         */
        public int $id;
        /**
         * @var string Order status.
         */
        public string $orderStatus;
        /**
         * @var float Shipping total.
         */
        public float $shippingTotal;
        /**
         * @var float Order subtotal.
         */
        public float $subtotal;
        /**
         * @var float Tax total.
         */
        public float $taxTotal;
        /**
         * @var float Order total.
         */
        public float $total;
        /**
         * @var string Shipping method title.
         */
        public string $methodTitle;
        /**
         * @var string Shipping method ID.
         */
        public string $methodID;
        /**
         * @var Customer Ordering customer.
         */
        public Customer $customer;
        /**
         * @var Address Billing address.
         */
        public Address $billingAddress;
        /**
         * @var Address Shipping address.
         */
        public Address $shippingAddress;
        /**
         * @var array Products.
         */
        public array $lineItems;
        /**
         * @var ShippingLines Shipping information.
         */
        public ShippingLines $shippingLines;
        /**
         * @var string Note.
         */
        public string $note;
        /**
         * @var DateTime Date of creation.
         */
        public DateTime $createdAt;
        /**
         * @var DateTime Date of last update.
         */
        public DateTime $updatedAt;
        /**
         * @var DateTime|null Date of closure.
         */
        public ?DateTime $closedAt;

        /**
         * @param int $id Order id.
         * @param string $orderStatus Order status.
         * @param float $shippingTotal Shipping total.
         * @param float $subtotal Order subtotal.
         * @param float $taxTotal Tax total.
         * @param float $total Order total.
         * @param string $methodTitle Shipping method title.
         * @param string $methodID Shipping method ID.
         * @param Customer|null $customer Ordering customer.
         * @param Address|null $billingAddress Billing address.
         * @param Address|null $shippingAddress Shipping address.
         * @param array $lineItems Products.
         * @param ShippingLines|null $shippingLines Shipping information.
         * @param string $note Note.
         * @param DateTime|null $createdAt Date of creation.
         * @param DateTime|null $updatedAt Date of last update.
         * @param DateTime|null $closedAt Date of closure.
         */
        public function __construct(int $id = 0, string $orderStatus = '', float $shippingTotal = 0, float $subtotal = 0,
                                    float $taxTotal = 0, float $total = 0, string $methodTitle = '', string $methodID = '', Customer $customer = null,
                                    Address $billingAddress = null, Address $shippingAddress = null,
                                    array $lineItems = [], ShippingLines $shippingLines = null,
                                    string $note = '', DateTime $createdAt = null, DateTime $updatedAt = null,
                                    DateTime $closedAt = null) {
            $this->id = esc_attr($id);
            $this->orderStatus = esc_attr($orderStatus);
            $this->shippingTotal = esc_attr($shippingTotal);
            $this->subtotal = esc_attr($subtotal);
            $this->taxTotal = esc_attr($taxTotal);
            $this->total = esc_attr($total);
            $this->methodTitle = esc_attr($methodTitle);
            $this->methodID = esc_attr($methodID);
            $this->customer = $customer ?: new Customer();
            $this->billingAddress = $billingAddress ?: new Address();
            $this->shippingAddress = $shippingAddress ?: new Address();
            $this->lineItems = $lineItems;
            $this->shippingLines = $shippingLines ?: new ShippingLines();
            $this->note = esc_attr($note);
            $this->createdAt = $createdAt ?: new DateTime();
            $this->updatedAt = $updatedAt ?: new DateTime();
            $this->closedAt = $closedAt;
        }
    }
}