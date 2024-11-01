<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('Ship_Discounts_Box')) {
    /**
     * Class managing the boxes for packaging.
     */
    class Ship_Discounts_Box {
        /**
         * @var string|null Box name.
         */
        public $name;
        /**
         * @var float Box additional cost.
         */
        public $price;
        /**
         * @var int|string Box shipping class.
         */
        public $class;
        /**
         * @var float Empty box weight.
         */
        public $weight;
        /**
         * @var mixed Box maximum weight (own weight + product weights).
         */
        public $max_weight;
        /**
         * @var float|mixed Box inner height.
         */
        public $height;
        /**
         * @var mixed Box outer height.
         */
        public $outer_height;
        /**
         * @var float|mixed Box inner width.
         */
        public $width;
        /**
         * @var mixed Box outer width.
         */
        public $outer_width;
        /**
         * @var float|mixed Box inner length.
         */
        public $length;
        /**
         * @var mixed Box outer length.
         */
        public $outer_length;
        /**
         * @var float|int Box volume.
         */
        private $volume;
        /**
         * @var array Box sorted dimensions.
         */
        private $dimensions;

        /**
         * Constructor.
         * @param $name string|null Box name.
         * @param $length int|float|string Box inner length.
         * @param $outer_length int |float|string Box outer length.
         * @param $width int|float|string Box inner width.
         * @param $outer_width int|float|string Box outer width.
         * @param $height int|float|string Box inner height.
         * @param $outer_height int|float|string Box outer height.
         * @param $weight int|float|string Empty box weight.
         * @param $max_weight int|float|string Box maximum weight (own weight + product weights).
         * @param $price int|float|string Box additional cost.
         */
        public function __construct($name, $length, $outer_length, $width, $outer_width, $height, $outer_height, $weight, $max_weight, $price = 0, $class = "") {
            // Name
            if (!$name) $name = '';
            $this->name = sanitize_text_field($name) ?: esc_html__('Box', 'ship-discounts');

            // Price
            $float_price = floatval(str_replace(',', '.', $price));
            $this->price = $float_price >= 0 ? $float_price : $float_price * -1.0;

            // Class
            if (!$class) $class = '';
            $this->class = sanitize_text_field($class);

            // Length
            $float_length = floatval(str_replace(',', '.', $length));
            $this->length = $float_length >= 0 ? $float_length : $float_length * -1.0;

            $float_outer_length = floatval(str_replace(',', '.', $outer_length));
            $this->outer_length = $float_outer_length >= 0 ? $float_outer_length : $float_outer_length * -1.0;

            $this->outer_length = max($this->outer_length, $this->length);
            $this->length = $this->length ?: $this->outer_length;

            // Width
            $float_width = floatval(str_replace(',', '.', $width));
            $this->width = $float_width >= 0 ? $float_width : $float_width * -1.0;

            $float_outer_width = floatval(str_replace(',', '.', $outer_width));
            $this->outer_width = $float_outer_width >= 0 ? $float_outer_width : $float_outer_width * -1.0;

            $this->outer_width = max($this->outer_width, $this->width);
            $this->width = $this->width ?: $this->outer_width;

            // Height
            $float_height = floatval(str_replace(',', '.', $height));
            $this->height = $float_height >= 0 ? $float_height : $float_height * -1.0;

            $float_outer_height = floatval(str_replace(',', '.', $outer_height));
            $this->outer_height = $float_outer_height >= 0 ? $float_outer_height : $float_outer_height * -1.0;

            $this->outer_height = max($this->outer_height, $this->height);
            $this->height = $this->height ?: $this->outer_height;

            // Weight
            $float_weight = floatval(str_replace(',', '.', $weight));
            $this->weight = $float_weight >= 0 ? $float_weight : $float_weight * -1.0;

            $float_max_weight = floatval(str_replace(',', '.', $max_weight));
            $this->max_weight = $float_max_weight >= 0 ? $float_max_weight : $float_max_weight * -1.0;
            $this->max_weight = max($this->max_weight, $this->weight);

            // Volume
            $this->volume = $this->length * $this->width * $this->height;

            // Dimensions
            $this->dimensions = [$this->length, $this->width, $this->height];
            sort($this->dimensions);
        }

        /**
         * Checks if an item fits in the box.
         * @param array $item Item.
         * @return bool If the item fits in the box.
         */
        public function checkFit($item) {
            $item_dim = [floatval($item['length']),
                         floatval($item['width']),
                         floatval($item['height'])];
            sort($item_dim);

            return $this->dimensions[0] >= $item_dim[0] &&
                $this->dimensions[1] >= $item_dim[1] &&
                $this->dimensions[2] >= $item_dim[2];
        }

        /**
         * Calculates the volume of an item.
         * @param array $item Item.
         * @return float|int Volume of the item.
         */
        private static function getItemVolume($item) {
            return floatval($item['length']) * floatval($item['width']) * floatval($item['height']);
        }

        /**
         * Pack items in a box.
         * @param array $items Items to pack.
         * @return object Packaged items.
         */
        private function packItems($items) {
            $packed = [];
            $unpacked = [];
            $packed_weight = $this->weight;
            $packed_volume = 0;

            // If a class is specified and not all items have it, this box can not be used
            if ($this->class) {
                foreach ($items as $item) {
                    if ($this->class != $item['class']) {
                        $unpacked = $items;
                        $items = [];
                        break;
                    }
                }
            }

            // Try to pack all the items in the box
            foreach ($items as $item) {
                $item_vol = self::getItemVolume($item);

                // Check volume
                if (($packed_volume + $item_vol) > $this->volume) {
                    $unpacked[] = $item;
                    continue;
                }

                // Check max weight
                if (($packed_weight + $item['weight']) > $this->max_weight) {
                    $unpacked[] = $item;
                    continue;
                }

                // Check dimensions
                if (!$this->checkFit($item)) {
                    $unpacked[] = $item;
                    continue;
                }

                $packed[] = $item;
                $packed_volume += $item_vol;
                $packed_weight += $item['weight'];
            }

            // Get weight and volume of unpacked items (for calculating the packing success rate)
            $unpacked_weight = $unpacked_volume = 0;
            foreach ($unpacked as $item) {
                $unpacked_weight += $item['weight'];
                $unpacked_volume += self::getItemVolume($item);
            }

            // Create the package
            $package = new Ship_Discounts_Package(
                $this->outer_length, $this->outer_width, $this->outer_height,
                $packed_weight, $this->price, 0, $packed, $unpacked
            );

            // Calculate packing success rate
            $weight_ratio = $volume_ratio = null;
            $weight_diff = $packed_weight - $this->weight;
            $weight_total = $weight_diff + $unpacked_weight;
            $volume_total = $packed_volume + $unpacked_volume;

            if ($weight_total > 0) $weight_ratio = $weight_diff / $weight_total;
            if ($volume_total > 0) $volume_ratio = $packed_volume / $volume_total;

            // If both null, use the amount of items
            if ($weight_ratio === null && $volume_ratio === null)
                $package->percent = (count($packed) / (count($unpacked) + count($packed))) * 100;
            // If weight null, use the volume
            else if ($weight_ratio === null)
                $package->percent = $volume_ratio * 100;
            // If volume null, use the weight
            else if ($volume_ratio === null)
                $package->percent = $weight_ratio * 100;
            // Use volume and weight
            else
                $package->percent = $weight_ratio * $volume_ratio * 100;

            return $package;
        }

        /**
         * Packs all items in boxes.
         * @param $boxes Ship_Discounts_Box[] Boxes.
         * @param $items array Items.
         * @return Ship_Discounts_Package[] Packaged items.
         */
        public static function packAllItems(array $boxes, array $items): array {
            $packages = [];
            $cannot_pack = [];

            if (!count($items))
                return $packages;

            // If there are no boxes, items will be packaged individually
            if (!$boxes) {
                $cannot_pack = $items;
                $items = [];
            }

            // Try to pack all the items in boxes
            while (count($items) > 0) {
                // Small items first
                uasort($items, function ($a, $b) {
                    $vol_a = self::getItemVolume($a);
                    $vol_b = self::getItemVolume($b);

                    if ($vol_a == $vol_b) {
                        if ($a['weight'] == $b['weight']) return 0;
                        return ($a['weight'] < $b['weight']) ? 1 : -1;
                    }
                    return ($vol_a < $vol_b) ? 1 : -1;
                });

                // Possible packages and the best one
                $maybes = [];
                $best = null;

                // Try all the boxes
                foreach ($boxes as $box) {
                    $maybes[] = $box->packItems($items);
                }

                // Find the best success rate
                $percent = 0;
                foreach ($maybes as $package) {
                    if ($package->percent > $percent) {
                        $percent = $package->percent;
                    }
                }
                // If no boxes can be used, items will be packaged individually
                if ($percent <= 0) {
                    $cannot_pack = $items;
                    $items = [];
                }
                // Find the best box based on admin order
                else {
                    foreach ($maybes as $package) {
                        if ($package->percent == $percent) {
                            $best = $package;
                            break;
                        }
                    }

                    $packages[] = $best;
                    // Start again with the items not packed yet
                    $items = $best->unpacked;
                }
            }

            // Package unfitting items individually
            if ($cannot_pack) {
                foreach ($cannot_pack as $item) {
                    $packages[] = new Ship_Discounts_Package(
                        $item['length'], $item['width'], $item['height'], $item['weight']
                    );
                }
            }

            return $packages;
        }
    }
}