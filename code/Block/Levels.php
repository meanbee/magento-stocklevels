<?php
class Meanbee_StockLevels_Block_Levels extends Mage_Catalog_Block_Product_View_Abstract {

    protected $_currentProduct = NULL;
    protected $_options = NULL;
    protected $_optionCount = NULL;
    protected $_associatedProducts = NULL;
    protected $_option2ProductMap = NULL;
    protected $_highestStockLevel = NULL;

    /**
     * Constructor for this block.
     */
    protected function _construct() {
        $this->_currentProduct = Mage::registry('current_product');
        $this->_currentProduct = Mage::getModel('catalog/product')->load($this->_currentProduct->getId());
        if ($this->_currentProduct->isConfigurable()) {
            $this->_associatedProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$this->_currentProduct);
            $this->_options = $this->_currentProduct->getTypeInstance()->getConfigurableAttributesAsArray();
            $this->_optionCount = count($this->_options);
            $this->_highestStockLevel = 0;
        }
        $this->_option2ProductMap = $this->_generateOption2ProductMap(array(), 0, array());
    }


    /**
     * Function which returns the maximum stock level specified in the configurable products 
     * "maximum_stock_level" attribute. This was added to your default attribute set.
     *
     * @return int the maxiumum stock level attribute's value.
     */
    public function getMaximumStockLevel() {
        return $this->_currentProduct->getData('maximum_stock_level');
    }

    /**
     * Recursive function which will build up a datastructive of all possible combinations of
     * configurable options as keys and products or NULL as values.
     *
     * @param array $option2ProductMap The node we are currently working with in the datastructure.
     * @param int $optionIndex The index into $this->_options which represents the current option.
     * @return array returns array to the caller or another array which contains the product and
     * the stock level when called recursively.
     */
    protected function _generateOption2ProductMap($option2ProductMap, $optionIndex, $values) {
        // where $optionIndex == $option count we are at a leaf in our tree.
        if ($optionIndex == $this->_optionCount) {
            // find the product (if it exists) from these values.
            $product = $this->_getProductFromOptionValues($values);

            $stockLevel = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty(); 
            // store the highest stock level seen so far. This is only used if no value is set in the admin
            // area.
            if ($stockLevel > $this->_highestStockLevel) {
                $this->_highestStockLevel = $stockLevel;
            }

            return array('product' => $product, 'stockLevel' => $stockLevel);
        }
        
        foreach ($this->_options[$optionIndex]['values'] as $optionValue) {
            // store the sequence of option values in the values array.
            array_push($values, $optionValue['store_label']);
            $option2ProductMap[$optionValue['store_label']] = $this->_generateOption2ProductMap($option2ProductMap['store_label'], $optionIndex + 1, $values);
            array_pop($values);

        }
        return $option2ProductMap;
    }

    /**
     * Function which returns a "Label" to "Value" map. Here labels are strings which are forms of 
     * concatenated options. For example if we have a car which has two options: Manufacturer and Colour, 
     * then some labels may be:
     *      - Ford Red
     *      - Ford Blue
     *      - Nissan Red
     *      - Nissan Green
     * Values are simply the number of this product which are in stock.
     *
     * @return array the label value map which has been contructed.
     */
    public function getLabelValueMap() {
        $labelValueMap = array();
        $this->_getLabelValueMap($this->_option2ProductMap, '', $labelValueMap);
        return $labelValueMap;
    }

    /**
     * Recursive component of getLabelValueMap(). Iterates over the option2ProductMap and
     * produces a more frontend friendly version of the datastructure.
     *
     * @param array $option2ProductMap data structure which contains a map between option values to 
     * products and stock levels. Function recurses over all the nodes.
     * @param string $label A string which represents the sequence of options which identifies this simple
     * product.
     * @param array $labelValueMap the data structure we are building with this function.
     * @return array the completed data structure.
     */
    protected function _getLabelValueMap($option2ProductMap, $label, &$labelValueMap) {
        if (isset($option2ProductMap['stockLevel'])) {
            // if the product is NULL we have no product matching this label, so add no entry.
            if ($option2ProductMap['product'] != NULL) {
                // trim the whitespace at the start of the label
                $labelValueMap[trim($label)] = $option2ProductMap['stockLevel'];
            }
        } else {
            foreach(array_keys($option2ProductMap) as $key) {
                $this->_getLabelValueMap($option2ProductMap[$key], $label . ' ' . $key, $labelValueMap);
            }
            return $labelValueMap;
        }
    }

    /**
     * Simple getter for the highest stock level found for the associated products of the configurable
     * product.
     *
     * @return int the highest stock level found for the associated products of the configurable product.
     */
    public function getHighestStockLevel() {
        return $this->_highestStockLevel;
    }
    
    /**
     * Function which identifies a product form an array of values
     *
     * @param array $values an array of strings which can be used to identify a product
     * @return Mage_Catalog_Model_Product|NULL The product which matches the options or NULL if none do.
     */
    protected function _getProductFromOptionValues($values) {
        foreach ($this->_associatedProducts as $product) {
            $count = 0;
            for ($i = 0; $i < $this->_optionCount; $i++) {
                if ($product->getAttributeText($this->_options[$i]['attribute_code']) == $values[$i]) {
                    $count++;
                }
            }

            if ($count == $this->_optionCount) {
                return $product;
            } 
        }
        return NULL;
    }
}
